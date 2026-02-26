<?php
declare(strict_types=1);

//Fehlerbehandlung Anfang -- Error-Reporting aktivieren
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
//Fehlerbehandlung Ende


//Config & Session Anfang -- Konfiguration laden und Session starten
$config = require __DIR__ . '/../config/config.php';
session_name($config['session_name']);
session_start();
//Config & Session Ende


//Zugriffskontrolle Anfang -- Login prüfen
if (empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

$user = $_SESSION['user'];
//Zugriffskontrolle Ende

//Return Password Anfang
$_SESSION['return_to_change_pw'] = '/dashboard.php';
//Return Password Ende

//Hilfsdateien Anfang -- QR-Helfer & Invite-Settings
require __DIR__ . '/qr_helpers.php';
require __DIR__ . '/invite_settings.php';
//Hilfsdateien Ende


//QR-Infos Anfang -- Ziel-URL & öffentliche Dateien
$defaultQrUrl  = 'https://culdria.framenode.net/join.php';
$currentQrUrl  = get_setting('qr_target_url') ?? $defaultQrUrl;
$qrPublic      = getQrPublicUrls(); // erwartet 'png' und 'svg'
//QR-Infos Ende

//Invite-Infos Anfang -- Invite-URL, Status & Timestamp
$currentInviteUrl = get_setting('invite_url') ?? 'https://discord.gg/dein-invite';
$inviteStatus     = get_setting('invite_status') ?? 'unknown';
$inviteLastCheck  = get_setting('invite_last_check') ?? null;
$inviteLastCheckDisplay = null;

if ($inviteLastCheck) {
    try {
        $dt = new DateTime($inviteLastCheck);
        $dt->setTimezone(new DateTimeZone('Europe/Berlin'));
        $inviteLastCheckDisplay = $dt->format('d.m.Y, H:i');
    } catch (Exception $e) {
        $inviteLastCheckDisplay = $inviteLastCheck;
    }
}

// Statusklasse & Text für Anzeige (Icon + Text in der Invite-Card)
$statusClass = 'waiting';
$statusText  = 'unbekannt';

if ($inviteStatus === 'complete') {
    $statusClass = 'complete';
    $statusText  = 'erreichbar';
} elseif ($inviteStatus === 'lost') {
    $statusClass = 'lost';
    $statusText  = 'nicht erreichbar';
}

// Flash-Statusmeldung (Toast) aus Session holen und an invite_status angleichen
$inviteFlash = $_SESSION['invite_flash'] ?? null;
unset($_SESSION['invite_flash']);

if ($inviteFlash !== null) {
    if ($inviteStatus === 'complete') {
        $inviteFlash = [
            'type'    => 'success',
            'message' => 'Invite-URL ist erreichbar.'
        ];
    } elseif ($inviteStatus === 'lost') {
        $inviteFlash = [
            'type'    => 'error',
            'message' => 'Invite-URL ist derzeit nicht erreichbar.'
        ];
    } else {
        $inviteFlash = [
            'type'    => 'error',
            'message' => 'Invite-Status ist aktuell unbekannt.'
        ];
    }
}
//Invite-Infos Ende


// Statusklasse & Text für Anzeige
$statusClass = 'waiting';
$statusText  = 'unbekannt';

if ($inviteStatus === 'complete') {
    $statusClass = 'complete';
    $statusText  = 'erreichbar';
} elseif ($inviteStatus === 'lost') {
    $statusClass = 'lost';
    $statusText  = 'nicht erreichbar';
}
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Culdria Invite – Dashboard</title>

    <style>
        @import "https://unpkg.com/open-props/easings.min.css";

        /* Basis-Themenvariablen Anfang */
        :root {
            color-scheme: light dark;
            --bg-light: #f4f4f5;
            --fg-light: #111827;
            --card-light: #E0E0E0;

            --bg-dark: #0f0f10;
            --fg-dark: #e5e7eb;
            --card-dark: #1a1a1c;

            --accent-light: #1B2579;
            --accent-dark:  #C0C5F1;

            --accent: var(--accent-light);

            --icon-fill: currentColor;
            --icon-fill-hover: #38bdf8;

            --border-soft: rgba(148, 163, 184, 0.35);
	    --icon-fill-hover: var(--accent)
        }

        html[data-theme="light"] {
            --accent: var(--accent-light);
        }

        html[data-theme="dark"] {
            --accent: var(--accent-dark);
        }

        html[data-theme="light"] body {
            background-color: var(--bg-light);
            color: var(--fg-light);
        }

        html[data-theme="light"] .card {
            background-color: var(--card-light);
        }

        html[data-theme="dark"] body {
            background-color: var(--bg-dark);
            color: var(--fg-dark);
        }

        html[data-theme="dark"] .card {
            background-color: var(--card-dark);
        }
        /* Basis-Themenvariablen Ende */


        /* Layout & Shell Anfang */
        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, sans-serif;
        }

        .shell {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
            box-sizing: border-box;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        h1 {
            margin: 0.2rem 0 0;
            font-size: 1.6rem;
        }

        .meta {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
        /* Layout & Shell Ende */


        /* Card CSS Basis Anfang */
        .card {
            border-radius: 0.9rem;
            padding: 1.5rem 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.35);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-subtitle {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        /* Card CSS Basis Ende */


        /* Buttons & Links Anfang */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            background: rgba(148, 163, 184, 0.18);
            color: inherit;
        }

        .btn-primary {
            background: var(--accent);
        }

        .btn-secondary {
            border-color: var(--border-soft);
            background: transparent;
        }

        .btn-accent {
            background: var(--accent);
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            white-space: nowrap;
        }

        html[data-theme="light"] .btn-primary,
        html[data-theme="light"] .btn-accent {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .btn-primary,
        html[data-theme="dark"] .btn-accent {
            color: #0f172a;
        }

        .btn:hover,
        .btn-accent:hover {
            opacity: 0.9;
        }

        button[type="submit"] {
            border: none;
        }
        /* Buttons & Links Ende */

	/* Invite-Toast Anfang */
	.invite-toast {
	    position: fixed;
	    top: 1.5rem;
	    left: 50%;
	    transform: translateX(-50%);
	    z-index: 999;
	    min-width: 260px;
	    max-width: 420px;
	    padding: 0.9rem 1.2rem;
	    border-radius: 0.75rem;
	    font-size: 0.95rem;
	    font-weight: 500;
	    text-align: center;
	    box-shadow: 0 16px 40px rgba(15,23,42,0.55);
	    opacity: 0;
	    pointer-events: none;
	    transition: opacity 0.25s ease, transform 0.25s ease;
	}

	.invite-toast--show {
	    opacity: 1;
	    transform: translateX(-50%) translateY(0);
	    pointer-events: auto;
	}

	.invite-toast--success {
	    background: rgba(34,197,94,0.95);
	    color: #022c22;
	}

	.invite-toast--error {
	    background: rgba(248,113,113,0.95);
	    color: #111827;
	}
	/* Invite-Toast Ende */


	/* Header-Actions & Menü Anfang */
	.header-actions {
	    display: flex;
	    align-items: center;
	    gap: 0.5rem;
	    position: relative;
	}

	/* Icon Button (Hamburger & Theme Toggle Wrapper) */
	.icon-button {
	    background: transparent;
	    border-radius: 999px;
	    padding: 0.35rem 0.5rem;
	    display: inline-flex;
	    align-items: center;
	    justify-content: center;
	    cursor: pointer;
	    transition: opacity 0.15s ease;
	}

	/* DARK MODE – klare helle Linie */
	html[data-theme="dark"] .icon-button {
	    border: 1px solid rgba(255,255,255,0.22);
	    color: #e5e7eb;
	}

	/* LIGHT MODE – dunkle Outline für Sichtbarkeit */
	html[data-theme="light"] .icon-button {
	    border: 1px solid rgba(0,0,0,0.35);
	    color: #111827;
	}

	/* dezentes Hover */
	.icon-button:hover {
	    opacity: 0.75;
	}

	.icon-button svg {
	    display: block;
	}

	/* Dropdown-Menü */
	.header-menu {
	    position: absolute;
	    top: calc(100% + 0.6rem);
	    right: 0;
	    min-width: 180px;
	    border-radius: 0.75rem;
	   	border: 1px solid rgba(148,163,184,0.35);
	    box-shadow: 0 18px 35px rgba(15, 23, 42, 0.55);
	    padding: 0.35rem 0;
	    z-index: 30;
	    background-color: var(--card-dark);
	}

	/* Light Mode Menü – dunklere Fläche für besseren Kontrast */
	html[data-theme="light"] .header-menu {
	    background-color: #e5e7eb; /* statt #f9fafb */
	    color: #111827;
	}

	/* wird via JS versteckt */
	.header-menu[hidden] {
	    display: none;
	}

	/* Menüeinträge */
	.header-menu-item {
	    display: block;
	    width: 100%;
	    padding: 0.55rem 1rem;
	    font-size: 0.92rem;
	    color: inherit;
	    background: transparent;
	    border: none;
	    cursor: pointer;
	    text-decoration: none;
	}

	/* KEIN Hintergrund beim Hover – nur subtile Opacity */
	.header-menu-item:hover {
	    opacity: 0.75;
	}
	/* Header-Actions & Menü Ende */


        /* Stats Placeholder Anfang */
        .stats-placeholder {
            text-align: center;
            font-size: 1.2rem;
            opacity: 0.75;
            padding: 2rem 0;
        }
        /* Stats Placeholder Ende */


        /* QR-Card Layout Anfang */
        .qr-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }

        .qr-image-wrapper {
            display: flex;
            justify-content: center;
        }

        .qr-image {
            max-width: 260px;
            width: 100%;
            height: auto;
            border-radius: 0.75rem;
            border: 1px solid var(--border-soft);
            padding: 0.5rem;
            background: #020617;
        }

        html[data-theme="light"] .qr-image {
            background: #f9fafb;
        }

        .qr-link-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .qr-link-anchor {
            font-weight: 500;
            word-break: break-all;
        }

        .qr-download-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        /* QR-Card Layout Ende */


	/* Invite-Card Layout Anfang */
	.invite-row {
	    display: grid;
	    grid-template-columns: minmax(0, 2.4fr) minmax(0, 1.8fr) minmax(0, 1.6fr);
	    column-gap: 1.5rem;
	    row-gap: 0.75rem;              /* mehr Abstand zwischen Label-Zeile und Werte-Zeile */
	    align-items: flex-start;
	    max-width: 900px;
	    margin: 0 auto;
	}

	.invite-col {
	    display: flex;
	    flex-direction: column;
	}

	/* Überschrift-Zeile: "Aktuelle URL / Status / Prüfen & Korrigieren" */
	.invite-label {
	    font-size: 0.9rem;
	    font-weight: 600;              /* fett */
	    opacity: 0.95;                 /* besser sichtbar */
	    margin-bottom: 0.4rem;         /* Abstand zur unteren Reihe */
	}

	/* zweite Reihe: URL, Status-Zeile, Buttons */
	.invite-value {
	    font-size: 0.9rem;
	}

	.invite-url-value {
	    word-break: break-all;
	}

	.invite-status-line {
	    display: flex;
	    flex-wrap: wrap;
	    gap: 0.75rem;
	    align-items: center;
	}

	.invite-status-cell {
	    display: flex;
	    align-items: center;
	    gap: 0.4rem;
	    font-size: 0.85rem;
	}

	.invite-last-check-inline {
	    font-size: 0.8rem;
	    opacity: 0.75;
	    margin-top: 0.25rem;
	}

	/* Buttons bleiben in EINER Reihe rechts */
	.invite-actions-inline {
	    display: flex;
	    flex-wrap: nowrap;             /* Buttons nicht umbrechen */
	    gap: 0.5rem;
	    justify-content: flex-end;
	}
	/* Invite-Card Layout Ende */


        /* Invite-Status Icons Anfang */
        .status {
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid transparent;
            box-sizing: border-box;
            position: relative;
        }

        .status.active::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            border-width: 3px;
            border-style: solid;
            border-color: transparent;
            margin-top: -3px;
            margin-left: -2px;
            border-left: 6px solid #3cb87e;
        }

        .status.waiting {
            border-color: #bbb;
        }
        .status.waiting::before {
            content: '';
            position: absolute;
            width: 3px;
            height: 4px;
            top: 2px;
            left: 50%;
            margin-left: -1px;
            border-left: 2px solid #bbb;
            border-bottom: 2px solid #bbb;
        }

        .status.complete {
            border-color: #3cb87e;
        }
        .status.complete::before {
            content: '✓';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-top: -8px;
            margin-left: -8px;
            background: #3cb87e;
            color: #ffffff;
            border-radius: 50%;
            font-size: 12px;
            line-height: 16px;
            text-align: center;
        }

        .status.lost {
            border-color: #f04848;
        }
        .status.lost::before {
            content: '×';
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -9px;
            margin-left: -9px;
            width: 18px;
            height: 18px;
            color: #f04848;
            font-weight: bold;
            font-size: 16px;
            line-height: 18px;
            text-align: center;
        }
        /* Invite-Status Icons Ende */


        /* Symbolik Themen Switch Anfang */
        .theme-toggle {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: currentColor;
        }

        .sun-and-moon > :is(.moon, .sun, .sun-beams) {
            transform-origin: center;
        }

        .sun-and-moon > :is(.moon, .sun) {
            fill: var(--icon-fill);
        }

        .theme-toggle:is(:hover, :focus-visible) > .sun-and-moon > :is(.moon, .sun) {
            fill: var(--icon-fill-hover);
        }

        .sun-and-moon > .sun-beams {
            stroke: var(--icon-fill);
            stroke-width: 2px;
        }

        .theme-toggle:is(:hover, :focus-visible) .sun-and-moon > .sun-beams {
            stroke: var(--icon-fill-hover);
        }

        [data-theme="dark"] .sun-and-moon > .sun {
            transform: scale(1.75);
        }

        [data-theme="dark"] .sun-and-moon > .sun-beams {
            opacity: 0;
        }

        [data-theme="dark"] .sun-and-moon > .moon > circle {
            transform: translateX(-7px);
        }

        @supports (cx: 1) {
            [data-theme="dark"] .sun-and-moon > .moon > circle {
                cx: 17;
                transform: translateX(0);
            }
        }

        @media (prefers-reduced-motion: no-preference) {
            .sun-and-moon > .sun {
                transition: transform .5s var(--ease-elastic-3);
            }

            .sun-and-moon > .sun-beams {
                transition: transform .5s var(--ease-elastic-4), opacity .5s var(--ease-3);
            }

            .sun-and-moon .moon > circle {
                transition: transform .25s var(--ease-out-5);
            }

            @supports (cx: 1) {
                .sun-and-moon .moon > circle {
                    transition: cx .25s var(--ease-out-5);
                }
            }

            [data-theme="dark"] .sun-and-moon > .sun {
                transition-timing-function: var(--ease-3);
                transition-duration: .25s;
                transform: scale(1.75);
            }

            [data-theme="dark"] .sun-and-moon > .sun-beams {
                transition-duration: .15s;
                transform: rotateZ(-25deg);
            }

            [data-theme="dark"] .sun-and-moon > .moon > circle {
                transition-duration: .5s;
                transition-delay: .25s;
            }
        }
        /* Symbolik Themen Switch Ende */


        /* Modal Popup Allgemein Anfang */
        .modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal[hidden] {
            display: none;
        }

        .modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
        }

        .modal-dialog {
            position: relative;
            z-index: 60;
            max-width: 480px;
            width: 92%;
            border-radius: 0.9rem;
            padding: 1.25rem 1.5rem 1rem;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background-color: var(--card-dark);
        }

        html[data-theme="light"] .modal-dialog {
            background-color: #f9fafb;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .modal-close {
            border: none;
            background: transparent;
            font-size: 1.3rem;
            line-height: 1;
            cursor: pointer;
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .hint {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        /* Modal Popup Allgemein Ende */


        /* Responsive Anpassungen Anfang */
        @media (max-width: 700px) {
            .shell {
                padding: 1.5rem 1rem 2.5rem;
            }

            .card {
                padding: 1.25rem 1.3rem;
            }

            .invite-row {
                grid-template-columns: 1fr;
            }

            .invite-actions-inline {
                justify-content: flex-start;
            }
        }
        /* Responsive Anpassungen Ende */
    </style>

    <script>
    //Theme-Toggle & Dashboard Logik Anfang
    (function () {
        const storageKey = 'culdria-theme';

        function getColorPreference() {
            try {
                const stored = localStorage.getItem(storageKey);
                if (stored === 'light' || stored === 'dark') return stored;
            } catch (e) {}

            return window.matchMedia &&
                   window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
        }

        const theme = {
            value: getColorPreference(),
        };

        function reflectPreference() {
            const html = document.documentElement;
            html.setAttribute('data-theme', theme.value);

            const toggle = document.querySelector('#theme-toggle');
            if (toggle) {
                toggle.setAttribute('aria-label', theme.value);
            }
        }

        function setPreference() {
            try {
                localStorage.setItem(storageKey, theme.value);
            } catch (e) {}
            reflectPreference();
        }

        function onThemeClick() {
            theme.value = theme.value === 'light' ? 'dark' : 'light';
            setPreference();
        }

        reflectPreference();

        window.addEventListener('load', function () {
            reflectPreference();

            const btn = document.querySelector('#theme-toggle');
            if (btn) {
                btn.addEventListener('click', onThemeClick);
            }

            // Header-Menü Anfang
            const menuToggle = document.getElementById('header-menu-toggle');
            const menu       = document.getElementById('header-menu');

            function closeMenu() {
                if (menu) {
                    menu.hidden = true;
                }
            }

            function toggleMenu(ev) {
                ev.stopPropagation();
                if (!menu) return;
                menu.hidden = !menu.hidden;
            }

            if (menuToggle && menu) {
                menuToggle.addEventListener('click', toggleMenu);

                document.addEventListener('click', function (ev) {
                    if (menu.hidden) return;
                    if (!menu.contains(ev.target) && ev.target !== menuToggle) {
                        closeMenu();
                    }
                });

                document.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Escape') {
                        closeMenu();
                    }
                });
            }
            // Header-Menü Ende


            // Modal-Helfer Anfang
            function setupModal(triggerId, modalId, closeIds) {
                const trigger = document.getElementById(triggerId);
                const modal   = document.getElementById(modalId);
                if (!modal) return;

                const backdrop = modal.querySelector('.modal-backdrop');
                const closeButtons = (closeIds || [])
                    .map(id => document.getElementById(id))
                    .filter(Boolean);

                function openModal() {
                    modal.hidden = false;
                }

                function closeModal() {
                    modal.hidden = true;
                }

                if (trigger) {
                    trigger.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        openModal();
                    });
                }

                closeButtons.forEach(btn => {
                    btn.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        closeModal();
                    });
                });

                if (backdrop) {
                    backdrop.addEventListener('click', closeModal);
                }

                document.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Escape' && !modal.hidden) {
                        closeModal();
                    }
                });
            }
            // Modal-Helfer Ende


            // Modal: QR-Download Anfang
            setupModal(
                'qr-download-modal-open',
                'qr-download-modal',
                ['qr-download-modal-close', 'qr-download-modal-cancel']
            );
            // Modal: QR-Download Ende


            // Modal: Invite-URL bearbeiten Anfang
            setupModal(
                'invite-change-btn',
                'invite-modal',
                ['invite-modal-close', 'invite-cancel-btn']
            );
            // Modal: Invite-URL bearbeiten Ende


            // QR-Link kopieren Anfang
            const copyBtn = document.getElementById('qr-copy-button');
            const urlText = "<?php echo htmlspecialchars($currentQrUrl, ENT_QUOTES, 'UTF-8'); ?>";

            if (copyBtn) {
                copyBtn.addEventListener('click', async function () {
                    try {
                        await navigator.clipboard.writeText(urlText);
                        copyBtn.textContent = "Kopiert!";
                        setTimeout(() => copyBtn.textContent = "Link kopieren", 1500);
                    } catch (err) {
                        alert("Konnte den Link nicht kopieren.");
                    }
                });
            }
            // QR-Link kopieren Ende
        });

        if (window.matchMedia) {
            const mql = window.matchMedia('(prefers-color-scheme: dark)');

            function onMediaChange(e) {
                theme.value = e.matches ? 'dark' : 'light';
                setPreference();
            }

            if (typeof mql.addEventListener === 'function') {
                mql.addEventListener('change', onMediaChange);
            } else if (typeof mql.addListener === 'function') {
                mql.addListener(onMediaChange);
            }
        }
    })();
    //Theme-Toggle & Dashboard Logik Ende

    document.addEventListener('DOMContentLoaded', function () {
	    const toast = document.getElementById('invite-toast');
	    if (!toast) return;

	    // kurz verzögert anzeigen
	    requestAnimationFrame(() => {
	        toast.classList.add('invite-toast--show');
	    });

	    // nach 3,5 Sekunden wieder ausblenden
	    setTimeout(() => {
	        toast.classList.remove('invite-toast--show');
	    }, 3500);
	});
    </script>
</head>

<body>
<?php if ($inviteFlash): ?>
    <div
        class="invite-toast invite-toast--<?php echo htmlspecialchars($inviteFlash['type'], ENT_QUOTES); ?>"
        id="invite-toast"
    >
        <?php echo htmlspecialchars($inviteFlash['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
    </div>
<?php endif; ?>
<div class="shell">

    <header>
        <div>
            <h1>Invite QR-Code – Dashboard</h1>
            <div class="meta">
                Eingeloggt als:
                <strong><?php echo htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                (<?php echo htmlspecialchars($user['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)
            </div>
        </div>

        <div class="header-actions">
            <!-- Header-Menü Button -->
            <button class="icon-button" id="header-menu-toggle" aria-label="Menü öffnen">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                    <line x1="4" y1="7" x2="20" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <line x1="4" y1="12" x2="20" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <line x1="4" y1="17" x2="20" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
            </button>

            <!-- Theme Toggle -->
            <button class="theme-toggle" id="theme-toggle" title="Toggles light & dark" aria-label="auto" aria-live="polite">
                <svg class="sun-and-moon" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24">
                    <mask class="moon" id="moon-mask">
                        <rect x="0" y="0" width="100%" height="100%" fill="white" />
                        <circle cx="24" cy="10" r="6" fill="black" />
                    </mask>
                    <circle class="sun" cx="12" cy="12" r="6" mask="url(#moon-mask)" fill="currentColor" />
                    <g class="sun-beams" stroke="currentColor">
                        <line x1="12" y1="1" x2="12" y2="3" />
                        <line x1="12" y1="21" x2="12" y2="23" />
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                        <line x1="1" y1="12" x2="3" y2="12" />
                        <line x1="21" y1="12" x2="23" y2="12" />
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                    </g>
                </svg>
            </button>

            <!-- Dropdown-Menü -->
            <div class="header-menu" id="header-menu" hidden>
                <a href="/change_password.php" class="header-menu-item">Passwort ändern</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="/admin_users.php" class="header-menu-item">Adminbereich</a>
                <?php endif; ?>
                <a href="/logout.php" class="header-menu-item">Abmelden</a>
            </div>
        </div>
    </header>

    <!-- Card: Statistiken (Platzhalter) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Statistiken</div>
            <div class="card-subtitle">Übersicht der QR-Scans & Nutzung</div>
        </div>
        <div class="card-body">
            <div class="stats-placeholder">
                Hier kommen demnächst die Statistiken hin.
            </div>
        </div>
    </div>

    <!-- Card: Server QR-Code (nur Anzeige, Kopieren, Download) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Server QR-Code</div>
            <div class="card-subtitle">Direkter Zugang zum Join-Link</div>
        </div>

        <div class="card-body">
            <div class="qr-layout">
                <div class="qr-image-wrapper">
                    <?php
			$qrPngPath = $qrPublic['png'] ?? '/qr/culdria-qr.png';
			$qrPngFs   = '/var/www/culdria-app/public' . $qrPngPath;
			$qrVer     = file_exists($qrPngFs) ? filemtime($qrPngFs) : time();
		    ?>
		    <img
		  	src="<?php echo htmlspecialchars($qrPngPath . '?v=' . $qrVer, ENT_QUOTES, 'UTF-8'); ?>"
		  	alt="Einladungs-QR-Code"
		  	class="qr-image"
		    >
                </div>

                <div class="qr-link-row">
                    <a
                        href="<?php echo htmlspecialchars($currentQrUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        target="_blank"
                        rel="noopener"
                        class="qr-link-anchor"
                    >
                        <?php echo htmlspecialchars($currentQrUrl, ENT_QUOTES, 'UTF-8'); ?>
                    </a>

                    <button class="btn-accent" id="qr-copy-button">
                        Link kopieren
                    </button>
                </div>

                <div class="qr-download-row">
                    <button class="btn-accent" id="qr-download-modal-open">
                        QR-Code herunterladen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: Invite-URL & Status -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Invite-URL</div>
            <div class="card-subtitle">Status & Verwaltung des Discord-Einladungslinks</div>
        </div>

        <div class="card-body">
            <div class="invite-row">
                <!-- Spalte 1: Aktuelle URL -->
                <div class="invite-col">
                    <div class="invite-label">Aktuelle URL:</div>
                    <div class="invite-value invite-url-value">
                        <?php echo htmlspecialchars($currentInviteUrl ?? 'Nicht gesetzt', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                </div>

                <!-- Spalte 2: Status + Timestamp -->
                <div class="invite-col">
                    <div class="invite-label">Status:</div>
                    <div class="invite-value">
                        <div class="invite-status-line">
                            <div class="invite-status-cell">
                                <span class="status active" title="Bereit zur Prüfung"></span>
                                <span>Prüfung</span>
                            </div>

                            <div class="invite-status-cell">
                                <span
                                    class="status <?php echo htmlspecialchars($statusClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                    title="<?php echo htmlspecialchars($statusText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                ></span>
                                <span><?php echo htmlspecialchars($statusText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                            </div>
                        </div>

                        <?php if ($inviteLastCheck): ?>
                            <div class="invite-last-check-inline">
                                Letzte Prüfung:
                                <?php echo htmlspecialchars($inviteLastCheckDisplay ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        <?php else: ?>
                            <div class="invite-last-check-inline">
                                Noch keine Prüfung durchgeführt.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Spalte 3: Aktionen -->
                <div class="invite-col">
                    <div class="invite-label">Prüfen &amp; Korrigieren:</div>
                    <div class="invite-value invite-actions-inline">
                        <form method="post" action="/check_invite.php">
                            <button type="submit" class="btn-accent">
                                Prüfen
                            </button>
                        </form>

                        <button type="button" class="btn-accent" id="invite-change-btn">
                            URL ändern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: QR-Code herunterladen -->
<div class="modal" id="qr-download-modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>QR-Code herunterladen</h3>
            <button type="button" class="modal-close" id="qr-download-modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <p>Wähle ein Format zum Herunterladen aus:</p>

            <div class="qr-download-options" style="display:flex; flex-direction:column; gap:0.75rem; align-items:center;">
                <a
                    href="<?php echo htmlspecialchars($qrPublic['png'] ?? '/qr/culdria-qr.png', ENT_QUOTES, 'UTF-8'); ?>"
                    download="culdria-qr.png"
                    class="btn-accent"
                    style="width:70%; justify-content:center;"
                >
                    PNG herunterladen
                </a>

                <a
                    href="<?php echo htmlspecialchars($qrPublic['svg'] ?? '/qr/culdria-qr.svg', ENT_QUOTES, 'UTF-8'); ?>"
                    download="culdria-qr.svg"
                    class="btn btn-accent"
                    style="width:70%; justify-content:center;"
                >
                    SVG herunterladen
                </a>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="qr-download-modal-cancel">
                Schließen
            </button>
        </div>
    </div>
</div>

<!-- Modal: Invite-URL ändern -->
<div class="modal" id="invite-modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3>Invite-URL ändern</h3>
            <button type="button" class="modal-close" id="invite-modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <form method="post" action="/update_invite.php">
                <label for="invite_url_input">Neue Discord-Invite-URL</label>
                <input
                    type="text"
                    id="invite_url_input"
                    name="invite_url"
                    required
                    style="width:100%; max-width:none; padding:0.45rem 0.55rem; border-radius:0.5rem; border:1px solid var(--border-soft); background:transparent; color:inherit; box-sizing:border-box;"
                >

                <p class="hint">
                    Beispiel: <code>https://discord.gg/dein-einladungslink</code>
                </p>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="invite-cancel-btn">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn-accent">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
