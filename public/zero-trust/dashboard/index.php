<?php
declare(strict_types=1);
$title       = 'Dashboard';
$headerTitle = 'Dashboard';
$extraCss = ['dashboard.css'];
$footerLinks = [
  ['href' => '/datenschutz/',          'label' => 'Datenschutz'],
  ['href' => '/zero-trust/interessensabwaegung/', 'label' => 'Interessensabwägung'],
  ['href' => '/zero-trust/bib/',                  'label' => 'QR-Bibliothek'],
];
require __DIR__ . '/../../../includes/head.php';
require_once __DIR__ . '/../../../includes/qr-generator.php';
require_once __DIR__ . '/../../../includes/config.php';

$latestQr  = getLatestQr();
$inviteUrl = getConfig('discord_invite_url');
?>

<div class="dashboard-grid">

  <!-- Statistik Card -->
  <section class="card">
    <div class="card-header">
      <h2 class="card-title">Statistik</h2>
      <button class="btn" id="btn-compare" type="button">Vergleich</button>
    </div>
    <div class="card-body">

      <div class="stats-grid">
        <div class="stat-item">
          <span class="stat-value" id="stat-total">–</span>
          <span class="stat-label">Scans gesamt</span>
        </div>
        <div class="stat-item">
          <span class="stat-value" id="stat-period">–</span>
          <span class="stat-label" id="stat-period-label">Dieser Monat</span>
        </div>
        <div class="stat-item">
          <span class="stat-value" id="stat-today">–</span>
          <span class="stat-label">Heute</span>
        </div>
      </div>

      <div class="chart-controls">
        <div class="chart-nav">
          <button class="chart-nav-btn" id="chart-prev" type="button" aria-label="Zurück">&#8592;</button>
          <div class="period-dropdown-wrap">
            <button class="period-btn" id="period-main-btn" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
            <div class="period-drum" id="period-main-dd" role="listbox" aria-label="Zeitraum wählen"></div>
          </div>
          <div class="period-dropdown-wrap">
            <button class="period-btn" id="period-year-btn" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
            <div class="period-drum" id="period-year-dd" role="listbox" aria-label="Jahr wählen"></div>
          </div>
          <button class="chart-nav-btn" id="chart-next" type="button" aria-label="Vor">&#8594;</button>
          <button class="chart-nav-btn" id="chart-today" type="button" aria-label="Heute" disabled>Heute</button>
        </div>
        <div class="chart-toggle">
          <button class="chart-toggle-btn active" id="toggle-month" type="button">Monat</button>
          <button class="chart-toggle-btn"        id="toggle-week"  type="button">Woche</button>
        </div>
      </div>

      <div class="chart-wrap">
        <canvas id="stats-chart"></canvas>
      </div>

    </div>
  </section>

  <!-- Vergleichs-Chart -->
  <section class="card hidden" id="compare-card">
    <div class="card-body">
      <div class="chart-controls">
        <div class="chart-nav">
          <button class="chart-nav-btn" id="cmp-prev" type="button" aria-label="Zurück">&#8592;</button>
          <div class="period-dropdown-wrap">
            <button class="period-btn" id="cmp-main-btn" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
            <div class="period-drum" id="cmp-main-dd" role="listbox" aria-label="Zeitraum wählen"></div>
          </div>
          <div class="period-dropdown-wrap">
            <button class="period-btn" id="cmp-year-btn" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
            <div class="period-drum" id="cmp-year-dd" role="listbox" aria-label="Jahr wählen"></div>
          </div>
          <button class="chart-nav-btn" id="cmp-next" type="button" aria-label="Vor">&#8594;</button>
          <button class="chart-nav-btn" id="cmp-today" type="button" aria-label="Heute" disabled>Heute</button>
        </div>
        <div class="chart-toggle">
          <button class="chart-toggle-btn active" id="cmp-toggle-month" type="button">Monat</button>
          <button class="chart-toggle-btn"        id="cmp-toggle-week"  type="button">Woche</button>
        </div>
      </div>
      <div class="chart-wrap">
        <canvas id="cmp-chart"></canvas>
      </div>
    </div>
  </section>

  <!-- QR + URL nebeneinander -->
  <div class="dashboard-row">

    <!-- QR Card -->
    <section class="card">
      <div class="card-header">
        <h2 class="card-title">QR-Code</h2>
      </div>
      <div class="card-body">
        <?php if ($latestQr): ?>
          <div class="qr-row">
            <a href="/zero-trust/bib/" title="Zur QR-Bibliothek">
              <img
                src="<?= htmlspecialchars($latestQr['png'], ENT_QUOTES, 'UTF-8') ?>"
                alt="Aktueller QR-Code"
                class="qr-image qr-image-sm"
              >
            </a>
            <div class="btn-stack">
              <a href="<?= htmlspecialchars($latestQr['png'], ENT_QUOTES, 'UTF-8') ?>" download class="btn">Download PNG</a>
              <a href="<?= htmlspecialchars($latestQr['svg'], ENT_QUOTES, 'UTF-8') ?>" download class="btn">Download SVG</a>
              <button id="btn-generate" class="btn btn-primary" type="button">Neu generieren</button>
            </div>
          </div>
        <?php else: ?>
          <p class="text-muted">Noch kein QR-Code generiert.</p>
          <button id="btn-generate" class="btn btn-primary" type="button">QR-Code generieren</button>
        <?php endif; ?>
        <p class="qr-privacy-hint">Dieser QR-Code erfasst anonyme Scan-Statistiken. Datenschutz: qr.framenode.net/datenschutz</p>
      </div>
    </section>

    <!-- URL Card -->
    <section class="card">
      <div class="card-header">
        <h2 class="card-title">Discord Invite URL</h2>
      </div>
      <div class="card-body">
        <div class="url-form">
          <input type="url" id="url-input" class="url-input" placeholder="https://discord.gg/...">
          <button id="btn-save-url" class="btn btn-primary" type="button">Speichern</button>
        </div>
        <div class="hint-text">
          <?php if ($inviteUrl): ?>
            Aktuelle URL: <span class="text-soft"><?= htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') ?></span>
          <?php else: ?>
            <span class="text-italic">Noch keine URL gesetzt</span>
          <?php endif; ?>
        </div>
        <div id="url-status" class="url-status-bar"></div>
        <div class="url-check-row">
          <div class="url-check-status">
            <span class="url-check-dot unknown" id="url-dot"></span>
            <span id="url-check-label">Unbekannt</span>
          </div>
          <button class="btn" id="btn-check-url" type="button">Jetzt prüfen</button>
          <span class="url-check-time" id="url-check-time"></span>
        </div>
      </div>
    </section>

  </div>

</div>

<!-- QR Generierungs-Popout -->
<div id="qr-popout-backdrop" class="popout-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="popout-title">
  <div class="popout">

    <div class="popout-header">
      <h3 class="popout-title" id="popout-title">QR-Code generieren</h3>
      <button class="popout-close" id="popout-close" type="button" aria-label="Schließen">✕</button>
    </div>

    <div class="popout-step" id="step-1">
      <label class="popout-label">Hintergrundfarbe</label>
      <div class="color-row">
        <input type="color" class="color-picker" id="bg-picker" value="#FFFFFF">
        <input type="text"  class="color-hex"    id="bg-hex"    value="#FFFFFF" maxlength="7" placeholder="#FFFFFF">
      </div>
      <div class="popout-footer">
        <button class="btn btn-primary" id="step1-next" type="button">Weiter</button>
      </div>
    </div>

    <div class="popout-step hidden" id="step-2">
      <label class="popout-label">Vordergrundfarbe</label>
      <div class="color-row">
        <input type="color" class="color-picker" id="fg-picker" value="#000000">
        <input type="text"  class="color-hex"    id="fg-hex"    value="#000000" maxlength="7" placeholder="#000000">
      </div>
      <div class="popout-footer">
        <button class="btn" id="step2-back" type="button">Zurück</button>
        <button class="btn btn-primary" id="step2-next" type="button">Weiter</button>
      </div>
    </div>

    <div class="popout-step hidden" id="step-3">
      <label class="popout-label">Logo einbetten?</label>
      <div class="logo-toggle">
        <button class="btn active" id="logo-no"  type="button">Nein</button>
        <button class="btn"        id="logo-yes" type="button">Ja</button>
      </div>
      <div id="logo-upload-wrap" class="logo-upload-wrap">
        <input type="file" id="logo-file" accept="image/png,image/svg+xml">
        <p class="logo-zone-hint" style="margin-top:0.5rem;">Empfohlen: PNG, max. 500×500px</p>
      </div>
      <div class="popout-footer">
        <button class="btn" id="step3-back" type="button">Zurück</button>
        <button class="btn btn-primary" id="step3-next" type="button">Vorschau</button>
      </div>
    </div>

    <div class="popout-step hidden" id="step-3b-rembg">
      <label class="popout-label">Hintergrund entfernen?</label>
      <p class="logo-zone-hint">Das Logo wird mit KI analysiert und der Hintergrund automatisch entfernt. Dies kann einige Sekunden dauern.</p>
      <div class="popout-footer">
        <button class="btn" id="rembg-skip" type="button">Überspringen</button>
        <button class="btn btn-primary" id="rembg-apply" type="button">Hintergrund entfernen</button>
      </div>
    </div>

    <div class="popout-step hidden" id="step-3b">
      <label class="popout-label">Logo positionieren</label>
      <div class="logo-position-wrap">
        <div class="logo-canvas-wrap">
          <canvas id="logo-pos-canvas" width="280" height="280"></canvas>
        </div>
        <div class="logo-size-wrap">
          <div class="logo-size-label">
            <span>Logo-Größe</span>
            <span id="logo-size-val">40px</span>
          </div>
          <input type="range" id="logo-size-slider" class="logo-size-slider" min="20" max="80" value="40" step="1">
        </div>
        <p class="logo-zone-hint">Rote Bereiche sind gesperrt – dort darf das Logo nicht platziert werden.</p>
      </div>
      <div class="popout-footer">
        <button class="btn" id="step3b-back" type="button">Zurück</button>
        <button class="btn btn-primary" id="step3b-next" type="button">Weiter</button>
      </div>
    </div>

    <div class="popout-step hidden" id="step-4">
      <label class="popout-label">Vorschau</label>
      <div class="popout-preview">
        <div class="popout-spinner" id="preview-spinner">Wird generiert…</div>
        <img id="preview-img" src="" alt="QR Vorschau" style="display:none;">
      </div>
      <div class="popout-footer">
        <button class="btn" id="step4-back" type="button">Zurück</button>
        <button class="btn btn-primary" id="step4-save" type="button">Übernehmen</button>
      </div>
    </div>

  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous"></script>
<?php $dashJsVer = is_file(__DIR__ . '/../../../assets/js/dashboard.js') ? (string) filemtime(__DIR__ . '/../../../assets/js/dashboard.js') : (string) time(); ?>
<script src="/assets/js/dashboard.js?v=<?= htmlspecialchars($dashJsVer, ENT_QUOTES, 'UTF-8') ?>"></script>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
