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
    "https://qr.framenode.net/dashboard/"
);

// Fehlerseite anzeigen
$title       = 'Link nicht verfügbar';
$headerTitle = 'Link nicht verfügbar';
$showFooterThemeMenu = true;
$footerLinks = [];
require __DIR__ . '/../../includes/head.php';
?>

<section class="card" style="max-width: 600px; margin: 2rem auto; text-align: center;">
  <div class="card-body">
    <p style="font-size: 2rem; margin-bottom: 1rem;">⚠️</p>
    <h2 class="card-title" style="margin-bottom: 0.75rem;">Einladungslink nicht verfügbar</h2>
    <p style="color: var(--text-soft);">
      Der Discord-Einladungslink ist momentan nicht erreichbar.<br>
      Bitte versuche es später erneut.
    </p>
  </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
