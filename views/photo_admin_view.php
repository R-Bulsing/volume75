<?php
  $currentPostedAt = $formData['posted_at'] ?: (new DateTimeImmutable('now', new DateTimeZone(PHOTO_REPOSITORY_TIMEZONE)))->format('Y-m-d\TH:i');
?>
<section class="section-intro">
  <h1>Fotopagina beheer</h1>
  <p>
    Upload verse beelden tijdens het event. Je post verschijnt direct op de openbare Fotopagina.
  </p>
</section>

<?php if (!empty($errors)): ?>
  <div class="alert alert--error">
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($successMessage): ?>
  <div class="alert alert--success">
    <?= htmlspecialchars($successMessage) ?>
  </div>
<?php endif; ?>

<section class="panel photo-manager">
  <form class="photo-manager__form" method="post" enctype="multipart/form-data">
    <div class="form-field form-field--full">
      <label for="caption">Caption</label>
      <textarea id="caption" name="caption" rows="3" placeholder="Beschrijf wat er gebeurt"><?= htmlspecialchars($formData['caption']) ?></textarea>
    </div>
    <div class="form-field">
      <label for="photographer_name">Fotograaf (optioneel)</label>
      <input type="text" id="photographer_name" name="photographer_name" value="<?= htmlspecialchars($formData['photographer_name']) ?>" />
      <small>Vul voor- en achternaam in als je de fotograaf zichtbaar wilt crediten.</small>
    </div>
    <div class="form-field">
      <label for="posted_at">Tijdstip (optioneel)</label>
      <input type="datetime-local" id="posted_at" name="posted_at" value="<?= htmlspecialchars($currentPostedAt) ?>" />
      <small>Laat leeg om het huidige moment te gebruiken.</small>
    </div>
    <div class="form-field form-field--full">
      <label for="photo">Foto upload *</label>
      <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" required />
      <small>Maximaal 8MB. Gebruik liggende beelden (4:5 of 1:1).</small>
    </div>
    <div class="form-actions">
      <button type="submit" name="form_action" value="create" class="cta-button">Publiceer foto</button>
    </div>
  </form>
</section>

<section class="panel photo-manager__recent">
  <header>
    <h2>Laatste uploads</h2>
    <p>Handig om te checken of je post live staat.</p>
  </header>
  <?php if (!empty($recentPosts)): ?>
    <div class="photo-manager__recent-grid">
      <?php foreach ($recentPosts as $index => $post):
        $captionId = 'recent-caption-' . $index;
        $photographerId = 'recent-photographer-' . $index;
        $postedAtId = 'recent-posted-at-' . $index;
      ?>
        <article class="photo-manager__recent-card">
          <div class="photo-manager__recent-media">
            <img src="<?= htmlspecialchars(($basePath ?? '') . '/' . ltrim($post['image'], '/')) ?>" alt="Live foto Volume 75" />
          </div>
          <form class="photo-manager__recent-form" method="post">
            <input type="hidden" name="post_slug" value="<?= htmlspecialchars($post['id']) ?>" />
            <div class="form-field">
              <label for="<?= htmlspecialchars($captionId) ?>">Caption</label>
              <textarea id="<?= htmlspecialchars($captionId) ?>" name="caption" rows="3"><?= htmlspecialchars($post['caption'] ?? '') ?></textarea>
            </div>
            <div class="form-field">
              <label for="<?= htmlspecialchars($photographerId) ?>">Fotograaf (optioneel)</label>
              <input type="text" id="<?= htmlspecialchars($photographerId) ?>" name="photographer_name" value="<?= htmlspecialchars($post['photographer'] ?? '') ?>" />
              <small>Wordt getoond bij de foto op de openbare pagina.</small>
            </div>
            <div class="form-field">
              <label for="<?= htmlspecialchars($postedAtId) ?>">Tijdstip</label>
              <input type="datetime-local" id="<?= htmlspecialchars($postedAtId) ?>" name="posted_at" value="<?= htmlspecialchars($post['posted_at_form'] ?? '') ?>" />
              <small>Laat leeg om het huidige tijdstip te behouden.</small>
            </div>
            <p class="photo-manager__recent-time">Live sinds <?= htmlspecialchars($post['timestamp']) ?></p>
            <div class="photo-manager__recent-actions">
              <button type="submit" name="form_action" value="update" class="cta-button">Wijzigingen opslaan</button>
              <button type="submit" name="form_action" value="delete" class="cta-button cta-button--danger" onclick="return confirm('Weet je zeker dat je deze fotopost wilt verwijderen?');">Verwijder post</button>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>Er zijn nog geen posts opgeslagen.</p>
  <?php endif; ?>
</section>
