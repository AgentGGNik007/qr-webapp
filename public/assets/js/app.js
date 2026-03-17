// app.js
(() => {
  "use strict";

  const storageKey = "qr-webapp-theme";
  const themes = ["light", "grey", "dark", "contrast"];
  const $ = (sel) => document.querySelector(sel);

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

  function setTheme(next) {
    const theme = themes.includes(next) ? next : "dark";

    document.documentElement.setAttribute("data-theme", theme);

    try {
      localStorage.setItem(storageKey, theme);
    } catch (_) {}

    // header button aria-label
    const headerBtn = $("#theme-toggle");
    if (headerBtn) headerBtn.setAttribute("aria-label", "Theme wechseln: " + themeLabel(theme));

    // header icon swap
    const icon = $("#theme-icon");
    if (icon) {
      icon.classList.remove("icon-light", "icon-dark", "icon-grey", "icon-contrast");
      const map = {
        light:    "icon-light",
        dark:     "icon-dark",
        grey:     "icon-grey",
        contrast: "icon-contrast",
      };
      icon.classList.add(map[theme] || "icon-dark");
    }

    // footer button icon swap
    const footerIcon = $("#theme-menu-toggle .icon");
    if (footerIcon) {
      footerIcon.classList.remove("icon-light", "icon-dark", "icon-grey", "icon-contrast");
      const map = {
        light:    "icon-light",
        dark:     "icon-dark",
        grey:     "icon-grey",
        contrast: "icon-contrast",
      };
      footerIcon.classList.add(map[theme] || "icon-dark");
    }

    // footer label text
    const label = $("#theme-label");
    if (label) label.textContent = themeLabel(theme);

    // footer dropdown: aktives item markieren
    document.querySelectorAll(".theme-menu-item").forEach((item) => {
      item.classList.toggle("is-active", item.getAttribute("data-theme") === theme);
    });
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

    const openMenu = () => {
      clearTimeout(closeTimer);
      menu.classList.add("is-open");
    };

    const closeMenu = () => {
      menu.classList.remove("is-open");
    };

    const scheduleClose = () => {
      closeTimer = setTimeout(closeMenu, 120);
    };

    // hover: wrapper damit Lücke zwischen Button und Menü kein Problem ist
    wrapper.addEventListener("mouseenter", openMenu);
    wrapper.addEventListener("mouseleave", scheduleClose);

    // click als Fallback (Touch)
    toggle.addEventListener("click", (ev) => {
      ev.stopPropagation();
      menu.classList.contains("is-open") ? closeMenu() : openMenu();
    });

    // item auswählen
    menu.addEventListener("click", (ev) => {
      const item = ev.target.closest("[data-theme]");
      if (!item) return;
      setTheme(item.getAttribute("data-theme"));
      closeMenu();
    });

    // klick außerhalb schließt
    document.addEventListener("click", (ev) => {
      if (!menu.classList.contains("is-open")) return;
      if (!wrapper.contains(ev.target)) closeMenu();
    });

    // Escape schließt
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
