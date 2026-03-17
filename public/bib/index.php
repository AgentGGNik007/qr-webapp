<?php
declare(strict_types=1);
$title       = 'QR-Bibliothek';
$headerTitle = 'QR-Bibliothek';
$footerLinks = [
  ['href' => '/dashboard/',            'label' => 'Dashboard'],
  ['href' => '/datenschutz/',          'label' => 'Datenschutz'],
  ['href' => '/interessensabwaegung/', 'label' => 'Interessensabwägung'],
];
require __DIR__ . '/../../includes/head.php';

require_once __DIR__ . '/../../includes/qr-generator.php';
$library = getQrLibrary();
?>

<section class="card">
  <div class="card-header">
    <h2 class="card-title">Gespeicherte QR-Codes</h2>
    <span class="card-subtitle"><?= count($library) ?> / 10</span>
  </div>

  <div class="card-body">
    <?php if (empty($library)): ?>
      <p style="color: var(--text-muted);">Noch keine QR-Codes generiert.</p>
    <?php else: ?>
      <div class="bib-grid">
        <?php foreach ($library as $i => $item): ?>
          <div class="bib-item card">
            <img
              src="<?= htmlspecialchars($item['png'], ENT_QUOTES, 'UTF-8') ?>"
              alt="QR-Code <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>"
              class="qr-image"
            >
            <div class="bib-meta">
              <?php if ($i === 0): ?>
                <span class="bib-badge">Aktuell</span>
              <?php endif; ?>
              <span class="bib-date"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="bib-actions">
              <a href="<?= htmlspecialchars($item['png'], ENT_QUOTES, 'UTF-8') ?>" download class="btn">PNG</a>
              <a href="<?= htmlspecialchars($item['svg'], ENT_QUOTES, 'UTF-8') ?>" download class="btn">SVG</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
