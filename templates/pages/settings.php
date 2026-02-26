<?php
require_once __DIR__ . '/db.php';

function get_setting($key) {
    global $db;
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = :key LIMIT 1");
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    return $result['value'] ?? null;
}

function set_setting($key, $value) {
    global $db;
    $stmt = $db->prepare("
        INSERT INTO settings (key, value, updated_at)
        VALUES (:key, :value, CURRENT_TIMESTAMP)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->bindValue(':value', $value, SQLITE3_TEXT);
    return $stmt->execute();
}
