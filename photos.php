<?php
  $pageTitle = 'Fotopagina · Volume 75';
  $pageDescription = 'Volg de live foto-updates vanuit de Maassilo.';
  $bodyClass = 'page-photos';

  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  require_once __DIR__ . '/config/database.php';
  require_once __DIR__ . '/config/photo_repository.php';

  $photoPosts = [];

  try {
    $pdo = getDatabaseConnection();
    photoRepositoryEnsureSchema($pdo);
    photoRepositorySeedDemoData($pdo);
    $photoPosts = photoRepositoryFetchPosts($pdo);
  } catch (Throwable $exception) {
    error_log('Fotopagina database fout: ' . $exception->getMessage());
    $photoPosts = volume75PhotoFallbackPosts();
  }

  if (!empty($photoPosts)) {
    usort($photoPosts, static function (array $a, array $b): int {
      $aTime = $a['datetime'] ?? '';
      $bTime = $b['datetime'] ?? '';
      return strcmp((string) $bTime, (string) $aTime);
    });
  }

  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/photos_view.php';
  include __DIR__ . '/partials/footer.php';
function volume75PhotoFallbackPosts(): array
{
  return [];
}

?>
