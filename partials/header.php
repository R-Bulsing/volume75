<?php
  $pageTitle = $pageTitle ?? 'Volume 75 – Laat je zien';
  $pageDescription = $pageDescription ?? 'Volume 75 is het jubileumfeest van het GLR. Ontdek de geschiedenis, activiteiten, line-up en stem voor de ultieme Top 75.';
  $bodyClass = $bodyClass ?? '';
  $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
  $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
  $basePath = $basePath === '.' ? '' : $basePath;
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="<?= $basePath ?>/assets/images/volume75-logo.png" />
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css" />
    <script defer src="<?= $basePath ?>/assets/js/main.js"></script>
  </head>
  <body class="<?= htmlspecialchars($bodyClass) ?>">
  <?php $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
  <header class="site-header">
    <a href="<?= $basePath ?>/" class="logo-link">
      <div class="logo-stack">
        <img src="<?= $basePath ?>/assets/images/volume75-logo.png" alt="Volume 75 logo" class="logo-volume" />
        <span class="logo-divider" aria-hidden="true">×</span>
        <img src="<?= $basePath ?>/assets/images/glr75-logo.png" alt="75 jaar GLR jubileumlogo" class="logo-glr" />
      </div>
    </a>
    <button class="nav-toggle" type="button" aria-label="Toon navigatie" aria-expanded="false">
      <span class="nav-toggle__line"></span>
      <span class="nav-toggle__line"></span>
    </button>
    <nav class="site-nav" aria-label="Hoofdmenu">
      <ul>
        <li><a href="<?= $basePath ?>/index.php" <?= $currentPage === 'index.php' ? "aria-current='page'" : '' ?>>Volume 75</a></li>
        <li><a href="<?= $basePath ?>/history.php" <?= $currentPage === 'history.php' ? "aria-current='page'" : '' ?>>Geschiedenis</a></li>
        <li><a href="<?= $basePath ?>/activities.php" <?= $currentPage === 'activities.php' ? "aria-current='page'" : '' ?>>Activiteiten</a></li>
        <li><a href="<?= $basePath ?>/top75.php" <?= $currentPage === 'top75.php' ? "aria-current='page'" : '' ?>>Top 75</a></li>
      </ul>
    </nav>
  </header>
  <main class="site-main">
