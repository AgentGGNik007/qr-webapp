<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$db   = getDB();
$date = date('Y-m-d');

// Neuen Tag eintragen
$stmt = $db->prepare("INSERT OR IGNORE INTO tracking_days (date) VALUES (?)");
$stmt->execute([$date]);

// Einträge löschen deren Monat mehr als 24 Monate zurückliegt
// Beispiel: am 1.3.2028 wird der gesamte März 2026 gelöscht
$cutoffMonth = date('Y-m', strtotime('-24 months'));
$db->prepare("DELETE FROM tracking_days WHERE strftime('%Y-%m', date) < ?")->execute([$cutoffMonth]);
