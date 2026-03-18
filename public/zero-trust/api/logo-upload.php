<?php
declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['logo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Keine Datei übermittelt']);
    exit;
}

$file     = $_FILES['logo'];
$allowed  = ['image/png', 'image/svg+xml'];
$maxSize  = 2 * 1024 * 1024; // 2MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload fehlgeschlagen']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nur PNG und SVG erlaubt']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Datei darf maximal 2MB groß sein']);
    exit;
}

$tmpDir = __DIR__ . '/../../data/uploads/';
$tmpName = 'logo_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . ($mime === 'image/svg+xml' ? 'svg' : 'png');
$tmpPath = $tmpDir . $tmpName;

if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
    exit;
}

echo json_encode([
    'success'   => true,
    'tmp_name'  => $tmpName,
]);
