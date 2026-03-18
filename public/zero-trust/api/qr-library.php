<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/qr-generator.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$library = getQrLibrary();

echo json_encode([
    'success' => true,
    'items'   => $library,
    'count'   => count($library),
]);
