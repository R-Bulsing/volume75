<?php
  $pageTitle = 'Volume 75 – Turn up the Volume';
  $pageDescription = 'Alle info over Volume 75: tijden, locatie, kosten en waarom je dit jubileumfeest van het GLR niet wilt missen.';
  $bodyClass = 'page-home';
  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/index_view.php';
  include __DIR__ . '/partials/footer.php';
?>
