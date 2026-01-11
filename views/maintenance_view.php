<?php
declare(strict_types=1);

$maintenanceViewData = $maintenanceViewData ?? [];
$maintenanceTitle = (string)($maintenanceViewData['title'] ?? 'We zijn zo terug');
$maintenanceMessage = (string)($maintenanceViewData['message'] ?? 'Volume 75 ondergaat kort onderhoud. Probeer het zo weer opnieuw.');
$maintenanceError = trim((string)($maintenanceViewData['error'] ?? ''));
$maintenanceFormEnabled = (bool)($maintenanceViewData['formEnabled'] ?? false);

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($maintenanceTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css" />
  </head>
  <body class="gate-page">
    <div class="gate-card" role="alertdialog" aria-labelledby="maintenance-title" aria-describedby="maintenance-message">
      <div class="gate-logo">
        <img src="<?= $basePath ?>/assets/images/volume75-logo.png" alt="Volume 75 logo" />
      </div>
      <p class="gate-badge">Onderhoud</p>
      <h1 id="maintenance-title" class="gate-title"><?= htmlspecialchars($maintenanceTitle) ?></h1>
      <p id="maintenance-message" class="gate-description">
        <?= htmlspecialchars($maintenanceMessage) ?>
      </p>
      <?php if ($maintenanceError !== ''): ?>
        <p class="gate-error" role="alert"><?= htmlspecialchars($maintenanceError) ?></p>
      <?php endif; ?>
      <?php if ($maintenanceFormEnabled): ?>
        <form class="gate-form" method="post" autocomplete="off">
          <label class="gate-label" for="maintenance-password">Admin wachtwoord</label>
          <input
            type="password"
            name="maintenance_password"
            id="maintenance-password"
            placeholder="Voer wachtwoord in"
            required
          />
          <button type="submit">Log in</button>
        </form>
      <?php endif; ?>
    </div>
    <script defer src="<?= $basePath ?>/assets/js/main.js"></script>
  </body>
</html>
