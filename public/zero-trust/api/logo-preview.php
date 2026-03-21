<?php
declare(strict_types=1);

$logoName = basename($_GET['name'] ?? '');

if (empty($logoName) || !preg_match('/^logo_[0-9a-f]+_[0-9]+\.(png|svg)$/', $logoName)) {
    http_response_code(400);
    exit;
}

$path = __DIR__ . '/../../../data/uploads/' . $logoName;

if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($path);
header('Content-Type: ' . $mime);
header('Cache-Control: no-store');
readfile($path);
