<?php
  $pageTitle = 'Volume 75 Top 75';
  $pageDescription = 'De stemming is gesloten. Bekijk hier de definitieve Volume 75 Top 75.';
  $bodyClass = 'page-top75';

  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  require_once __DIR__ . '/config/database.php';

  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $message = ['type' => 'info', 'text' => 'De stemming is gesloten. Bekijk hieronder de definitieve Top 75.'];

  $pdo = null;
  try {
    $pdo = getDatabaseConnection();
  } catch (Throwable $exception) {
    error_log('Database connection error: ' . $exception->getMessage());
  }

  $trackPool = [];
  if ($pdo instanceof \PDO) {
    try {
      $poolStatement = $pdo->query('SELECT id, track, artist FROM track_pool WHERE active = 1 ORDER BY track ASC');
      $trackPool = $poolStatement->fetchAll() ?: [];
    } catch (Throwable $exception) {
      error_log('Track pool query error: ' . $exception->getMessage());
      $trackPool = [];
    }
  }

  usort($trackPool, fn($a, $b) => strcasecmp($a['track'] ?? '', $b['track'] ?? ''));

  foreach ($trackPool as &$entry) {
    $entry['id'] = isset($entry['id']) ? (string) $entry['id'] : '';
    $entry['track'] = $entry['track'] ?? 'Onbekende track';
    $entry['artist'] = $entry['artist'] ?? 'Onbekende artiest';
  }
  unset($entry);

  $poolById = [];
  foreach ($trackPool as $entry) {
    $entryId = isset($entry['id']) ? (string) $entry['id'] : '';
    if ($entryId !== '') {
      $poolById[$entryId] = $entry;
    }
  }

  $topTracks = [];
  if ($pdo instanceof \PDO) {
    try {
      $statement = $pdo->query('SELECT track_id, MAX(track_title) AS track, MAX(track_artist) AS artist, COUNT(*) AS votes FROM vote_selections GROUP BY track_id ORDER BY votes DESC, track ASC');
      $topTracks = $statement->fetchAll() ?: [];
    } catch (Throwable $exception) {
      error_log('Top tracks query error: ' . $exception->getMessage());
      $topTracks = [];
    }
  }

  foreach ($topTracks as &$track) {
    $trackId = isset($track['track_id']) ? (string) $track['track_id'] : '';
    if ($trackId === '') {
      continue;
    }

    $poolEntry = $poolById[$trackId] ?? null;
    if ($poolEntry) {
      $track['track'] = $poolEntry['track'] ?? ($track['track'] ?? 'Onbekende track');
      $track['artist'] = $poolEntry['artist'] ?? ($track['artist'] ?? 'Onbekende artiest');
    } else {
      $track['track'] = $track['track'] ?? 'Onbekende track';
      $track['artist'] = $track['artist'] ?? 'Onbekende artiest';
    }

    $track['id'] = $trackId;
    $track['votes'] = (int) ($track['votes'] ?? 0);
  }
  unset($track);

  // Inject manual vote counts without touching the database
  $manualVotes = [
    'trk-027' => 32,
    'trk-239' => 27,
    'trk-195' => 25,
    'trk-191' => 24,
    'trk-016' => 16,
    'trk-149' => 18,
    'trk-099' => 22,
    'trk-245' => 28,
    'trk-210' => 12,
    'trk-247' => 28,
    'trk-164' => 8,
  ];

  $topTracksById = [];
  foreach ($topTracks as $track) {
    $trackId = $track['id'] ?? '';
    if ($trackId !== '') {
      $topTracksById[$trackId] = $track;
    }
  }

  foreach ($manualVotes as $manualId => $manualCount) {
    if ($manualCount <= 0) {
      continue;
    }

    if (isset($topTracksById[$manualId])) {
      $currentVotes = (int) ($topTracksById[$manualId]['votes'] ?? 0);
      $topTracksById[$manualId]['votes'] = $currentVotes + $manualCount;
    } elseif (isset($poolById[$manualId])) {
      $poolEntry = $poolById[$manualId];
      $topTracksById[$manualId] = [
        'id' => $manualId,
        'track_id' => $manualId,
        'track' => $poolEntry['track'] ?? 'Onbekende track',
        'artist' => $poolEntry['artist'] ?? 'Onbekende artiest',
        'votes' => $manualCount,
      ];
    }
  }

  $topTracks = array_values($topTracksById);
  usort($topTracks, function ($a, $b) {
    $votesA = (int) ($a['votes'] ?? 0);
    $votesB = (int) ($b['votes'] ?? 0);

    if ($votesA === $votesB) {
      return strcasecmp($a['track'] ?? '', $b['track'] ?? '');
    }

    return $votesB <=> $votesA;
  });

  if (!$topTracks && $trackPool) {
    $topTracks = $trackPool;
  }

  $top75 = array_slice($topTracks, 0, 75);

  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/top75_view.php';
  include __DIR__ . '/partials/footer.php';
