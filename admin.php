<?php
  $pageTitle = 'Admin · Volume 75';
  $pageDescription = 'Beheer voorvertoning van Volume 75 pagina\'s.';
  $bodyClass = 'page-admin';

  require_once __DIR__ . '/config/access_gate.php';
  require_once __DIR__ . '/config/maintenance.php';
  require_once __DIR__ . '/config/database.php';
  volume75EnforceMaintenanceMode();
  enforceAccessGate($pageTitle, [
    'gate' => [
      'headline' => 'Admin toegang',
      'description' => 'Alleen crew met het Volume 75 wachtwoord krijgt toegang tot deze adminpagina.',
      'buttonLabel' => 'Ga verder',
      'badge' => 'Afgeschermd',
      'helpTitle' => 'Wachtwoord kwijt?',
      'helpText' => 'Vraag het na bij het Volume 75 developer team.',
    ],
  ]);

  $restrictedPages = [
    ['path' => '/photo_admin.php', 'label' => 'Fotobeheer'],
  ];

  $voteError = null;
  $fullTrackList = [];

  try {
    $pdo = getDatabaseConnection();

    // Haal alle tracks op en zet ze met 0 stemmen klaar
    $poolStatement = $pdo->query('SELECT id, track, artist FROM track_pool WHERE active = 1 ORDER BY track ASC');
    $trackPool = $poolStatement->fetchAll() ?: [];

    $fullTrackList = array_map(static function ($entry) {
      return [
        'id' => isset($entry['id']) ? (string) $entry['id'] : '',
        'track' => $entry['track'] ?? 'Onbekende track',
        'artist' => $entry['artist'] ?? 'Onbekende artiest',
        'votes' => 0,
      ];
    }, $trackPool);

    $fullById = [];
    foreach ($fullTrackList as $track) {
      $id = $track['id'] ?? '';
      if ($id !== '') {
        $fullById[$id] = $track;
      }
    }

    // Telling uit de database
    $statement = $pdo->query('SELECT track_id, MAX(track_title) AS track, MAX(track_artist) AS artist, COUNT(*) AS votes FROM vote_selections GROUP BY track_id');
    $dbTracks = $statement->fetchAll() ?: [];

    foreach ($dbTracks as $track) {
      $id = isset($track['track_id']) ? (string) $track['track_id'] : '';
      if ($id === '') {
        continue;
      }

      $votes = (int) ($track['votes'] ?? 0);
      $title = $track['track'] ?? 'Onbekende track';
      $artist = $track['artist'] ?? 'Onbekende artiest';

      if (isset($fullById[$id])) {
        $fullById[$id]['votes'] = $votes;
        // Titel/artist uit pool houden leidend, behalve als ze leeg zijn.
        if ($fullById[$id]['track'] === 'Onbekende track') {
          $fullById[$id]['track'] = $title;
        }
        if ($fullById[$id]['artist'] === 'Onbekende artiest') {
          $fullById[$id]['artist'] = $artist;
        }
      } else {
        $fullById[$id] = [
          'id' => $id,
          'track' => $title,
          'artist' => $artist,
          'votes' => $votes,
        ];
      }
    }

    // Eventuele handmatige telling meenemen zodat het gelijkloopt met de Top 75 weergave
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

    foreach ($manualVotes as $manualId => $manualCount) {
      if ($manualCount <= 0) {
        continue;
      }

      if (isset($fullById[$manualId])) {
        $fullById[$manualId]['votes'] += $manualCount;
      }
    }

    $fullTrackList = array_values($fullById);

    usort($fullTrackList, static function ($a, $b) {
      $votesA = (int) ($a['votes'] ?? 0);
      $votesB = (int) ($b['votes'] ?? 0);

      if ($votesA === $votesB) {
        return strcasecmp($a['track'] ?? '', $b['track'] ?? '');
      }

      return $votesB <=> $votesA;
    });
  } catch (Throwable $exception) {
    error_log('Admin vote export error: ' . $exception->getMessage());
    $voteError = 'Kon stemmen niet ophalen. Controleer de databaseverbinding.';
  }

  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/admin_view.php';
  include __DIR__ . '/partials/footer.php';
?>
