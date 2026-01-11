<?php
  $logoPath = ($basePath ?? '') . '/assets/images/volume75-logo.png';
?>
<section class="section-intro photo-intro">
  <h1>Live Fotopagina</h1>
  <p>
    Vanuit het fototeam wordt er de hele avond gepost om de sfeer vast te leggen. Bekijk hier de laatste beelden!
  </p>
</section>

<section class="photo-feed">
  <?php if (!empty($photoPosts)): ?>
    <?php foreach ($photoPosts as $post):
      $imagePath = ($basePath ?? '') . '/' . ltrim($post['image'] ?? '', '/');
      $photographer = trim($post['photographer'] ?? '');
    ?>
    <article class="photo-card">
      <header class="photo-card__header">
        <img src="<?= $logoPath ?>" alt="Volume 75 logo" class="photo-card__avatar" />
        <?php $datetime = $post['datetime'] ?? ''; ?>
        <div class="photo-card__meta">
          <?php if ($photographer !== ''): ?>
            <p class="photo-card__photographer">Door <?= htmlspecialchars($photographer) ?></p>
          <?php endif; ?>
          <time class="photo-card__timestamp" <?= $datetime ? 'datetime="' . htmlspecialchars($datetime) . '"' : '' ?>>
            <?= htmlspecialchars($post['timestamp']) ?>
          </time>
        </div>
      </header>
      <figure class="photo-card__media">
        <img src="<?= htmlspecialchars($imagePath) ?>" alt="Live foto Volume 75" loading="lazy" />
      </figure>
      <?php if (!empty($post['caption'])): ?>
        <p class="photo-card__caption"><?= htmlspecialchars($post['caption']) ?></p>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="photo-feed__empty">Geen fotoposts gevonden.</p>
  <?php endif; ?>
</section>
