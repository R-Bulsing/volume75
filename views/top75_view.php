<section class="section-intro">
  <h2>De stemming is gesloten</h2>
  <p>
    Bedankt voor het stemmen! De definitieve Volume 75 Top 75 staat hieronder. Tel af van 75 naar 1 en ontdek welke tracks we draaien tijdens het jubileumfeest.
  </p>
</section>

<?php if ($message): ?>
  <div class="alert alert--<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
<?php endif; ?>

<section class="top75-preview" data-tilt>
  <h3>Definitieve Volume 75 Top 75</h3>
  <?php if (!empty($top75)): ?>
    <ol class="top75-list">
      <?php foreach ($top75 as $index => $track): ?>
        <?php
          $position = $index + 1;
          $title = $track['track'] ?? 'Onbekende track';
          $artist = $track['artist'] ?? 'Onbekende artiest';
        ?>
        <li>
          <span class="top75-position">#<?= $position ?></span>
          <strong><?= htmlspecialchars($title) ?></strong>
          <span> – <?= htmlspecialchars($artist) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php else: ?>
    <p>De Top 75 is nog niet beschikbaar. Kom later terug voor de volledige lijst.</p>
  <?php endif; ?>
</section>
