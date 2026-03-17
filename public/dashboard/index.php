<?php
declare(strict_types=1);
$title       = 'Dashboard';
$headerTitle = 'Dashboard';
$footerLinks = [
  ['href' => '/datenschutz/',          'label' => 'Datenschutz'],
  ['href' => '/interessensabwaegung/', 'label' => 'Interessensabwägung'],
  ['href' => '/bib/',                  'label' => 'QR-Bibliothek'],
];
require __DIR__ . '/../../includes/head.php';
require_once __DIR__ . '/../../includes/qr-generator.php';
require_once __DIR__ . '/../../includes/config.php';

$latestQr  = getLatestQr();
$inviteUrl = getConfig('discord_invite_url');
?>

<div class="dashboard-grid">

  <!-- Statistik Card -->
  <section class="card">
    <div class="card-header">
      <h2 class="card-title">Statistik</h2>
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
          <span class="chart-period" id="chart-period-label"></span>
          <button class="chart-nav-btn" id="chart-next" type="button" aria-label="Vor">&#8594;</button>
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
            <a href="/bib/" title="Zur QR-Bibliothek">
              <img
                src="<?= htmlspecialchars($latestQr['png'], ENT_QUOTES, 'UTF-8') ?>"
                alt="Aktueller QR-Code"
                class="qr-image"
                style="max-width:140px;"
              >
            </a>
            <div style="display:flex; flex-direction:column; gap:0.5rem; flex:1;">
              <a href="<?= htmlspecialchars($latestQr['png'], ENT_QUOTES, 'UTF-8') ?>" download class="btn">Download PNG</a>
              <a href="<?= htmlspecialchars($latestQr['svg'], ENT_QUOTES, 'UTF-8') ?>" download class="btn">Download SVG</a>
              <button id="btn-generate" class="btn btn-primary" type="button">Neu generieren</button>
            </div>
          </div>
        <?php else: ?>
          <p style="color:var(--text-muted); margin-bottom:0.75rem;">Noch kein QR-Code generiert.</p>
          <button id="btn-generate" class="btn btn-primary" type="button">QR-Code generieren</button>
        <?php endif; ?>
      </div>
    </section>

    <!-- URL Card -->
    <section class="card">
      <div class="card-header">
        <h2 class="card-title">Discord Invite URL</h2>
      </div>
      <div class="card-body" style="gap:0.5rem;">

        <?php if ($inviteUrl): ?>
          <div class="url-pill" style="font-size:0.85rem;">
            <?= htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php else: ?>
          <p style="color:var(--text-muted); font-size:0.85rem; margin:0;">Noch keine URL gesetzt.</p>
        <?php endif; ?>

        <div class="url-form">
          <input
            type="url"
            id="url-input"
            class="url-input"
            placeholder="https://discord.gg/..."
            value="<?= htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') ?>"
          >
          <button id="btn-save-url" class="btn btn-primary" type="button">Speichern</button>
        </div>
        <p class="url-status" id="url-status" style="margin:0; min-height:1.2em;"></p>

        <div class="url-check-row" style="margin-top:0; padding-top:0.5rem;">
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
      <div id="logo-upload-wrap" style="display:none; margin-top:0.5rem;">
        <input type="file" id="logo-file" accept="image/png,image/jpeg,image/gif,image/webp">
      </div>
      <div class="popout-footer">
        <button class="btn" id="step3-back" type="button">Zurück</button>
        <button class="btn btn-primary" id="step3-next" type="button">Vorschau</button>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(() => {
  "use strict";

  const MONTHS_DE = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
  let chartMode   = 'month';
  let chartOffset = 0;
  let chartInst   = null;

  function getChartColor() {
    return getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#93C5FD';
  }

  function generatePlaceholderData(labels) {
    return labels.map(() => Math.floor(Math.random() * 30));
  }

  function getPeriodLabels() {
    const now = new Date();
    if (chartMode === 'month') {
      const d    = new Date(now.getFullYear(), now.getMonth() + chartOffset + 1, 0);
      const days = d.getDate();
      return Array.from({ length: days }, (_, i) => String(i + 1));
    } else {
      const d = new Date(now);
      d.setDate(d.getDate() + chartOffset * 7);
      const labels = [];
      for (let i = 6; i >= 0; i--) {
        const day = new Date(d);
        day.setDate(d.getDate() - i);
        labels.push(day.getDate() + '.' + (day.getMonth() + 1) + '.');
      }
      return labels;
    }
  }

  function getPeriodTitle() {
    const now = new Date();
    if (chartMode === 'month') {
      const d = new Date(now.getFullYear(), now.getMonth() + chartOffset, 1);
      return MONTHS_DE[d.getMonth()] + ' ' + d.getFullYear();
    } else {
      const d     = new Date(now);
      d.setDate(d.getDate() + chartOffset * 7);
      const end   = new Date(d);
      const start = new Date(d);
      start.setDate(d.getDate() - 6);
      return start.getDate() + '.' + (start.getMonth()+1) + '. – ' + end.getDate() + '.' + (end.getMonth()+1) + '.';
    }
  }

  function renderChart() {
    const labels = getPeriodLabels();
    const data   = generatePlaceholderData(labels);
    const color  = getChartColor();

    document.getElementById('chart-period-label').textContent = getPeriodTitle();
    document.getElementById('chart-next').disabled = chartOffset >= 0;
    const minOffset = chartMode === 'month' ? -23 : -103;
    document.getElementById('chart-prev').disabled = chartOffset <= minOffset;

    const ctx = document.getElementById('stats-chart').getContext('2d');
    if (chartInst) chartInst.destroy();

    chartInst = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data,
          borderColor: color,
          backgroundColor: color + '22',
          pointBackgroundColor: color,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            grid:  { color: 'rgba(128,128,128,0.1)' },
            ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() },
          },
          y: {
            beginAtZero: true,
            grid:  { color: 'rgba(128,128,128,0.1)' },
            ticks: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim(),
              stepSize: 1,
            },
          }
        }
      }
    });
  }

  document.getElementById('chart-prev').addEventListener('click', () => { chartOffset--; renderChart(); });
  document.getElementById('chart-next').addEventListener('click', () => { chartOffset++; renderChart(); });

  document.getElementById('toggle-month').addEventListener('click', () => {
    chartMode = 'month'; chartOffset = 0;
    document.getElementById('toggle-month').classList.add('active');
    document.getElementById('toggle-week').classList.remove('active');
    document.getElementById('stat-period-label').textContent = 'Dieser Monat';
    renderChart();
  });

  document.getElementById('toggle-week').addEventListener('click', () => {
    chartMode = 'week'; chartOffset = 0;
    document.getElementById('toggle-week').classList.add('active');
    document.getElementById('toggle-month').classList.remove('active');
    document.getElementById('stat-period-label').textContent = 'Diese Woche';
    renderChart();
  });

  renderChart();

  // ── URL Card ──────────────────────────────────────────
  const urlInput      = document.getElementById('url-input');
  const btnSaveUrl    = document.getElementById('btn-save-url');
  const urlStatus     = document.getElementById('url-status');
  const urlDot        = document.getElementById('url-dot');
  const urlCheckLabel = document.getElementById('url-check-label');
  const urlCheckTime  = document.getElementById('url-check-time');
  const btnCheckUrl   = document.getElementById('btn-check-url');

  function setUrlCheckStatus(reachable, time) {
    urlDot.className          = 'url-check-dot ' + (reachable ? 'ok' : 'error');
    urlCheckLabel.textContent = reachable ? 'Erreichbar' : 'Nicht erreichbar';
    urlCheckLabel.style.color = reachable ? 'var(--status-success)' : 'var(--status-error)';
    if (time) urlCheckTime.textContent = 'Zuletzt geprüft: ' + time;
  }

  async function checkUrl() {
    btnCheckUrl.textContent   = 'Prüfe…';
    btnCheckUrl.disabled      = true;
    urlDot.className          = 'url-check-dot unknown';
    urlCheckLabel.textContent = 'Wird geprüft…';
    urlCheckLabel.style.color = 'var(--text-muted)';
    try {
      const res  = await fetch('/api/invite-url.php');
      const data = await res.json();
      const now  = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
      setUrlCheckStatus(data.reachable, now);
    } catch (_) {
      urlCheckLabel.textContent = 'Fehler';
      urlCheckLabel.style.color = 'var(--status-error)';
    }
    btnCheckUrl.textContent = 'Jetzt prüfen';
    btnCheckUrl.disabled    = false;
  }

  btnCheckUrl.addEventListener('click', checkUrl);

  btnSaveUrl.addEventListener('click', async () => {
    const url = urlInput.value.trim();
    urlStatus.textContent = 'Wird geprüft…';
    urlStatus.style.color = 'var(--text-muted)';
    try {
      const res  = await fetch('/api/invite-url.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url }),
      });
      const data = await res.json();
      if (data.success) {
        urlStatus.textContent = '✓ URL gespeichert';
        urlStatus.style.color = 'var(--status-success)';
        const pill = document.querySelector('.url-pill');
        if (pill) pill.textContent = url;
        checkUrl();
      } else {
        urlStatus.textContent = '✗ ' + (data.error ?? 'Fehler');
        urlStatus.style.color = 'var(--status-error)';
      }
    } catch (_) {
      urlStatus.textContent = '✗ Verbindungsfehler';
      urlStatus.style.color = 'var(--status-error)';
    }
  });

  checkUrl();

  // ── Popout ────────────────────────────────────────────
  const backdrop = document.getElementById('qr-popout-backdrop');
  const btnGen   = document.getElementById('btn-generate');
  const btnClose = document.getElementById('popout-close');
  const steps    = [1,2,3,4].map(n => document.getElementById('step-' + n));
  let logoTmpPath = null;

  function showStep(n) {
    steps.forEach((s, i) => s.classList.toggle('hidden', i !== n - 1));
  }

  function openPopout() {
    backdrop.classList.remove('hidden');
    showStep(1);
    logoTmpPath = null;
    document.getElementById('logo-no').classList.add('active');
    document.getElementById('logo-yes').classList.remove('active');
    document.getElementById('logo-upload-wrap').style.display = 'none';
    document.getElementById('logo-file').value = '';
  }

  function closePopout() { backdrop.classList.add('hidden'); }

  btnGen.addEventListener('click', openPopout);
  btnClose.addEventListener('click', closePopout);
  backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closePopout(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePopout(); });

  const bgPicker = document.getElementById('bg-picker');
  const bgHex    = document.getElementById('bg-hex');
  bgPicker.addEventListener('input', () => { bgHex.value = bgPicker.value.toUpperCase(); });
  bgHex.addEventListener('input',   () => { if (/^#[0-9A-Fa-f]{6}$/.test(bgHex.value)) bgPicker.value = bgHex.value; });
  document.getElementById('step1-next').addEventListener('click', () => showStep(2));

  const fgPicker = document.getElementById('fg-picker');
  const fgHex    = document.getElementById('fg-hex');
  fgPicker.addEventListener('input', () => { fgHex.value = fgPicker.value.toUpperCase(); });
  fgHex.addEventListener('input',   () => { if (/^#[0-9A-Fa-f]{6}$/.test(fgHex.value)) fgPicker.value = fgHex.value; });
  document.getElementById('step2-back').addEventListener('click', () => showStep(1));
  document.getElementById('step2-next').addEventListener('click', () => showStep(3));

  const logoNo         = document.getElementById('logo-no');
  const logoYes        = document.getElementById('logo-yes');
  const logoUploadWrap = document.getElementById('logo-upload-wrap');
  const logoFile       = document.getElementById('logo-file');

  logoNo.addEventListener('click', () => {
    logoNo.classList.add('active');
    logoYes.classList.remove('active');
    logoUploadWrap.style.display = 'none';
    logoTmpPath = null;
  });

  logoYes.addEventListener('click', () => {
    logoYes.classList.add('active');
    logoNo.classList.remove('active');
    logoUploadWrap.style.display = 'block';
  });

  document.getElementById('step3-back').addEventListener('click', () => showStep(2));

  document.getElementById('step3-next').addEventListener('click', async () => {
    if (logoYes.classList.contains('active') && logoFile.files.length > 0) {
      const formData = new FormData();
      formData.append('logo', logoFile.files[0]);
      try {
        const res  = await fetch('/api/logo-upload.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          logoTmpPath = data.tmp_path;
        } else {
          alert(data.error ?? 'Logo-Upload fehlgeschlagen');
          return;
        }
      } catch (_) {
        alert('Verbindungsfehler beim Logo-Upload');
        return;
      }
    }
    showStep(4);
    loadPreview();
  });

  const previewSpinner = document.getElementById('preview-spinner');
  const previewImg     = document.getElementById('preview-img');

  async function loadPreview() {
    previewSpinner.style.display = 'flex';
    previewImg.style.display     = 'none';
    try {
      const res  = await fetch('/api/qr-preview.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fg: fgHex.value, bg: bgHex.value, logo_tmp: logoTmpPath }),
      });
      const data = await res.json();
      if (data.preview) {
        previewImg.src               = data.preview;
        previewImg.style.display     = 'block';
        previewSpinner.style.display = 'none';
      }
    } catch (_) {
      previewSpinner.textContent = 'Fehler beim Laden der Vorschau';
    }
  }

  document.getElementById('step4-back').addEventListener('click', () => showStep(3));

  document.getElementById('step4-save').addEventListener('click', async () => {
    const btn = document.getElementById('step4-save');
    btn.textContent = 'Wird gespeichert…';
    btn.disabled    = true;
    try {
      const res  = await fetch('/api/qr-save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fg: fgHex.value, bg: bgHex.value, logo_tmp: logoTmpPath }),
      });
      const data = await res.json();
      if (data.success) {
        closePopout();
        window.location.reload();
      } else {
        alert(data.error ?? 'Fehler beim Speichern');
        btn.textContent = 'Übernehmen';
        btn.disabled    = false;
      }
    } catch (_) {
      alert('Verbindungsfehler');
      btn.textContent = 'Übernehmen';
      btn.disabled    = false;
    }
  });

})();
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
