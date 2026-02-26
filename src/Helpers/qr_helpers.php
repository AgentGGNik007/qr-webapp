<?php
declare(strict_types=1);

// Composer-Autoloader einbinden
require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

// Pfade zu den QR-Dateien
function getQrPaths(): array
{
    // wir sind in /public -> QR-Dateien liegen in /public/qr
    $base = __DIR__ . '/qr';

    return [
        'png' => $base . '/culdria-qr.png',
        'svg' => $base . '/culdria-qr.svg',
    ];
}

// Öffentliche URLs
function getQrPublicUrls(): array
{
    return [
        'png' => '/qr/culdria-qr.png',
        'svg' => '/qr/culdria-qr.svg',
    ];
}

// QR neu generieren und als Dateien speichern
function generateQrCodeToFiles(string $url): bool
{
    $paths = getQrPaths();

    // PNG erzeugen
    $pngBuilder = new Builder();
    $pngResult = $pngBuilder->build(
        writer: new PngWriter(),
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 1024,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
    );
    $pngResult->saveToFile($paths['png']);

    // SVG erzeugen
    $svgBuilder = new Builder();
    $svgResult = $svgBuilder->build(
        writer: new SvgWriter(),
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 1024,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
    );
    $svgResult->saveToFile($paths['svg']);

    return true;
}
