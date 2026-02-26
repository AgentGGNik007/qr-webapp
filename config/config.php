<?php
declare(strict_types=1);

return [
    // SQLite-Datei für Benutzer
    'db_path'          => __DIR__ . '/../data/users.sqlite',

    // Shlink-Anbindung
    'shlink_base_url'  => 'http://127.0.0.1:8081/rest/v3',
    'shlink_api_key'   => 'culdria-change-me-please',
    'short_code'       => 'join',

    // Allgemeines
    'base_url'         => 'https://culdria.framenode.net',
    'session_name'     => 'culdria_invite_session',
];
