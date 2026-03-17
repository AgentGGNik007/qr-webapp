<?php
declare(strict_types=1);
$title       = 'Dashboard';
$headerTitle = 'Dashboard';
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
      <div class="card-body" style="gap:0.75rem;">
        <div class="url-form">
          <input type="url" id="url-input" class="url-input" placeholder="https://discord.gg/...">
          <button id="btn-save-url" class="btn btn-primary" type="button">Speichern</button>
        </div>
        <div style="font-size:0.8rem; color:var(--text-muted); text-align:center;">
          <?php if ($inviteUrl): ?>
            Aktuelle URL: <span style="color:var(--text-soft);"><?= htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') ?></span>
          <?php else: ?>
            <span style="font-style:italic;">Noch keine URL gesetzt</span>
          <?php endif; ?>
        </div>
        <div id="url-status" style="text-align:center; min-height:1.5rem; display:flex; align-items:center; justify-content:center; gap:0.4rem; font-size:0.9rem;"></div>
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

  // ── Chart ─────────────────────────────────────────────
  let chartMode   = 'month';
  let chartOffset = 0;
  let chartInst   = null;
  let allPeriods  = [];

  function getChartColor() {
    return getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#93C5FD';
  }

  function drawChart(labels, values) {
    const color = getChartColor();
    const ctx   = document.getElementById('stats-chart').getContext('2d');
    if (chartInst) chartInst.destroy();
    chartInst = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data: values,
          borderColor: color,
          backgroundColor: color + '22',
          pointBackgroundColor: color,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          spanGaps: false,
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

  async function renderChart() {
    document.getElementById('chart-next').disabled = chartOffset >= 0;
    updateTodayBtn();
    const minOffset = chartMode === 'month'
      ? (window._minMonthOffset ?? 0)
      : (window._minWeekOffset  ?? 0);
    document.getElementById('chart-prev').disabled = chartOffset <= minOffset;

    try {
      const res  = await fetch('/api/stats.php?mode=' + chartMode + '&offset=' + chartOffset);
      const data = await res.json();

      document.getElementById('stat-total').textContent  = data.total  ?? '–';
      document.getElementById('stat-period').textContent = data.period ?? '–';
      document.getElementById('stat-today').textContent  = data.today  ?? '–';

      if (data.min_week_offset  !== undefined) window._minWeekOffset  = data.min_week_offset;
      if (data.min_month_offset !== undefined) window._minMonthOffset = data.min_month_offset;
      if (data.periods) buildDropdowns(data.periods);

      drawChart(data.labels, data.values);
    } catch (_) {
      drawChart([], []);
    }
  }

  const todayBtn = document.getElementById('chart-today');

  function updateTodayBtn() {
    todayBtn.disabled = chartOffset >= 0;
  }

  todayBtn.addEventListener('click', () => {
    chartOffset = 0;
    renderChart();
  });

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

  // ── Period Drum Roll ──────────────────────────────────
  const mainBtn = document.getElementById('period-main-btn');
  const mainDD  = document.getElementById('period-main-dd');
  const yearBtn = document.getElementById('period-year-btn');
  const yearDD  = document.getElementById('period-year-dd');

  let selectedMain = null;
  let selectedYear = null;

  function buildDrum(container, items, activeVal, onCommit) {
    container.innerHTML = '';

    const inner    = document.createElement('div');
    inner.className = 'period-drum-inner';

    const selector = document.createElement('div');
    selector.className = 'period-drum-selector';
    inner.appendChild(selector);

    const list = document.createElement('div');
    list.className = 'period-drum-list';

    items.forEach(item => {
      const el = document.createElement('div');
      el.className    = 'period-drum-item' + (String(item) === String(activeVal) ? ' is-active' : '');
      el.textContent  = String(item);
      el.dataset.value = String(item);
      list.appendChild(el);
    });

    inner.appendChild(list);
    container.appendChild(inner);

    // Scroll-Snap → aktives Element markieren
    let scrollTimer;
    list.addEventListener('scroll', () => {
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(() => {
        const idx = Math.round(list.scrollTop / 40);
        list.querySelectorAll('.period-drum-item').forEach((el, i) => {
          el.classList.toggle('is-active', i === idx);
        });
      }, 80);
    });

    // Klick auf ein Item scrollt dazu
    list.addEventListener('click', (e) => {
      const item = e.target.closest('.period-drum-item');
      if (!item) return;
      const items = [...list.querySelectorAll('.period-drum-item')];
      const idx   = items.indexOf(item);
      list.scrollTo({ top: idx * 40, behavior: 'smooth' });
    });

    // Pfeiltasten
    list.setAttribute('tabindex', '0');
    list.addEventListener('keydown', (e) => {
      const items = [...list.querySelectorAll('.period-drum-item')];
      const idx   = Math.round(list.scrollTop / 40);
      if (e.key === 'ArrowDown') { e.preventDefault(); list.scrollTo({ top: (idx + 1) * 40, behavior: 'smooth' }); }
      if (e.key === 'ArrowUp')   { e.preventDefault(); list.scrollTo({ top: (idx - 1) * 40, behavior: 'smooth' }); }
      if (e.key === 'Enter')     { e.preventDefault(); onCommit(items[idx]?.dataset.value); closeAll(); }
      if (e.key === 'Escape')    { closeAll(); }
    });

    // Scroll aktiven Wert
    const activeIdx = items.findIndex(i => String(i) === String(activeVal));
    if (activeIdx >= 0) setTimeout(() => list.scrollTo({ top: activeIdx * 40 }), 30);

    return list;
  }

  function getDrumValue(dd) {
    const list = dd.querySelector('.period-drum-list');
    if (!list) return null;
    const idx  = Math.round(list.scrollTop / 40);
    return list.querySelectorAll('.period-drum-item')[idx]?.dataset.value ?? null;
  }

  function buildDropdowns(periods) {
    allPeriods = periods;
    const current = periods.find(p => p.offset === chartOffset) ?? periods[0];

    const mainValues = [...new Map(periods.map(p => {
      const key = chartMode === 'month' ? p.month : p.kw_label;
      return [key, key];
    })).values()];

    const yearValues = [...new Map(periods.map(p => [p.year, p.year])).values()];

    selectedMain = chartMode === 'month' ? current.month : current.kw_label;
    selectedYear = current.year;

    mainBtn.textContent = selectedMain;
    yearBtn.textContent = String(selectedYear);

    buildDrum(mainDD, mainValues, selectedMain, (val) => {
      if (val) { selectedMain = val; mainBtn.textContent = val; applySelection(); }
    });

    buildDrum(yearDD, yearValues, selectedYear, (val) => {
      if (val) { selectedYear = Number(val); yearBtn.textContent = val; applySelection(); }
    });
  }

  function applySelection() {
    let found;
    if (chartMode === 'month') {
      found = allPeriods.find(p => p.month === selectedMain && p.year === selectedYear);
    } else {
      found = allPeriods.find(p => p.kw_label === selectedMain && p.year === selectedYear);
    }
    if (found) { chartOffset = found.offset; renderChart(); }
  }

  function commitAndClose(dd, btn, isYear) {
    const val = getDrumValue(dd);
    if (val) {
      if (isYear) { selectedYear = Number(val); btn.textContent = val; }
      else        { selectedMain = val;          btn.textContent = val; }
      applySelection();
    }
    closeAll();
  }

  function openDrum(btn, dd) {
    closeAll();
    dd.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    setTimeout(() => dd.querySelector('.period-drum-list')?.focus(), 60);
  }

  function closeAll() {
    mainDD.classList.remove('is-open');
    yearDD.classList.remove('is-open');
    mainBtn.setAttribute('aria-expanded', 'false');
    yearBtn.setAttribute('aria-expanded', 'false');
  }

  mainBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    mainDD.classList.contains('is-open') ? commitAndClose(mainDD, mainBtn, false) : openDrum(mainBtn, mainDD);
  });

  yearBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    yearDD.classList.contains('is-open') ? commitAndClose(yearDD, yearBtn, true) : openDrum(yearBtn, yearDD);
  });

  // Klick ins Off → aktuellen Wert übernehmen
  document.addEventListener('click', (e) => {
    if (!mainDD.contains(e.target) && e.target !== mainBtn) {
      if (mainDD.classList.contains('is-open')) commitAndClose(mainDD, mainBtn, false);
    }
    if (!yearDD.contains(e.target) && e.target !== yearBtn) {
      if (yearDD.classList.contains('is-open')) commitAndClose(yearDD, yearBtn, true);
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      commitAndClose(mainDD, mainBtn, false);
      commitAndClose(yearDD, yearBtn, true);
    }
  });

  // Scroll auf Buttons (ohne Öffnen)
  mainBtn.addEventListener('wheel', (e) => {
    e.preventDefault();
    const items = allPeriods.map(p => chartMode === 'month' ? p.month : p.kw_label).filter((v,i,a) => a.indexOf(v) === i);
    const idx   = items.indexOf(selectedMain);
    const next  = items[idx + (e.deltaY > 0 ? 1 : -1)];
    if (next) { selectedMain = next; mainBtn.textContent = next; applySelection(); }
  }, { passive: false });

  yearBtn.addEventListener('wheel', (e) => {
    e.preventDefault();
    const years = [...new Set(allPeriods.map(p => p.year))];
    const idx   = years.indexOf(selectedYear);
    const next  = years[idx + (e.deltaY > 0 ? 1 : -1)];
    if (next !== undefined) { selectedYear = next; yearBtn.textContent = String(next); applySelection(); }
  }, { passive: false });

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
      const now  = new Date().toLocaleString('de-DE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
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
    urlStatus.innerHTML = '<span style="color:var(--text-muted);">Wird geprüft…</span>';
    try {
      const res  = await fetch('/api/invite-url.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ url }),
      });
      const data = await res.json();
      if (data.success) {
        urlStatus.innerHTML = '<span style="color:var(--status-success); font-size:1.1rem;">✓</span><span style="color:var(--status-success);">URL gespeichert</span>';
        urlInput.value = '';
        checkUrl();
      } else {
        urlStatus.innerHTML = '<span style="color:var(--status-error); font-size:1.1rem;">✗</span><span style="color:var(--status-error);">' + (data.error ?? 'Fehler') + '</span>';
      }
    } catch (_) {
      urlStatus.innerHTML = '<span style="color:var(--status-error); font-size:1.1rem;">✗</span><span style="color:var(--status-error);">Verbindungsfehler</span>';
    }
  });

  checkUrl();

  // ── QR Popout ─────────────────────────────────────────
  const backdrop = document.getElementById('qr-popout-backdrop');
  const btnGen   = document.getElementById('btn-generate');
  const btnClose = document.getElementById('popout-close');
  const steps    = [1,2,3,4].map(n => document.getElementById('step-' + n));
  let logoTmpPath = null;

  function showStep(n) { steps.forEach((s, i) => s.classList.toggle('hidden', i !== n - 1)); }
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
    logoNo.classList.add('active'); logoYes.classList.remove('active');
    logoUploadWrap.style.display = 'none'; logoTmpPath = null;
  });
  logoYes.addEventListener('click', () => {
    logoYes.classList.add('active'); logoNo.classList.remove('active');
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
        if (data.success) { logoTmpPath = data.tmp_path; }
        else { alert(data.error ?? 'Logo-Upload fehlgeschlagen'); return; }
      } catch (_) { alert('Verbindungsfehler beim Logo-Upload'); return; }
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
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ fg: fgHex.value, bg: bgHex.value, logo_tmp: logoTmpPath }),
      });
      const data = await res.json();
      if (data.preview) {
        previewImg.src               = data.preview;
        previewImg.style.display     = 'block';
        previewSpinner.style.display = 'none';
      }
    } catch (_) { previewSpinner.textContent = 'Fehler beim Laden der Vorschau'; }
  }

  document.getElementById('step4-back').addEventListener('click', () => showStep(3));
  document.getElementById('step4-save').addEventListener('click', async () => {
    const btn = document.getElementById('step4-save');
    btn.textContent = 'Wird gespeichert…';
    btn.disabled    = true;
    try {
      const res  = await fetch('/api/qr-save.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ fg: fgHex.value, bg: bgHex.value, logo_tmp: logoTmpPath }),
      });
      const data = await res.json();
      if (data.success) { closePopout(); window.location.reload(); }
      else { alert(data.error ?? 'Fehler beim Speichern'); btn.textContent = 'Übernehmen'; btn.disabled = false; }
    } catch (_) { alert('Verbindungsfehler'); btn.textContent = 'Übernehmen'; btn.disabled = false; }
  });

  // ── Vergleichs-Chart ─────────────────────────────────
  (() => {
    let cmpMode   = 'month';
    let cmpOffset = 0;
    let cmpInst   = null;
    let cmpPeriods = [];
    let cmpSelectedMain = null;
    let cmpSelectedYear = null;
    let compareVisible  = false;

    const compareCard = document.getElementById('compare-card');
    const btnCompare  = document.getElementById('btn-compare');

    btnCompare.addEventListener('click', () => {
      compareVisible = !compareVisible;
      compareCard.classList.toggle('hidden', !compareVisible);
      btnCompare.classList.toggle('is-active', compareVisible);
      if (compareVisible && cmpPeriods.length === 0) renderCmp();
    });

    function updateCmpTodayBtn() {
      document.getElementById('cmp-today').disabled = cmpOffset >= 0;
    }

    function drawCmp(labels, values) {
      const color = getChartColor();
      const ctx   = document.getElementById('cmp-chart').getContext('2d');
      if (cmpInst) cmpInst.destroy();
      cmpInst = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: values,
            borderColor: color,
            backgroundColor: color + '22',
            pointBackgroundColor: color,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.3,
            spanGaps: false,
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

    async function renderCmp() {
      document.getElementById('cmp-next').disabled = cmpOffset >= 0;
      const minOffset = cmpMode === 'month'
        ? (window._minMonthOffset ?? 0)
        : (window._minWeekOffset  ?? 0);
      document.getElementById('cmp-prev').disabled = cmpOffset <= minOffset;
      updateCmpTodayBtn();

      try {
        const res  = await fetch('/api/stats.php?mode=' + cmpMode + '&offset=' + cmpOffset);
        const data = await res.json();
        if (data.periods) buildCmpDropdowns(data.periods);
        drawCmp(data.labels, data.values);
      } catch (_) {
        drawCmp([], []);
      }
    }

    function buildCmpDropdowns(periods) {
      cmpPeriods = periods;
      const current = periods.find(p => p.offset === cmpOffset) ?? periods[0];

      const mainValues = [...new Map(periods.map(p => {
        const key = cmpMode === 'month' ? p.month : p.kw_label;
        return [key, key];
      })).values()];
      const yearValues = [...new Map(periods.map(p => [p.year, p.year])).values()];

      cmpSelectedMain = cmpMode === 'month' ? current.month : current.kw_label;
      cmpSelectedYear = current.year;

      document.getElementById('cmp-main-btn').textContent = cmpSelectedMain;
      document.getElementById('cmp-year-btn').textContent = String(cmpSelectedYear);

      buildDrum(document.getElementById('cmp-main-dd'), mainValues, cmpSelectedMain, (val) => {
        if (val) { cmpSelectedMain = val; document.getElementById('cmp-main-btn').textContent = val; applyCmpSelection(); }
      });
      buildDrum(document.getElementById('cmp-year-dd'), yearValues, cmpSelectedYear, (val) => {
        if (val) { cmpSelectedYear = Number(val); document.getElementById('cmp-year-btn').textContent = val; applyCmpSelection(); }
      });
    }

    function applyCmpSelection() {
      let found;
      if (cmpMode === 'month') {
        found = cmpPeriods.find(p => p.month === cmpSelectedMain && p.year === cmpSelectedYear);
      } else {
        found = cmpPeriods.find(p => p.kw_label === cmpSelectedMain && p.year === cmpSelectedYear);
      }
      if (found) { cmpOffset = found.offset; renderCmp(); }
    }

    function commitCmpAndClose(dd, btn, isYear) {
      const val = getDrumValue(dd);
      if (val) {
        if (isYear) { cmpSelectedYear = Number(val); btn.textContent = val; }
        else        { cmpSelectedMain = val;          btn.textContent = val; }
        applyCmpSelection();
      }
      closeAllCmp();
    }

    function closeAllCmp() {
      document.getElementById('cmp-main-dd').classList.remove('is-open');
      document.getElementById('cmp-year-dd').classList.remove('is-open');
      document.getElementById('cmp-main-btn').setAttribute('aria-expanded', 'false');
      document.getElementById('cmp-year-btn').setAttribute('aria-expanded', 'false');
    }

    function openCmpDrum(btn, dd) {
      closeAllCmp();
      dd.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      setTimeout(() => dd.querySelector('.period-drum-list')?.focus(), 60);
    }

    document.getElementById('cmp-main-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      const dd  = document.getElementById('cmp-main-dd');
      const btn = document.getElementById('cmp-main-btn');
      dd.classList.contains('is-open') ? commitCmpAndClose(dd, btn, false) : openCmpDrum(btn, dd);
    });

    document.getElementById('cmp-year-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      const dd  = document.getElementById('cmp-year-dd');
      const btn = document.getElementById('cmp-year-btn');
      dd.classList.contains('is-open') ? commitCmpAndClose(dd, btn, true) : openCmpDrum(btn, dd);
    });

    document.addEventListener('click', (e) => {
      const mainDD  = document.getElementById('cmp-main-dd');
      const yearDD  = document.getElementById('cmp-year-dd');
      const mainBtn = document.getElementById('cmp-main-btn');
      const yearBtn = document.getElementById('cmp-year-btn');
      if (!mainDD.contains(e.target) && e.target !== mainBtn) {
        if (mainDD.classList.contains('is-open')) commitCmpAndClose(mainDD, mainBtn, false);
      }
      if (!yearDD.contains(e.target) && e.target !== yearBtn) {
        if (yearDD.classList.contains('is-open')) commitCmpAndClose(yearDD, yearBtn, true);
      }
    });

    document.getElementById('cmp-prev').addEventListener('click',  () => { cmpOffset--; renderCmp(); });
    document.getElementById('cmp-next').addEventListener('click',  () => { cmpOffset++; renderCmp(); });
    document.getElementById('cmp-today').addEventListener('click', () => { cmpOffset = 0; renderCmp(); });

    document.getElementById('cmp-toggle-month').addEventListener('click', () => {
      cmpMode = 'month'; cmpOffset = 0;
      document.getElementById('cmp-toggle-month').classList.add('active');
      document.getElementById('cmp-toggle-week').classList.remove('active');
      renderCmp();
    });

    document.getElementById('cmp-toggle-week').addEventListener('click', () => {
      cmpMode = 'week'; cmpOffset = 0;
      document.getElementById('cmp-toggle-week').classList.add('active');
      document.getElementById('cmp-toggle-month').classList.remove('active');
      renderCmp();
    });

    document.getElementById('cmp-main-btn').addEventListener('wheel', (e) => {
      e.preventDefault();
      const items = cmpPeriods.map(p => cmpMode === 'month' ? p.month : p.kw_label).filter((v,i,a) => a.indexOf(v) === i);
      const idx   = items.indexOf(cmpSelectedMain);
      const next  = items[idx + (e.deltaY > 0 ? 1 : -1)];
      if (next) { cmpSelectedMain = next; document.getElementById('cmp-main-btn').textContent = next; applyCmpSelection(); }
    }, { passive: false });

    document.getElementById('cmp-year-btn').addEventListener('wheel', (e) => {
      e.preventDefault();
      const years = [...new Set(cmpPeriods.map(p => p.year))];
      const idx   = years.indexOf(cmpSelectedYear);
      const next  = years[idx + (e.deltaY > 0 ? 1 : -1)];
      if (next !== undefined) { cmpSelectedYear = next; document.getElementById('cmp-year-btn').textContent = String(next); applyCmpSelection(); }
    }, { passive: false });
  })();

  // Start
  renderChart();

})();
</script>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>
