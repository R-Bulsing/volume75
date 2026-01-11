<?php
declare(strict_types=1);

require_once __DIR__ . '/config/maintenance.php';
volume75EnforceMaintenanceMode();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/hcaptcha.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Voting has ended; block all further submissions
header('Location: /top75.php?status=closed');
http_response_code(403);
echo 'Voting is closed.';
exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo 'Only POST allowed';
    exit;
}

$userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$refererHost = $referer !== '' ? strtolower((string)(parse_url($referer, PHP_URL_HOST) ?? '')) : '';

if ($userAgent === '' || $refererHost === '' || ($host !== '' && $refererHost !== $host)) {
    header('Location: /top75.php?status=bot');
    exit;
}

if (stripos($userAgent, 'bot') !== false || stripos($userAgent, 'crawler') !== false) {
    header('Location: /top75.php?status=bot');
    exit;
}

function field(string $key): string
{
    return trim($_POST[$key] ?? '');
}

/** @return array<int, string> */
function fieldArray(string $key): array
{
    $raw = $_POST[$key] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn($value) => is_string($value) ? trim($value) : '',
        $raw
    ), static fn($value) => $value !== ''));
}

$name = field('name');
$email = field('email');
$trackIds = array_values(array_unique(fieldArray('track_ids')));
$csrfToken = field('csrf_token');
$honeypot = field('website');

if ($honeypot !== '') {
    header('Location: /top75.php?status=bot');
    exit;
}

$storedCsrf = $_SESSION[VOLUME75_CSRF_SESSION_KEY] ?? '';
if (!is_string($storedCsrf) || $storedCsrf === '' || $csrfToken === '' || !hash_equals($storedCsrf, $csrfToken)) {
    header('Location: /top75.php?status=csrf');
    exit;
}

if ($name === '' || $email === '' || $trackIds === []) {
    header('Location: /top75.php?status=error');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /top75.php?status=error');
    exit;
}

$allowedDomains = ['glr.nl'];
$emailDomain = strtolower(substr(strrchr($email, '@') ?: '', 1));
if ($emailDomain === '' || !in_array($emailDomain, $allowedDomains, true)) {
    header('Location: /top75.php?status=domain');
    exit;
}

$maxSelections = 3;
if (count($trackIds) > $maxSelections) {
    header('Location: /top75.php?status=limit');
    exit;
}

$hcaptchaResponse = field('h-captcha-response');
$hcaptchaSecret = volume75HcaptchaSecret();
$remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');

if ($hcaptchaSecret === '') {
    error_log('hCaptcha secret missing; request denied');
    header('Location: /top75.php?status=captcha');
    exit;
}

if ($hcaptchaResponse === '' || !volume75VerifyHcaptcha($hcaptchaResponse, $hcaptchaSecret, $remoteIp)) {
    header('Location: /top75.php?status=captcha');
    exit;
}

$now = time();
$sessionThrottle = $_SESSION['volume75_vote_throttle'] ?? null;
if (!is_array($sessionThrottle)) {
    $sessionThrottle = [];
}
$sessionWindowSeconds = 24 * 60 * 60; // 24h window
$cooldownSeconds = 5 * 60; // 5 minutes minimum between submissions
$currentWindowStart = $sessionThrottle['window_start'] ?? $now;
$currentCount = (int)($sessionThrottle['count'] ?? 0);
$lastVoteAt = (int)($sessionThrottle['last_vote_at'] ?? 0);

if (!is_int($currentWindowStart)) {
    $currentWindowStart = $now;
}

// Reset the counter if the window expired
if (($now - $currentWindowStart) > $sessionWindowSeconds) {
    $currentWindowStart = $now;
    $currentCount = 0;
}

// Cooldown check blocks rapid resubmits within the same session/device
if ($lastVoteAt > 0 && ($now - $lastVoteAt) < $cooldownSeconds) {
    header('Location: /top75.php?status=throttle');
    exit;
}

// Hard limit: max 1 vote per 24h for the same session/device
if ($currentCount >= 1) {
    header('Location: /top75.php?status=throttle');
    exit;
}

$pdo = null;

try {
    $pdo = getDatabaseConnection();
} catch (Throwable $exception) {
    error_log('Database connection error: ' . $exception->getMessage());
    header('Location: /top75.php?status=error');
    exit;
}

$ipRateLimitBypass = ['145.118.6.57'];

if ($remoteIp !== '' && !in_array($remoteIp, $ipRateLimitBypass, true)) {
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS vote_ip_rate_limit (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ip_hash CHAR(64) NOT NULL,
                ua_hash CHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_ip_time (ip_hash, created_at),
                INDEX idx_ua_time (ua_hash, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $ipHash = hash('sha256', $remoteIp);
        $uaHash = hash('sha256', $userAgent);

        $rateWindowSeconds = 24 * 60 * 60; // 24h window per IP
        $rateWindowLimit = 3; // max votes per IP per window
        $cooldownSeconds = 5 * 60; // minimum gap between IP submissions

        $nowDateTime = new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));
        $windowStart = $nowDateTime->sub(new DateInterval('PT' . $rateWindowSeconds . 'S'))->format('Y-m-d H:i:s');
        $cooldownStart = $nowDateTime->sub(new DateInterval('PT' . $cooldownSeconds . 'S'))->format('Y-m-d H:i:s');
        $currentTime = $nowDateTime->format('Y-m-d H:i:s');

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM vote_ip_rate_limit WHERE ip_hash = :ip_hash AND created_at >= :window_start');
        $countStmt->execute([
            'ip_hash' => $ipHash,
            'window_start' => $windowStart,
        ]);
        $ipWindowCount = (int) $countStmt->fetchColumn();

        if ($ipWindowCount >= $rateWindowLimit) {
            header('Location: /top75.php?status=throttle');
            exit;
        }

        $cooldownStmt = $pdo->prepare('SELECT 1 FROM vote_ip_rate_limit WHERE ip_hash = :ip_hash AND created_at >= :cooldown_start LIMIT 1');
        $cooldownStmt->execute([
            'ip_hash' => $ipHash,
            'cooldown_start' => $cooldownStart,
        ]);

        $cooldownHit = (bool) $cooldownStmt->fetchColumn();

        if ($cooldownHit) {
            header('Location: /top75.php?status=throttle');
            exit;
        }

        $insertRate = $pdo->prepare('INSERT INTO vote_ip_rate_limit (ip_hash, ua_hash, created_at) VALUES (:ip_hash, :ua_hash, :created_at)');
        $insertRate->execute([
            'ip_hash' => $ipHash,
            'ua_hash' => $uaHash,
            'created_at' => $currentTime,
        ]);
    } catch (Throwable $exception) {
        error_log('IP rate limit error: ' . $exception->getMessage());
        header('Location: /top75.php?status=error');
        exit;
    }
}

try {
    $poolStatement = $pdo->query('SELECT id, track, artist FROM track_pool WHERE active = 1 ORDER BY track ASC');
    $pool = $poolStatement->fetchAll() ?: [];
} catch (Throwable $exception) {
    error_log('Track pool query error: ' . $exception->getMessage());
    header('Location: /top75.php?status=error');
    exit;
}

if ($pool === []) {
    header('Location: /top75.php?status=error');
    exit;
}

$poolById = [];
foreach ($pool as $entry) {
    $id = (string)($entry['id'] ?? '');
    if ($id === '') {
        continue;
    }
    $poolById[$id] = $entry;
}

$selectedTracks = [];
foreach ($trackIds as $trackId) {
    if (!isset($poolById[$trackId])) {
        header('Location: /top75.php?status=error');
        exit;
    }
    $selectedEntry = $poolById[$trackId];
    $selectedTracks[] = [
        'id' => $trackId,
        'track' => $selectedEntry['track'] ?? 'Onbekende track',
        'artist' => $selectedEntry['artist'] ?? 'Onbekende artiest',
    ];
}

$currentTimestamp = (new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam')))->format('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    $findVote = $pdo->prepare('SELECT id FROM votes WHERE email = :email LIMIT 1');
    $findVote->execute(['email' => $email]);
    $existingVoteId = $findVote->fetchColumn();

    $status = 'ok';

    if ($existingVoteId) {
        $status = 'updated';
        $voteId = (int) $existingVoteId;

        $updateVote = $pdo->prepare('UPDATE votes SET name = :name, email = :email, updated_at = :updated_at WHERE id = :id');
        $updateVote->execute([
            'name' => $name,
            'email' => $email,
            'updated_at' => $currentTimestamp,
            'id' => $voteId,
        ]);

        $deleteSelections = $pdo->prepare('DELETE FROM vote_selections WHERE vote_id = :vote_id');
        $deleteSelections->execute(['vote_id' => $voteId]);
    } else {
        $insertVote = $pdo->prepare('INSERT INTO votes (name, email, created_at, updated_at) VALUES (:name, :email, :created_at, :updated_at)');
        $insertVote->execute([
            'name' => $name,
            'email' => $email,
            'created_at' => $currentTimestamp,
            'updated_at' => $currentTimestamp,
        ]);

        $voteId = (int) $pdo->lastInsertId();
    }

    $insertSelection = $pdo->prepare('INSERT INTO vote_selections (vote_id, track_id, track_title, track_artist) VALUES (:vote_id, :track_id, :track_title, :track_artist)');

    foreach ($selectedTracks as $track) {
        $insertSelection->execute([
            'vote_id' => $voteId,
            'track_id' => (string) $track['id'],
            'track_title' => $track['track'],
            'track_artist' => $track['artist'],
        ]);
    }

    $pdo->commit();

    $_SESSION['volume75_vote_throttle'] = [
        'window_start' => $currentWindowStart,
        'count' => $currentCount + 1,
        'last_vote_at' => $now,
    ];

    header('Location: /top75.php?status=' . $status);
    exit;
} catch (Throwable $exception) {
    if ($pdo instanceof \PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Vote error: ' . $exception->getMessage());
    header('Location: /top75.php?status=error');
    exit;
}
