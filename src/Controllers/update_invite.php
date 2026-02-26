<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
session_name($config['session_name']);
session_start();

// Nur eingeloggte Nutzer
if (empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

// Hilfsfunktionen für settings-Tabelle
require __DIR__ . '/invite_settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['invite_url'] ?? '');

    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
        set_setting('invite_url', $url);
    }
}

header('Location: /dashboard.php');
exit;
