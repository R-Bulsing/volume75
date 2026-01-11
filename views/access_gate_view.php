<?php
declare(strict_types=1);

$gatePageTitle = $gatePageTitle ?? 'Volume 75 – Binnenkort online';
$gateError = $gateError ?? null;
$gateViewData = $gateViewData ?? [];
$gateHeadline = (string)($gateViewData['headline'] ?? 'Binnenkort online');
$gateDescription = (string)($gateViewData['description'] ?? 'Volume 75 staat klaar om live te gaan. Heb je het wachtwoord? Vul het hieronder in en neem alvast een kijkje.');
$gateButtonLabel = (string)($gateViewData['buttonLabel'] ?? 'Ga verder');
$gateBadge = trim((string)($gateViewData['badge'] ?? ''));
$gateHelpTitle = trim((string)($gateViewData['helpTitle'] ?? ''));
$gateHelpText = trim((string)($gateViewData['helpText'] ?? ''));
$gateTips = $gateViewData['tips'] ?? [];
if (!is_array($gateTips)) {
  $gateTips = [];
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($gatePageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css" />
  </head>
  <body class="gate-page">
    <div class="gate-card" role="dialog" aria-labelledby="gate-title" aria-describedby="gate-description">
      <div class="gate-logo">
        <img src="<?= $basePath ?>/assets/images/glr75-logo.png" alt="Volume 75 logo" />
      </div>
      <?php if ($gateBadge !== ''): ?>
        <p class="gate-badge"><?= htmlspecialchars($gateBadge) ?></p>
      <?php endif; ?>
      <h1 id="gate-title" class="gate-title"><?= htmlspecialchars($gateHeadline) ?></h1>
      <p id="gate-description" class="gate-description">
        <?= htmlspecialchars($gateDescription) ?>
      </p>
      <?php if ($gateError): ?>
        <p class="gate-error" role="alert"><?= htmlspecialchars($gateError) ?></p>
      <?php endif; ?>
      <form class="gate-form" method="post" autocomplete="off">
        <label class="gate-label" for="access-password">Wachtwoord</label>
        <input
          type="password"
          name="access_password"
          id="access-password"
          placeholder="Voer wachtwoord in"
          required
          autofocus
        />
        <button type="submit"><?= htmlspecialchars($gateButtonLabel) ?></button>
      </form>
      <?php if ($gateHelpTitle !== '' || $gateHelpText !== '' || $gateTips !== []): ?>
        <div class="gate-help">
          <?php if ($gateHelpTitle !== ''): ?>
            <p class="gate-help__title"><?= htmlspecialchars($gateHelpTitle) ?></p>
          <?php endif; ?>
          <?php if ($gateHelpText !== ''): ?>
            <p class="gate-help__text"><?= htmlspecialchars($gateHelpText) ?></p>
          <?php endif; ?>
          <?php if ($gateTips !== []): ?>
            <ul class="gate-help__list">
              <?php foreach ($gateTips as $tip): ?>
                <?php
                  $cleanTip = trim((string)$tip);
                  if ($cleanTip === '') {
                    continue;
                  }
                ?>
                <li><?= htmlspecialchars($cleanTip) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <script defer src="<?= $basePath ?>/assets/js/main.js"></script>
  </body>
</html>
