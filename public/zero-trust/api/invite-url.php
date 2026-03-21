<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/invite-check.php';
require_once __DIR__ . '/../../../includes/mailer.php';

header('Content-Type: application/json');

// GET – aktuelle URL lesen
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $url       = getConfig('discord_invite_url');
    $reachable = $url ? checkInviteUrl($url) : false;
    if ($url && !$reachable) {
        sendInviteErrorMail($url);
    }
    echo json_encode([
        'url'       => $url,
        'reachable' => $reachable,
    ]);
    exit;
}

// POST – neue URL speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $url   = trim($input['url'] ?? '');

    if (empty($url)) {
        http_response_code(400);
        echo json_encode(['error' => 'URL darf nicht leer sein']);
        exit;
    }

    // Nur Discord Invite URLs erlauben
    if (!preg_match('#^https://discord\.gg/[a-zA-Z0-9-]+$#', $url)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Discord Invite URL']);
        exit;
    }

    // Erreichbarkeit prüfen
    if (!checkInviteUrl($url)) {
        http_response_code(422);
        echo json_encode(['error' => 'URL ist nicht erreichbar']);
        exit;
    }

    setConfig('discord_invite_url', $url);

    echo json_encode(['success' => true, 'url' => $url]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
