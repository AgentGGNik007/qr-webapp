<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/invite-check.php';
require_once __DIR__ . '/../includes/mailer.php';

$url = getConfig('discord_invite_url');

if (empty($url)) {
    exit(0);
}

if (!checkInviteUrl($url)) {
    sendMail(
        'Warnung: Discord Invite Link nicht erreichbar',
        "Die tägliche Überprüfung hat ergeben dass der Discord Invite Link nicht erreichbar ist.\n\n" .
        "URL: " . $url . "\n" .
        "Zeitpunkt: " . date('d.m.Y H:i:s') . "\n\n" .
        "Bitte Link im Dashboard aktualisieren:\n" .
        "https://qr.framenode.net/dashboard/"
    );
}
