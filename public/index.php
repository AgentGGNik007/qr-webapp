<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

session_name($config['session_name']);
session_start();

// Wenn schon eingeloggt → weiterleiten
if (!empty($_SESSION['user'])) {
    if (!empty($_SESSION['user']['must_change_password'])) {
        header('Location: /change_password.php');
    } else {
        header('Location: /dashboard.php');
    }
    exit;
}

$error = null;

// Login-Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Bitte Benutzername und Passwort eingeben.';
    } else {
        try {
            $db = new PDO('sqlite:' . $config['db_path']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id'        => (int)$user['id'],
                    'username'  => $user['username'],
                    'role'      => $user['role'],
                    'must_change_password' => (int)$user['must_change_password'] === 1,
                ];

                if ((int)$user['must_change_password'] === 1) {
                    header('Location: /change_password.php');
                } else {
                    header('Location: /dashboard.php');
                }
                exit;
            } else {
                $error = 'Ungültiger Benutzername oder Passwort.';
            }
        } catch (Throwable $e) {
            $error = 'Interner Fehler bei der Anmeldung.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Culdria Invite – Login</title>

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
            --accent-dark: #C0C5F1;

            --accent: var(--accent-light);

            --icon-fill: currentColor;
            --icon-fill-hover: var(--accent); /* Hover jetzt Accent-Farbe */
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

        html[data-theme="light"] input[type="text"],
        html[data-theme="light"] input[type="password"] {
            background: #ffffff;                 /* Weiß statt transparent */
            color: #111827;                      /* dunkle Schrift */
            border: 1px solid rgba(0, 0, 0, 0.35);
        }

        html[data-theme="light"] input[type="text"]:focus,
        html[data-theme="light"] input[type="password"]:focus {
            border-color: #38bdf8;   /* Akzentblau */
            background: #f9fafb;     /* ganz leichtes Grau */
            outline: none;
        }

        html[data-theme="dark"] body {
            background-color: var(--bg-dark);
            color: var(--fg-dark);
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
            max-width: 420px;
            padding: 1.5rem;

            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
        }

        h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }

        .subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .card {
            border-radius: 0.75rem;
            padding: 1.5rem 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.35); /* an andere Seiten angeglichen */
        }

        label {
            display: block;
            text-align: center;
            margin-bottom: 0.35rem;
            font-size: 1.2rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 40%;              /* schmaler */
            max-width: 240px;
            margin: 0.5rem auto;
            display: block;
            padding: 0.5rem 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: transparent;
            color: inherit;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
        }

        button[type="submit"] {
            margin-top: 0.75rem;
            width: 30%;
            padding: 0.6rem 0.8rem;
            border-radius: 0.5rem;
            border: none;
            background: var(--accent);
            color: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        /* Light Mode: dunkler Accent → helle Schrift */
        html[data-theme="light"] button[type="submit"] {
            color: #e5e7eb;
        }

        /* Dark Mode: heller Accent → dunkle Schrift */
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

        .top-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Theme-Toggle */
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
    </div>

    <div class="card">

        <br>

        <h1 style="line-height: 1.2;">
            Culdria´s Chronicale´s<br>
            QR-Code Statistik
        </h1>
        <div class="subtitle">Admin & Owner Login</div>

        <br><br>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/index.php">
            <label for="username">Benutzername:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Passwort:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Anmelden</button>

            <br><br>

        </form>
    </div>
</div>
</body>
</html>
