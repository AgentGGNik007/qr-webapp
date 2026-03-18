// app.js
(() => {
  "use strict";
  const storageKey = "qr-webapp-theme";
  const themes = ["light", "grey", "dark", "contrast"];
  const $ = (sel) => document.querySelector(sel);

  const icons = {
    light: `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' width='28' height='28'><circle cx='12' cy='12' r='4'/><path d='M12 2v3 M12 19v3 M2 12h3 M19 12h3 M4.93 4.93l2.12 2.12 M16.95 16.95l2.12 2.12 M19.07 4.93l-2.12 2.12 M7.05 16.95l-2.12 2.12'/></svg>`,
    grey:  `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30' width='28' height='28'><path fill='currentColor' stroke='none' d='M 10.356,16 C 9.7204738,13.042345 11.974847,10.252129 15,10.252129 c 3.025153,0 5.279526,2.790216 4.644,5.747871 L 19.464023,16.510682 17.983825,16.36609 18.092,16 C 18.771615,13.900767 17.206489,11.748995 15,11.748995 c -2.206489,0 -3.771615,2.151772 -3.092,4.251005 l 0.155767,0.367753 -1.491034,0.203705 z'/><path fill='none' stroke='currentColor' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' d='M15 5v3 M5 15h3 M22 15h3 M7.93 7.93l2.12 2.12 M22.07 7.93l-2.12 2.12'/><path fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' d='M1 20.75 Q15 16.25 29 20.75'/></svg>`,
    dark:  `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' width='28' height='28'><g transform='rotate(15 12 12)'><path d='M21 12.8A9 9 0 1 1 11.2 3a7 7 0 1 0 9.8 9.8Z'/></g></svg>`,
    contrast: `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='28' height='28'><circle cx='12' cy='12' r='10' fill='none' stroke='currentColor' stroke-width='1.5'/><path d='M12 2 A10 10 0 0 1 12 22 Z' fill='currentColor'/></svg>`,
  };

  function themeLabel(theme) {
    switch (theme) {
      case "light":    return "Light";
      case "dark":     return "Dark";
      case "grey":     return "Grey";
      case "contrast": return "High Contrast";
      default:         return theme;
    }
  }

  function getInitialTheme() {
    try {
      const stored = localStorage.getItem(storageKey);
      if (themes.includes(stored)) return stored;
    } catch (_) {}
    const prefersDark =
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches;
    return prefersDark ? "dark" : "light";
  }

  function setIcon(el, theme) {
    if (!el) return;
    el.innerHTML = icons[theme] || icons.dark;
  }

  function setTheme(next) {
    const theme = themes.includes(next) ? next : "dark";
    document.documentElement.setAttribute("data-theme", theme);
    try {
      localStorage.setItem(storageKey, theme);
    } catch (_) {}

    const headerBtn = $("#theme-toggle");
    if (headerBtn) headerBtn.setAttribute("aria-label", "Theme wechseln: " + themeLabel(theme));

    setIcon($("#theme-icon"), theme);
    setIcon($("#theme-menu-toggle .icon"), theme);

    const label = $("#theme-label");
    if (label) label.textContent = themeLabel(theme);

    document.querySelectorAll(".theme-menu-item").forEach((item) => {
      item.classList.toggle("is-active", item.getAttribute("data-theme") === theme);
    });
    document.dispatchEvent(new CustomEvent("themechange", { detail: { theme } }));
  }

  function cycleTheme() {
    const current = document.documentElement.getAttribute("data-theme") || "dark";
    const idx = themes.indexOf(current);
    const next = themes[(idx + 1 + themes.length) % themes.length];
    setTheme(next);
  }

  function setupThemeMenu() {
    const wrapper = $(".footer-theme");
    const toggle  = $("#theme-menu-toggle");
    const menu    = $("#theme-menu");
    if (!wrapper || !toggle || !menu) return;
    let closeTimer = null;
    const openMenu = () => { clearTimeout(closeTimer); menu.classList.add("is-open"); };
    const closeMenu = () => { menu.classList.remove("is-open"); };
    const scheduleClose = () => { closeTimer = setTimeout(closeMenu, 120); };
    wrapper.addEventListener("mouseenter", openMenu);
    wrapper.addEventListener("mouseleave", scheduleClose);
    toggle.addEventListener("click", (ev) => {
      ev.stopPropagation();
      menu.classList.contains("is-open") ? closeMenu() : openMenu();
    });
    menu.addEventListener("click", (ev) => {
      const item = ev.target.closest("[data-theme]");
      if (!item) return;
      setTheme(item.getAttribute("data-theme"));
      closeMenu();
    });
    document.addEventListener("click", (ev) => {
      if (!menu.classList.contains("is-open")) return;
      if (!wrapper.contains(ev.target)) closeMenu();
    });
    document.addEventListener("keydown", (ev) => {
      if (ev.key === "Escape") closeMenu();
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    setTheme(getInitialTheme());
    const headerBtn = $("#theme-toggle");
    if (headerBtn) headerBtn.addEventListener("click", cycleTheme);
    setupThemeMenu();
  });
})();
