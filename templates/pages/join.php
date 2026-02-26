<?php
declare(strict_types=1);

require __DIR__ . '/invite_settings.php';

// Aktuelle Invite-URL aus der settings-Tabelle holen
$inviteUrl = get_setting('invite_url');

// Wenn nichts gesetzt ist → einfache Fehlermeldung ausgeben
if (empty($inviteUrl)) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Culdria Invite – nicht konfiguriert</title>
    </head>
    <body>
        <h1>Invite derzeit nicht verfügbar</h1>
        <p>Es ist aktuell keine Invite-URL konfiguriert.</p>
    </body>
    </html>
    <?php
    exit;
}

// Wenn vorhanden → HTTP-Redirect auf die Discord-Invite-URL
header('Location: ' . $inviteUrl, true, 302);
exit;
