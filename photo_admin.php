<?php
  $pageTitle = 'Foto beheer · Volume 75';
  $pageDescription = 'Beheer live fotoposts voor de Fotopagina. Upload nieuwe beelden en voeg optioneel een caption toe.';
  $bodyClass = 'page-photo-admin';

  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  require_once __DIR__ . '/config/access_gate.php';
  require_once __DIR__ . '/config/database.php';
  require_once __DIR__ . '/config/photo_repository.php';

  enforceAccessGate($pageTitle, [
    'gate' => [
      'headline' => 'Fotobeheer login',
      'description' => 'Alleen crew die live foto updates plaatst gebruikt dit wachtwoord. Houd het prive.',
      'buttonLabel' => 'Start fotobeheer',
      'badge' => 'Alleen admins',
      'helpTitle' => 'Wachtwoord kwijt?',
      'helpText' => 'Stuur een bericht in Teams naar GLR 75 Software Development.',
      'tips' => [
        'Gebruik een vertrouwde laptop en log uit zodra je klaar bent.',
        'Deel het wachtwoord nooit via mail of socials.',
      ],
    ],
  ]);

  $errors = [];
  $successMessage = null;
  $formData = [
    'caption' => '',
    'posted_at' => '',
    'photographer_name' => '',
  ];
  $recentPosts = [];

  try {
    $pdo = getDatabaseConnection();
    photoRepositoryEnsureSchema($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_POST['form_action'] ?? 'create';

      if ($action === 'create') {
        $formData = [
          'caption' => trim($_POST['caption'] ?? ''),
          'posted_at' => trim($_POST['posted_at'] ?? ''),
          'photographer_name' => trim($_POST['photographer_name'] ?? ''),
        ];

        $uploadResult = volume75HandlePhotoUpload($_FILES['photo'] ?? null, $errors, $pdo);

        if (empty($errors)) {
          $payload = [
            'slug' => $uploadResult['slug'],
            'caption' => $formData['caption'],
            'posted_at' => $formData['posted_at'] ?: null,
            'image_path' => $uploadResult['publicPath'],
            'photographer_name' => $formData['photographer_name'],
          ];

          $post = photoRepositoryCreatePost($pdo, $payload);
          $successMessage = 'Nieuwe fotopost geplaatst. Hij staat direct live op de Fotopagina.';
          $formData = [
            'caption' => '',
            'posted_at' => '',
            'photographer_name' => '',
          ];
        }
      } elseif ($action === 'update') {
        $slug = trim($_POST['post_slug'] ?? '');

        if ($slug === '') {
          $errors[] = 'Kon de fotopost niet bijwerken: geen ID ontvangen.';
        } else {
          $payload = [
            'caption' => trim($_POST['caption'] ?? ''),
            'posted_at' => trim($_POST['posted_at'] ?? ''),
            'photographer_name' => trim($_POST['photographer_name'] ?? ''),
          ];

          try {
            photoRepositoryUpdatePost($pdo, $slug, $payload);
            $successMessage = 'Fotopost bijgewerkt.';
          } catch (Throwable $exception) {
            $errors[] = 'Kon fotopost niet bijwerken: ' . $exception->getMessage();
          }
        }
      } elseif ($action === 'delete') {
        $slug = trim($_POST['post_slug'] ?? '');

        if ($slug === '') {
          $errors[] = 'Kon de fotopost niet verwijderen: geen ID ontvangen.';
        } else {
          try {
            if (photoRepositoryDeletePost($pdo, $slug)) {
              $successMessage = 'Fotopost verwijderd.';
            } else {
              $errors[] = 'Fotopost niet gevonden.';
            }
          } catch (Throwable $exception) {
            $errors[] = 'Kon fotopost niet verwijderen: ' . $exception->getMessage();
          }
        }
      }
    }

    $recentPosts = photoRepositoryFetchPosts($pdo, 6);
  } catch (Throwable $exception) {
    $errors[] = 'Database niet beschikbaar: ' . $exception->getMessage();
    $pdo = null;
  }

  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/photo_admin_view.php';
  include __DIR__ . '/partials/footer.php';

function volume75HandlePhotoUpload(?array $file, array &$errors, \PDO $pdo): array
{
  if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Upload een foto.';
    return [];
  }

  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    $errors[] = 'Upload mislukt (code ' . (int) $file['error'] . ').';
    return [];
  }

  $maxSize = 8 * 1024 * 1024;
  if (($file['size'] ?? 0) > $maxSize) {
    $errors[] = 'Bestand is te groot. Maximaal 8MB.';
    return [];
  }

  $allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'image/svg+xml' => 'svg',
  ];

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name'] ?? '') ?: '';
  if (!array_key_exists($mime, $allowedMime)) {
    $errors[] = 'Alleen JPG, PNG, WEBP, GIF of SVG wordt ondersteund.';
    return [];
  }

  $extension = $allowedMime[$mime];
  $slug = photoRepositoryGenerateSlug($pdo);
  $filename = $slug . '.' . $extension;

  try {
    $targetDirectory = photoRepositoryEnsureUploadsDirectory();
  } catch (Throwable $exception) {
    $errors[] = $exception->getMessage();
    return [];
  }

  $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
  if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $errors[] = 'Kon de foto niet opslaan op de server.';
    return [];
  }

  return [
    'slug' => $slug,
    'publicPath' => photoRepositoryUploadsPublicPath($filename),
  ];
}
?>
