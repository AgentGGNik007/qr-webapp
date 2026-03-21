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
    const cs        = getComputedStyle(document.documentElement);
    const color     = cs.getPropertyValue('--accent').trim();
    const textColor = cs.getPropertyValue('--text').trim();
    const bgColor   = cs.getPropertyValue('--bg').trim();
    const ctx   = document.getElementById('stats-chart').getContext('2d');
    if (chartInst) chartInst.destroy();
    chartInst = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data: values,
          borderColor: color,
          backgroundColor: color + '18',
          pointBackgroundColor: color,
          pointBorderColor: color,
          pointRadius: 3,
          pointHoverRadius: 5,
          tension: 0,
          spanGaps: false,
          fill: { target: { value: -0.5 }, above: color + '18' },
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            grid:  { color: bgColor },
            ticks: { color: textColor },
          },
          y: {
            min: -0.5,
            grid:  { color: bgColor },
            ticks: {
              color: textColor,
              stepSize: 0.5,
              callback: (val) => Number.isInteger(val) && val >= 0 ? val : '',
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
      const res  = await fetch('/zero-trust/api/stats.php?mode=' + chartMode + '&offset=' + chartOffset);
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
  document.addEventListener('themechange', () => {
    renderChart();
    if (typeof renderCmp === 'function') renderCmp();
  });

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
    urlCheckLabel.textContent = reachable ? 'Gültig' : 'Ungültig';
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
      const res  = await fetch('/zero-trust/api/invite-url.php');
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
      const res  = await fetch('/zero-trust/api/invite-url.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ url }),
      });
      const data = await res.json();
      if (data.success) {
        urlStatus.innerHTML = '<span style="color:var(--status-success); font-size:1.1rem;">✓</span><span style="color:var(--status-success);">URL gespeichert</span>';
        urlInput.value = ''; window.location.reload();
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
  document.getElementById('step1-next').addEventListener('click', () => {
    if (!/^#[0-9A-Fa-f]{6}$/.test(bgHex.value)) {
      bgHex.style.outline = '2px solid var(--status-error)';
      return;
    }
    bgHex.style.outline = '';
    showStep(2);
  });

  const fgPicker = document.getElementById('fg-picker');
  const fgHex    = document.getElementById('fg-hex');
  fgPicker.addEventListener('input', () => { fgHex.value = fgPicker.value.toUpperCase(); });
  fgHex.addEventListener('input',   () => { if (/^#[0-9A-Fa-f]{6}$/.test(fgHex.value)) fgPicker.value = fgHex.value; });
  document.getElementById('step2-back').addEventListener('click', () => showStep(1));
  document.getElementById('step2-next').addEventListener('click', () => {
    if (!/^#[0-9A-Fa-f]{6}$/.test(fgHex.value)) {
      fgHex.style.outline = '2px solid var(--status-error)';
      return;
    }
    fgHex.style.outline = '';
    showStep(3);
  });

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
        const res  = await fetch('/zero-trust/api/logo-upload.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) { logoTmpPath = data.tmp_name; }
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
      const res  = await fetch('/zero-trust/api/qr-preview.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ fg: fgHex.value, bg: bgHex.value, logo_name: logoTmpPath }),
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
      const res  = await fetch('/zero-trust/api/qr-save.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ fg: fgHex.value, bg: bgHex.value, logo_name: logoTmpPath }),
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
      const cs        = getComputedStyle(document.documentElement);
      const color     = cs.getPropertyValue('--accent').trim();
      const textColor = cs.getPropertyValue('--text').trim();
      const bgColor   = cs.getPropertyValue('--bg').trim();
      const ctx   = document.getElementById('cmp-chart').getContext('2d');
      if (cmpInst) cmpInst.destroy();
      cmpInst = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: values,
            borderColor: color,
            backgroundColor: color + '18',
            pointBackgroundColor: color,
            pointBorderColor: color,
            pointRadius: 3,
            pointHoverRadius: 5,
            tension: 0,
            spanGaps: false,
            fill: { target: { value: -0.5 }, above: color + '18' },
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: {
              grid:  { color: bgColor },
              ticks: { color: textColor },
            },
            y: {
              min: -0.5,
              grid:  { color: bgColor },
              ticks: {
                color: textColor,
                stepSize: 0.5,
                callback: (val) => Number.isInteger(val) && val >= 0 ? val : '',
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
        const res  = await fetch('/zero-trust/api/stats.php?mode=' + cmpMode + '&offset=' + cmpOffset);
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
