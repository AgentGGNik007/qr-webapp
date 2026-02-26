<?php
declare(strict_types=1);

/**
 * Globale SQLite-Verbindung.
 * Quelle der Wahrheit: config/config.php -> db_path (=> /data/users.sqlite)
 */

$config = require __DIR__ . '/../config/config.php';
$dbPath = $config['db_path'] ?? null;

if (!$dbPath) {
    throw new Exception("db_path fehlt in config/config.php");
}
if (!file_exists($dbPath)) {
    throw new Exception("Datenbank nicht gefunden: $dbPath");
}

$db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);

// Optionale Einstellungen
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode = WAL;');
$db->exec('PRAGMA foreign_keys = ON;');
