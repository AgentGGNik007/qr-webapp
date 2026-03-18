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

$lastSent = getConfig('join_error_mail_last_sent');
$now      = time();

if (empty($lastSent) || ($now - (int)$lastSent) >= 3600) {
    sendMail(
        'Fehler: Discord Invite Link nicht erreichbar',
        "Der Discord Invite Link ist nicht erreichbar.\n\n" .
        "URL: " . $url . "\n" .
        "Zeitpunkt: " . date('d.m.Y H:i:s') . "\n\n" .
        "Bitte Link im Dashboard aktualisieren:\n" .
        "https://qr.framenode.net/zero-trust/dashboard/"
    );
    setConfig('join_error_mail_last_sent', (string)$now);
}

$globalVer = is_file(__DIR__ . '/../../public/assets/css/global.css') ? (string) filemtime(__DIR__ . '/../../public/assets/css/global.css') : (string) time();
$errorVer  = is_file(__DIR__ . '/../../public/assets/css/error.css')  ? (string) filemtime(__DIR__ . '/../../public/assets/css/error.css')  : (string) time();
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Link nicht verfügbar</title>
  <link rel="stylesheet" href="/assets/css/global.css?v=<?= htmlspecialchars($globalVer, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="/assets/css/error.css?v=<?= htmlspecialchars($errorVer, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="page-centered">
  <div class="error-wrap">
    <div class="card text-center">
      <div class="card-body">
        <p class="error-icon">⚠️</p>
        <h1 class="card-title error-title">Einladungslink nicht verfügbar</h1>
        <p class="error-text">
          Der Discord-Einladungslink ist momentan nicht erreichbar.<br>
          Bitte versuche es später erneut.
        </p>
      </div>
    </div>
  </div>
</body>
</html>
