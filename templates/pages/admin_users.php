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


//Zugriffskontrolle Anfang -- Login prüfen und Adminrolle erzwingen
if (empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo 'Zugriff verweigert.';
    exit;
}
//Zugriffskontrolle Ende

//Return Password Anfang
$_SESSION['return_to_change_pw'] = '/dashboard.php';
//Return Password Ende

//Statusvariablen Anfang -- Meldungen für UI initialisieren
$error   = null;
$success = null;

$qrMessage     = null;
$qrMessageType = 'success';
//Statusvariablen Ende


//DB-Verbindung Anfang -- SQLite via PDO öffnen
try {
    $userDb = new PDO('sqlite:' . $config['db_path']);
    $userDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'DB-Fehler.';
    exit;
}
//DB-Verbindung Ende


//Hilfsdateien Anfang -- Settings & QR-Helfer laden
require __DIR__ . '/invite_settings.php';
require __DIR__ . '/qr_helpers.php';
//Hilfsdateien Ende


//QR-Defaults Anfang -- Standard-URL dynamisch aus config
$config = require __DIR__ . '/../config/config.php';

$defaultQrUrl = rtrim($config['base_url'], '/') . '/join.php';
$currentQrUrl = $defaultQrUrl;

$qrPublic = getQrPublicUrls(); // Erwartet Array mit 'png'/'svg'-Pfaden
//QR-Defaults Ende


//POST-Handling Anfang -- Formulare für Benutzer & QR verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Benutzeraktionen Anfang -- Benutzer anlegen / Passwort zurücksetzen
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        //Benutzer anlegen Anfang
        $newUsername  = trim($_POST['new_username'] ?? '');
        $newPassword  = $_POST['new_password'] ?? '';
        $newPassword2 = $_POST['new_password_confirm'] ?? '';
        $newRole      = $_POST['new_role'] ?? 'owner';

        if ($newUsername === '' || $newPassword === '' || $newPassword2 === '') {
            $error = 'Bitte alle Felder ausfüllen.';
        } elseif ($newPassword !== $newPassword2) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            try {
                // Prüfen, ob Benutzername existiert
                $stmt = $userDb->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
                $stmt->execute([':u' => $newUsername]);
                $exists = (int)$stmt->fetchColumn() > 0;

                if ($exists) {
                    $error = 'Benutzername ist bereits vergeben.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $role = $newRole === 'admin' ? 'admin' : 'owner';

                    $insert = $userDb->prepare(
                        'INSERT INTO users (username, password_hash, role, must_change_password, created_at)
                         VALUES (:u, :p, :r, 1, CURRENT_TIMESTAMP)'
                    );
                    $insert->execute([
                        ':u' => $newUsername,
                        ':p' => $hash,
                        ':r' => $role,
                    ]);

                    $success = 'Benutzer wurde angelegt. Beim ersten Login wird ein Passwortwechsel erzwungen.';
                }
            } catch (Throwable $e) {
                $error = 'Fehler beim Anlegen des Benutzers.' . $e->getMessage();
            }
        }
        //Benutzer anlegen Ende
    }

    if ($action === 'reset_password') {
        //Passwort-Reset Anfang
        $resetUserId  = (int)($_POST['user_id'] ?? 0);
        $tempPassword = $_POST['temp_password'] ?? '';

        if ($resetUserId <= 0 || $tempPassword === '') {
            $error = 'Benutzer und neues Passwort angeben.';
        } else {
            try {
                $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

                $upd = $userDb->prepare(
                    'UPDATE users SET password_hash = :p, must_change_password = 1 WHERE id = :id'
                );
                $upd->execute([
                    ':p'  => $hash,
                    ':id' => $resetUserId,
                ]);

                $success = 'Passwort wurde zurückgesetzt. Beim nächsten Login muss der Benutzer es ändern.';
            } catch (Throwable $e) {
                $error = 'Fehler beim Zurücksetzen des Passworts.' . $e->getMessage();
            }
        }
        //Passwort-Reset Ende
    }
    //Benutzeraktionen Ende


    //QR-Aktionen Anfang -- Ziel-URL aktualisieren / QR neu generieren
    if (isset($_POST['qr_action'])) {
        $qrAction = $_POST['qr_action'];

        if ($qrAction === 'update_url') {
            //QR-URL-Update Anfang
            $newUrl = trim($_POST['qr_target_url'] ?? '');

            if ($newUrl === '') {
                $qrMessage     = 'Bitte eine gültige URL eingeben.';
                $qrMessageType = 'error';
            } else {
                set_setting('qr_target_url', $newUrl);
                $currentQrUrl = $newUrl;

                try {
                    generateQrCodeToFiles($currentQrUrl);
                    $qrMessage     = 'QR-Ziel-URL wurde aktualisiert und der QR-Code wurde neu generiert.';
                    $qrMessageType = 'success';
                } catch (Throwable $e) {
                    $qrMessage     = 'QR-Ziel-URL wurde gespeichert, aber der QR-Code konnte nicht generiert werden.' . $e->getMessage();
                    $qrMessageType = 'error';
                }
            }
            //QR-URL-Update Ende

        } elseif ($qrAction === 'regenerate_qr') {
            //QR-Regeneration Anfang
            try {
                generateQrCodeToFiles($currentQrUrl);
                $qrMessage     = 'QR-Code wurde neu generiert.';
                $qrMessageType = 'success';
            } catch (Throwable $e) {
                $qrMessage     = 'QR-Code konnte nicht neu generiert werden.';
                $qrMessageType = 'error';
            }
            //QR-Regeneration Ende
        }
    }
    //QR-Aktionen Ende
}
//POST-Handling Ende


//Benutzerliste Laden Anfang -- Benutzerübersicht für Tabelle holen
$users = [];

try {
    $stmt = $userDb->query('SELECT id, username, role, must_change_password FROM users ORDER BY id ASC');

    if ($stmt === false) {
        $error = $error ?? 'Die Benutzertabelle "users" konnte nicht geladen werden (möglicherweise fehlt sie in der Datenbank).';
        $users = [];
    } else {
        if ($stmt instanceof PDOStatement) {
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = $error ?? 'Die Benutzertabelle "users" konnte nicht geladen werden (unerwarteter DB-Typ).';
            $users = [];
        }
    }
} catch (Throwable $e) {
    $error = $error ?? 'Die Benutzertabelle "users" konnte nicht geladen werden: ' . $e->getMessage();
    $users = [];
}
//Benutzerliste Laden Ende
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung – Culdria Invite</title>

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
            --accent-dark: #C0C5F1;

            --accent: var(--accent-light);

            --border-soft: rgba(148, 163, 184, 0.35);
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


        /* Formulare & Selects Darkmode Anfang */
        html[data-theme="dark"] select {
            background-color: #1a1a1c;
            color: #e5e7eb;
            border-color: rgba(148,163,184,0.5);
        }

        html[data-theme="dark"] select option {
            background-color: #1a1a1c;
            color: #e5e7eb;
        }

        html[data-theme="dark"] select option:hover,
        html[data-theme="dark"] select option:focus {
            background-color: #2a2a2c;
            color: #ffffff;
        }
        /* Formulare & Selects Darkmode Ende */


        /* Layout & Shell Anfang -- Seite nicht mehr vertikal "eingeklemmt" */
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

        .back-link {
            font-size: 0.9rem;
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

        .top-grid ~ .card {
            margin-top: 3rem;
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


        /* Top-Grids Anfang -- Benutzer & QR nebeneinander */
        .top-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .card-create-user,
        .card-qr {
            height: 100%;
        }
        /* Top-Grids Ende */


        /* Formulare & Inputs Anfang */
        label {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="password"],
        input[type="url"],
        select {
            width: 100%;
            max-width: 260px;
            padding: 0.45rem 0.55rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-soft);
            background: transparent;
            color: inherit;
            font-size: 0.9rem;
            box-sizing: border-box;
        }

        .create-user-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .create-user-form .form-row {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
        }

        .create-user-form button {
            margin-top: 0.75rem;
        }

        .inline-input {
            max-width: 150px;
        }
        /* Formulare & Inputs Ende */


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

        html[data-theme="light"] .btn-primary {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .btn-primary {
            color: #0f172a;
        }

        .btn:hover {
            opacity: 0.9;
        }

        button[type="submit"] {
            border: none;
        }

        .link-button {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
        }

        html[data-theme="dark"] .link-button {
            background: var(--accent);
            color: #0f172a;
        }

        html[data-theme="light"] .link-button {
            background: var(--accent);
            color: #e5e7eb;
        }

        .link-button:hover {
            opacity: 0.9;
        }

	/* Button "Benutzer anlegen" verkleinern */
	.create-user-form button[type="submit"] {
	    width: 50%;               	/* kleinere Breite */
	    max-width: 220px;         	/* absolute Grenze */
	    margin: 1rem auto 0 auto; 	/* zentriert, mit etwas Abstand oben */
	    padding: 0.5rem 1rem;     	/* kompakter */
	    font-size: 0.95rem;       	/* etwas kleiner */
	    border-radius: 0.75rem;   	/* weicher */
	}
        /* Buttons & Links Ende */


        /* Alerts & Statusmeldungen Anfang */
        .error,
        .success,
        .alert {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .error,
        .alert-error {
            color: #f97373;
        }

        .success,
        .alert-success {
            color: #4ade80;
        }

        .alert {
            padding: 0.4rem 0.6rem;
            border-radius: 0.4rem;
            border: 1px solid var(--border-soft);
        }
        /* Alerts & Statusmeldungen Ende */


        /* Tabelle Benutzerliste Anfang */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .user-table th,
        .user-table td {
            padding: 0.4rem 0.5rem;
            border-bottom: 1px solid rgba(148,163,184,0.2);
            text-align: left;
        }

        .user-table th {
            font-weight: 600;
            opacity: 0.9;
        }

        .user-reset-form {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: nowrap;
        }
        /* Tabelle Benutzerliste Ende */


        /* QR-Card Layout Anfang */
        .qr-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .qr-image-wrapper {
            display: flex;
            justify-content: center;
        }

        .qr-image {
            max-width: 180px;
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid var(--border-soft);
            background: #0b1120;
            padding: 0.5rem;
        }

        html[data-theme="light"] .qr-image {
            background: #f9fafb;
        }

        .qr-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .qr-url-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
        }

        .qr-url-value {
            flex: 1 1 220px;
            font-size: 0.9rem;
            word-break: break-all;
            padding: 0.4rem 0.55rem;
            border-radius: 0.45rem;
            border: 1px solid var(--border-soft);
            background: rgba(15,23,42,0.35);
        }

        html[data-theme="light"] .qr-url-value {
            background: rgba(249, 250, 251, 0.9);
        }

        .qr-description {
            font-size: 0.85rem;
            opacity: 0.8;
        }

	.qr-regenerate-form {
	    display: flex;
	    justify-content: center;   /* Button mittig */
	    width: 100%;
	}
        /* QR-Card Layout Ende */


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
            fill: var(--icon-fill, currentColor);
        }

        .theme-toggle:is(:hover, :focus-visible) > .sun-and-moon > :is(.moon, .sun) {
            fill: var(--accent);
        }

        .sun-and-moon > .sun-beams {
            stroke: var(--icon-fill, currentColor);
            stroke-width: 2px;
        }

        .theme-toggle:is(:hover, :focus-visible) .sun-and-moon > .sun-beams {
            stroke: var(--accent);
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


        /* Modal Popup Anfang -- zentriertes Overlay */
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
        /* Modal Popup Ende */


        /* Sonstiges Responsive Anfang */
        @media (max-width: 600px) {
            .shell {
                padding: 1.5rem 1rem 2.5rem;
            }

            .card {
                padding: 1.25rem 1.3rem;
            }
        }
        /* Sonstiges Responsive Ende */

	/* Input-Felder & Dropdown zentrieren Anfang*/
	.create-user-form input[type="text"],
	.create-user-form input[type="password"],
	.create-user-form select {
	    width: 80%;
	    max-width: 240px;
	    margin: 0 auto;           /* zentriert das Feld */
	    display: block;           /* damit margin: auto wirkt */
	}

	.create-user-form .form-row {
	    display: flex;
	    flex-direction: column;
	    align-items: center;     /* zentriert Input UND Label-Bereich */
	    width: 100%;
	    margin-bottom: 0.75rem;
	}

	.create-user-form .form-row label {
	    width: 80%;
	    max-width: 240px;
	    margin: 0 auto 0.25rem auto;
	    text-align: left;
	}
	/* Input-Felder & Dropdown zentrieren Ende*/
    </style>

    <script>
    //Theme-Toggle Anfang -- Farbschema speichern und anwenden
    const storageKey = 'culdria-theme';

    const getColorPreference = () => {
      const stored = localStorage.getItem(storageKey);
      if (stored === 'light' || stored === 'dark') return stored;

      return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
    };

    const theme = {
      value: getColorPreference(),
    };

    const reflectPreference = () => {
      document.firstElementChild.setAttribute('data-theme', theme.value);
      document
        .querySelector('#theme-toggle')
        ?.setAttribute('aria-label', theme.value);
    };

    const setPreference = () => {
      localStorage.setItem(storageKey, theme.value);
      reflectPreference();
    };

    const onClick = () => {
      theme.value = theme.value === 'light' ? 'dark' : 'light';
      setPreference();
    };

    reflectPreference();

    window.addEventListener('load', () => {
      reflectPreference();
      const btn = document.querySelector('#theme-toggle');
      if (btn) {
        btn.addEventListener('click', onClick);
      }

      //Modal-Steuerung Anfang -- QR-URL-Modal öffnen und schließen
      (function () {
          const editButton   = document.getElementById('qr-url-edit-button');
          const modal        = document.getElementById('qr-url-modal');
          const closeButton  = document.getElementById('qr-url-modal-close');
          const cancelButton = document.getElementById('qr-url-modal-cancel');
          const backdrop     = modal ? modal.querySelector('.modal-backdrop') : null;

          if (!editButton || !modal) {
              return;
          }

          function openModal() {
              modal.hidden = false;
              const input = modal.querySelector('#qr_target_url');
              if (input) {
                  input.focus();
                  input.select();
              }
          }

          function closeModal() {
              modal.hidden = true;
          }

          editButton.addEventListener('click', openModal);

          if (closeButton) {
              closeButton.addEventListener('click', closeModal);
          }

          if (cancelButton) {
              cancelButton.addEventListener('click', closeModal);
          }

          if (backdrop) {
              backdrop.addEventListener('click', closeModal);
          }

          document.addEventListener('keydown', function (event) {
              if (event.key === 'Escape' && !modal.hidden) {
                  closeModal();
              }
          });
      })();
      //Modal-Steuerung Ende
    });

    window
      .matchMedia('(prefers-color-scheme: dark)')
      .addEventListener('change', ({matches: isDark}) => {
        theme.value = isDark ? 'dark' : 'light';
        setPreference();
      });
    //Theme-Toggle Ende
    </script>
</head>

<body>
<div class="shell">
    <header>
        <div>
            <a href="/dashboard.php" class="back-link">← Zurück zum Dashboard</a>
            <h1>Benutzerverwaltung</h1>
        </div>

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
    </header>

    <!-- Obere Zeile: Benutzer anlegen & QR-Generation -->
    <div class="top-grid">
        <!-- Card: Benutzer anlegen -->
        <div class="card card-create-user">
            <div class="card-header">
                <div class="card-title">Neuen Benutzer anlegen</div>
            </div>
			<br>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="error">
                        <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                <?php elseif ($success): ?>
                    <div class="success">
                        <?php echo htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/admin_users.php" class="create-user-form">
                    <input type="hidden" name="action" value="create_user">

                    <div class="form-row">
                        <label for="new_username">Benutzername:</label>
                        <input type="text" id="new_username" name="new_username" required>
                    </div>

                    <div class="form-row">
                        <label for="new_password">Startpasswort:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-row">
                        <label for="new_password_confirm">Startpasswort (Wiederholung):</label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" required>
                    </div>

                    <div class="form-row">
                        <label for="new_role">Rolle:</label>
                        <select id="new_role" name="new_role">
                            <option value="owner">Owner</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Benutzer anlegen</button>
                </form>
            </div>
        </div>

        <!-- Card: QR-Generation -->
        <div class="card card-qr">
            <div class="card-header">
                <div class="card-title">QR-Generation</div>
                <div class="card-subtitle">Ziel-URL und Code-Verwaltung</div>
            </div>

		<br>

            <div class="card-body">
                <?php if (!empty($qrMessage)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($qrMessageType, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($qrMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

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
				<br>
                    <div class="qr-actions">
                        <form method="post" class="qr-regenerate-form">
                            <input type="hidden" name="qr_action" value="regenerate_qr">
                            <button type="submit" class="btn btn-primary">
                                QR-Code neu generieren
                            </button>
                        </form>
				<br><br>
                        <div class="qr-url-row">
                            <div class="qr-url-value">
                                <?php echo htmlspecialchars($currentQrUrl, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <button type="button"
                                    class="btn btn-primary"
                                    id="qr-url-edit-button">
                                URL ändern
                            </button>
                        </div>

                        <p class="qr-description">
                            Diese URL wird von <code>join.php</code> als Ziel verwendet. Wenn sich Domain
                            oder Weiterleitung ändern, kannst du die Ziel-URL hier anpassen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unterer Bereich: Benutzerliste -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Benutzer & Passwort-Reset</div>
        </div>

        <div class="card-body">
            <table class="user-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Benutzername</th>
                    <th>Rolle</th>
                    <th>Erzw. PW-Wechsel</th>
                    <th>Passwort zurücksetzen</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                        <td><?php echo ((int)$u['must_change_password'] === 1) ? 'Ja' : 'Nein'; ?></td>
                        <td>
                            <?php if ((int)$u['id'] === (int)$user['id']): ?>
                                <em>Eigenes Passwort bitte über
                                    <a href="/change_password.php" class="link-button">Passwort ändern</a>.
                                </em>
                            <?php else: ?>
                                <form method="post" action="/admin_users.php" class="user-reset-form">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                    <input type="password"
                                           name="temp_password"
                                           placeholder="Neues PW"
                                           required
                                           class="inline-input">
                                    <button type="submit" class="btn btn-primary">Setzen</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: QR-URL bearbeiten -->
<div class="modal" id="qr-url-modal" hidden>
    <div class="modal-backdrop"></div>
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>QR-Ziel-URL bearbeiten</h3>
            <button type="button" class="modal-close" id="qr-url-modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="qr_action" value="update_url">

                <label for="qr_target_url">Ziel-URL des QR-Codes</label>
                <input
                    type="url"
                    id="qr_target_url"
                    name="qr_target_url"
                    required
                    value="<?php echo htmlspecialchars($currentQrUrl, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <p class="hint">
                    Beispiel: <code>https://culdria.framenode.net/join.php</code>
                </p>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="qr-url-modal-cancel">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
