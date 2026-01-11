<?php
  $pageTitle = 'Activiteiten & Line-up · Volume 75';
  $pageDescription = 'Check de planning, activiteiten en line-up voor Volume 75. Van workshops tot afterparty.';
  $bodyClass = 'page-activities';
  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/activities_view.php';
  include __DIR__ . '/partials/footer.php';
?>
