<?php
  http_response_code(404);
  $pageTitle = 'Pagina niet gevonden';
  $pageDescription = 'De pagina die je zoekt bestaat niet (meer). Keer terug naar Volume 75 en ontdek ons jubileum.';
  $bodyClass = 'page-404';

  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();
  include __DIR__ . '/partials/header.php';
  include __DIR__ . '/views/404_view.php';
  include __DIR__ . '/partials/footer.php';
