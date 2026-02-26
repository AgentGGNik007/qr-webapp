if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}


<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';

$dbPath = $config['db_path'];
$dir = dirname($dbPath);

if (!is_dir($dir)) {
    mkdir($dir, 0750, true);
}

// SQLite-Verbindung
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tabelle für Benutzer
$db->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL,
    must_change_password INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL
)');

// Admin-Benutzer anlegen (Standard-Login, muss Passwort ändern)
$username       = 'admin';
$passwordPlain = getenv('INIT_ADMIN_PASSWORD') ?: 'CHANGE_ME';
$passwordHash   = password_hash($passwordPlain, PASSWORD_DEFAULT);

$stmt = $db->prepare(
    'INSERT OR IGNORE INTO users (username, password_hash, role, must_change_password, created_at)
     VALUES (:u, :p, :r, :m, :c)'
);

$stmt->execute([
    ':u' => $username,
    ':p' => $passwordHash,
    ':r' => 'admin',
    ':m' => 1,
    ':c' => date('c'),
]);

echo "Admin-Benutzer wurde (falls noch nicht vorhanden) angelegt.\n";
echo "Login:  {$username}\n";
echo "Passwort: {$passwordPlain}\n";

