<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/qr-generator.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$fgHex  = $input['fg']       ?? '#000000';
$bgHex  = $input['bg']       ?? '#FFFFFF';
$logoX  = (float)($input['logo_x']   ?? 0.5);
$logoY  = (float)($input['logo_y']   ?? 0.5);
$logoSz = (int)($input['logo_size']  ?? 40);
$logoSz = max(20, min(80, $logoSz));
$logo   = null;

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $fgHex) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $bgHex)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige Farbwerte']);
    exit;
}

if (!empty($input['logo_name'])) {
    $logoName = basename($input['logo_name']);
    $logoPath = __DIR__ . '/../../../data/uploads/' . $logoName;
    if (is_file($logoPath) && preg_match('/^logo_[0-9a-f]+_[0-9]+\.(png|svg)$/', $logoName)) {
        $logo = $logoPath;
    }
}

try {
    $result = generateQrCode(
        $_ENV['SHLINK_SHORT_URL'] ?? '',
        $fgHex,
        $bgHex,
        $logo,
        $logoX,
        $logoY,
        $logoSz
    );

    // Logo aufräumen
    if ($logo && is_file($logo)) @unlink($logo);

    echo json_encode([
        'success' => true,
        'png'     => $result['png'],
        'svg'     => $result['svg'],
        'stamp'   => $result['stamp'],
    ]);
} catch (Throwable $e) {
    error_log('QR save error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'QR-Code konnte nicht gespeichert werden']);
}
