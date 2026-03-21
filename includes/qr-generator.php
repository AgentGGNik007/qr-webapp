<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

function generateQrCode(
    string $url,
    string $fgHex = '#000000',
    string $bgHex = '#FFFFFF',
    ?string $logoPath = null,
    float $logoX = 0.5,
    float $logoY = 0.5,
    int $logoSize = 40
): array {
    $qrDir   = __DIR__ . '/../public/assets/qr/';
    $stamp   = date('Ymd_His');
    $pngFile = $qrDir . 'qr_' . $stamp . '.png';
    $svgFile = $qrDir . 'qr_' . $stamp . '.svg';

    $fg = hexToColor($fgHex);
    $bg = hexToColor($bgHex);

    // PNG
    $pngBuilder = new Builder(
        writer: new PngWriter(),
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 400,
        margin: 10,
        foregroundColor: $fg,
        backgroundColor: $bg,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
    );

    file_put_contents($pngFile, $pngBuilder->build()->getString());

    // Logo per GD an gewünschter Position einzeichnen
    if ($logoPath && is_file($logoPath)) {
        $qrImg   = imagecreatefrompng($pngFile);
        $qrW     = imagesx($qrImg);
        $logoImg = null;
        $mime    = mime_content_type($logoPath);
        if ($mime === 'image/png') $logoImg = imagecreatefrompng($logoPath);
        if ($logoImg) {
            $cx   = (int)($logoX * $qrW);
            $cy   = (int)($logoY * $qrW);
            $half = (int)($logoSize / 2);
            $srcW = imagesx($logoImg);
            $srcH = imagesy($logoImg);
            imagecopyresampled($qrImg, $logoImg, $cx - $half, $cy - $half, 0, 0, $logoSize, $logoSize, $srcW, $srcH);
            imagedestroy($logoImg);
        }
        imagepng($qrImg, $pngFile);
        imagedestroy($qrImg);
    }

    // SVG (kein Logo-Support)
    $svgBuilder = new Builder(
        writer: new SvgWriter(),
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 400,
        margin: 10,
        foregroundColor: $fg,
        backgroundColor: $bg,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
    );

    file_put_contents($svgFile, $svgBuilder->build()->getString());

    pruneQrLibrary($qrDir);

    return [
        'png'   => '/assets/qr/qr_' . $stamp . '.png',
        'svg'   => '/assets/qr/qr_' . $stamp . '.svg',
        'stamp' => $stamp,
    ];
}

function hexToColor(string $hex): Color {
    $hex = ltrim($hex, '#');
    return new Color(
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2))
    );
}

function pruneQrLibrary(string $qrDir): void {
    $pngs = glob($qrDir . 'qr_*.png');
    if (!$pngs) return;
    rsort($pngs);
    $keep = array_slice($pngs, 0, 10);
    foreach ($pngs as $file) {
        if (!in_array($file, $keep, true)) {
            $base = substr($file, 0, -4);
            @unlink($file);
            @unlink($base . '.svg');
        }
    }
}

function getQrLibrary(): array {
    $qrDir = __DIR__ . '/../public/assets/qr/';
    $pngs  = glob($qrDir . 'qr_*.png');
    if (!$pngs) return [];
    rsort($pngs);
    $result = [];
    foreach (array_slice($pngs, 0, 10) as $file) {
        $name  = basename($file, '.png');
        $stamp = substr($name, 3);
        $dt    = DateTime::createFromFormat('Ymd_His', $stamp);
        $result[] = [
            'png'   => '/assets/qr/' . $name . '.png',
            'svg'   => '/assets/qr/' . $name . '.svg',
            'label' => $dt ? $dt->format('d.m.Y H:i:s') : $stamp,
        ];
    }
    return $result;
}

function getLatestQr(): ?array {
    $lib = getQrLibrary();
    return $lib[0] ?? null;
}
