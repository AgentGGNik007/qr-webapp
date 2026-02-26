<?php
declare(strict_types=1);

$config = [
    'db_path'          => __DIR__ . '/../data/users.sqlite',

    'shlink_base_url'  => 'http://127.0.0.1:8081/rest/v3',
    'shlink_api_key'   => null,
    'short_code'       => 'join',

    'base_url'         => 'https://culdria.framenode.net',
    'session_name'     => 'culdria_invite_session',
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    $config = array_merge($config, $local);
}

return $config;
