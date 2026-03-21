<?php
declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$logoName = basename($input['logo_name'] ?? '');

if (empty($logoName) || !preg_match('/^logo_[0-9a-f]+_[0-9]+\.(png|svg)$/', $logoName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiger Dateiname']);
    exit;
}

$uploadsDir = __DIR__ . '/../../../data/uploads/';
$inputPath  = $uploadsDir . $logoName;

if (!is_file($inputPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Datei nicht gefunden']);
    exit;
}

// Nur PNG unterstützt
if (!str_ends_with($logoName, '.png')) {
    http_response_code(400);
    echo json_encode(['error' => 'Nur PNG wird unterstützt']);
    exit;
}

// Ausgabedatei
$outName = 'logo_' . bin2hex(random_bytes(8)) . '_' . time() . '.png';
$outPath = $uploadsDir . $outName;

// rembg aufrufen
$cmd    = escapeshellcmd('python3') . ' -c ' . escapeshellarg('
from rembg import remove
with open("' . $inputPath . '", "rb") as f:
    data = f.read()
result = remove(data)
with open("' . $outPath . '", "wb") as f:
    f.write(result)
print("ok")
') . ' 2>&1';

$output = shell_exec($cmd);

if (!is_file($outPath)) {
    error_log('rembg error: ' . $output);
    http_response_code(500);
    echo json_encode(['error' => 'Hintergrundentfernung fehlgeschlagen']);
    exit;
}

// Alte Datei löschen
@unlink($inputPath);

echo json_encode([
    'success'  => true,
    'tmp_name' => $outName,
]);
