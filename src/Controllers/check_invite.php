<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
session_name($config['session_name']);
session_start();

if (empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

require __DIR__ . '/invite_settings.php';

$url = get_setting('invite_url');
$status = 'lost';
$httpCode = 0;

if (!empty($url)) {

    // Invite-Code extrahieren
    $parts = explode('/', trim($url));
    $inviteCode = end($parts);

    if (!empty($inviteCode)) {

        // Discord Invite API URL
        $apiUrl = "https://discord.com/api/v9/invites/" . urlencode($inviteCode) . "?with_counts=true&with_expiration=true";

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT      => 'CuldriaInviteChecker/2.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // API gibt JSON zurück
        if ($response !== false && $httpCode === 200) {
            $data = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {

                // Fehlercode für ungültige Invites
                if (!isset($data['code']) || $data['code'] != 10006) {
                    // Guild vorhanden = gültiger Invite
                    if (isset($data['guild'])) {
                        $status = 'complete';
                    }
                }
            }
        }
    }
}

set_setting('invite_status', $status);
set_setting('invite_last_check', date('c'));
set_setting('invite_last_http_code', (string)$httpCode);

if ($istErreichbar) {
    $_SESSION['invite_flash'] = [
        'type'    => 'success',
        'message' => 'Invite-URL ist erreichbar.'
    ];
} else {
    $_SESSION['invite_flash'] = [
        'type'    => 'error',
        'message' => 'Invite-URL ist derzeit nicht erreichbar.'
    ];
}

header('Location: /dashboard.php');
exit;
