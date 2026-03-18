<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/qr-generator.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fgHex = $input['fg'] ?? '#000000';
$bgHex = $input['bg'] ?? '#FFFFFF';
$logo  = null;

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

$tmpDir = __DIR__ . '/../../../data/tmp/';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0750, true);

$stamp   = 'preview_' . uniqid() . '_' . time();
$pngFile = $tmpDir . $stamp . '.png';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

$fg = hexToColor($fgHex);
$bg = hexToColor($bgHex);

$builder = new Builder(
    writer: new PngWriter(),
    data: 'https://qr.framenode.net/join/',
    encoding: new Encoding('UTF-8'),
    errorCorrectionLevel: ErrorCorrectionLevel::High,
    size: 400,
    margin: 10,
    foregroundColor: $fg,
    backgroundColor: $bg,
    roundBlockSizeMode: RoundBlockSizeMode::Margin,
    logoPath: ($logo && is_file($logo)) ? $logo : '',
    logoResizeToWidth: ($logo && is_file($logo)) ? 80 : null,
);

file_put_contents($pngFile, $builder->build()->getString());

$base64 = base64_encode(file_get_contents($pngFile));
@unlink($pngFile);

echo json_encode([
    'preview' => 'data:image/png;base64,' . $base64,
    'fg'      => $fgHex,
    'bg'      => $bgHex,
]);
