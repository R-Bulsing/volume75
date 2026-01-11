<?php
  $pageTitle = 'Geschiedenis · Volume 75';
  $pageDescription = 'Duik in 75 jaar GLR: Highlights uit de geschiedenis richting Volume 75.';
  $bodyClass = 'page-history';
  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/history_view.php';
  include __DIR__ . '/partials/footer.php';
?>
