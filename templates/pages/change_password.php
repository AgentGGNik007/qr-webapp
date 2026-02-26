<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

session_name($config['session_name']);
session_start();

if (empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

$user = $_SESSION['user'];

// Sicherer Backlink (wird von dashboard/admin_users gesetzt)
$backUrl = $_SESSION['return_to_change_pw'] ?? '/dashboard.php';

$error = null;

// Passwort ändern – Logik UNVERÄNDERT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new1    = $_POST['new_password'] ?? '';
    $new2    = $_POST['new_password_confirm'] ?? '';

    if ($new1 === '' || $new2 === '') {
        $error = 'Neues Passwort darf nicht leer sein.';
    } elseif ($new1 !== $new2) {
        $error = 'Die neuen Passwörter stimmen nicht überein.';
    } else {
        try {
            $db = new PDO('sqlite:' . $config['db_path']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $user['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current, $row['password_hash'])) {
                $error = 'Aktuelles Passwort ist falsch.';
            } else {
                $newHash = password_hash($new1, PASSWORD_DEFAULT);

                $upd = $db->prepare(
                    'UPDATE users SET password_hash = :p, must_change_password = 0 WHERE id = :id'
                );
                $upd->execute([
                    ':p'  => $newHash,
                    ':id' => $user['id'],
                ]);

                $_SESSION['user']['must_change_password'] = false;

                header('Location: ' . $backUrl);
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Fehler beim Speichern des neuen Passworts.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Passwort ändern – Culdria Invite</title>

    <style>
        @import "https://unpkg.com/open-props/easings.min.css";

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
            --icon-fill-hover: var(--accent);
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

        html[data-theme="dark"] body {
            background-color: var(--bg-dark);
            color: var(--fg-dark);
        }

        html[data-theme="light"] .card {
            background-color: var(--card-light);
        }

        html[data-theme="dark"] .card {
            background-color: var(--card-dark);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, sans-serif;

            display: flex;
            justify-content: center;
            align-items: center;

            height: 100vh;
        }

        .shell {
            width: 100%;
            max-width: 450px;
            padding: 1.5rem;

            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
        }

        /* Top-Zeile: Backlink links, Switch rechts – gleiche Position wie vorher */
        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-link {
            font-size: 0.9rem;
            color: var(--accent);
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            border-radius: 0.75rem;
            padding: 1.5rem 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.35);
        }

        h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.0rem;
        }

        .meta {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: -0.3rem;
            margin-bottom: 1.1rem;
        }

        label {
            display: block;
            text-align: center;
            margin-bottom: 0.35rem;
            font-size: 1.0rem;
        }

        input[type="password"] {
            width: 60%;
            max-width: 260px;
            margin: 0.5rem auto 0.9rem auto;
            display: block;
            padding: 0.5rem 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: transparent;
            color: inherit;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
        }

        button[type="submit"] {
            margin-top: 0.75rem;
            width: 50%;
            padding: 0.6rem 0.8rem;
            border-radius: 0.5rem;
            border: none;
            background: var(--accent);
            color: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        html[data-theme="light"] button[type="submit"] {
            color: #e5e7eb;
        }

        html[data-theme="dark"] button[type="submit"] {
            color: #0f172a;
        }

        button[type="submit"]:hover {
            opacity: 0.9;
        }

        .error {
            color: #f97373;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        /* Theme-Switch Icon-Styles (Sun & Moon) */
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
    </style>

    <script>
    // Theme-Toggle wie auf den anderen Seiten
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

    window.onload = () => {
      reflectPreference();
      const btn = document.querySelector('#theme-toggle');
      if (btn) {
        btn.addEventListener('click', onClick);
      }
    };

    window
      .matchMedia('(prefers-color-scheme: dark)')
      .addEventListener('change', ({matches: isDark}) => {
        theme.value = isDark ? 'dark' : 'light';
        setPreference();
      });
    </script>
</head>

<body>
<div class="shell">
    <div class="top-row">
        <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="back-link">
            ← Zurück
        </a>

        <button class="theme-toggle" id="theme-toggle" title="Toggles light & dark" aria-label="auto" aria-live="polite">
            <svg class="sun-and-moon" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24">
                <mask class="moon" id="moon-mask">
                    <rect x="0" y="0" width="100%" height="100%" fill="white" />
                    <circle cx="24" cy="10" r="6" fill="black" />
                </mask>
                <circle class="sun" cx="12" cy="12" r="6" mask="url(#moon-mask)" fill="currentColor" />
                <g class="sun-beams" stroke="currentColor">
                    <line x1="12" y1="1"  x2="12" y2="3" />
                    <line x1="12" y1="21" x2="12" y2="23" />
                    <line x1="4.22" y1="4.22"  x2="5.64" y2="5.64" />
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                    <line x1="1"    y1="12"    x2="3"    y2="12" />
                    <line x1="21"   y1="12"    x2="23"   y2="12" />
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                </g>
            </svg>
        </button>
    </div>

    <div class="card">
        <h1>Passwort ändern</h1>
        <div class="meta">
            Eingeloggt als
            <strong><?php echo htmlspecialchars($user['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/change_password.php">
            <label for="current_password">Aktuelles Passwort:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">Neues Passwort:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="new_password_confirm">Neues Passwort (Wiederholung):</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" required>

            <button type="submit">Passwort speichern</button>
        </form>
    </div>
</div>
</body>
</html>
