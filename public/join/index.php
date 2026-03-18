<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/invite-check.php';
require_once __DIR__ . '/../../includes/mailer.php';

$url = getConfig('discord_invite_url');

if (checkInviteUrl($url)) {
    header('Location: ' . $url, true, 302);
    exit;
}

// URL nicht erreichbar – E-Mail senden
sendMail(
    'Fehler: Discord Invite Link nicht erreichbar',
    "Der Discord Invite Link ist nicht erreichbar.\n\n" .
    "URL: " . $url . "\n" .
    "Zeitpunkt: " . date('d.m.Y H:i:s') . "\n\n" .
    "Bitte Link im Dashboard aktualisieren:\n" .
    "https://qr.framenode.net/zero-trust/dashboard/"
);

$cssPathFs = __DIR__ . '/../../public/assets/css/app.css';
$cssVer    = is_file($cssPathFs) ? (string) filemtime($cssPathFs) : (string) time();
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Link nicht verfügbar</title>
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= htmlspecialchars($cssVer, ENT_QUOTES, 'UTF-8') ?>">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg);
      color: var(--text);
      font-family: system-ui, sans-serif;
    }
    .error-wrap {
      width: 100%;
      max-width: 420px;
      padding: 1.5rem;
      box-sizing: border-box;
    }
  </style>
</head>
<body>
  <div class="error-wrap">
    <div class="card" style="text-align:center;">
      <div class="card-body">
        <p style="font-size:2.5rem; margin:0 0 0.75rem 0;">⚠️</p>
        <h1 class="card-title" style="font-size:1.2rem; margin-bottom:0.6rem;">Einladungslink nicht verfügbar</h1>
        <p style="color:var(--text-soft); margin:0; font-size:0.95rem; line-height:1.5;">
          Der Discord-Einladungslink ist momentan nicht erreichbar.<br>
          Bitte versuche es später erneut.
        </p>
      </div>
    </div>
  </div>
</body>
</html>
