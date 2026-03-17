<?php
declare(strict_types=1);

function checkInviteUrl(string $url): bool {
    if (empty($url)) return false;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_NOBODY         => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; FramenodeQR/1.0)',
    ]);
    curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 200, 301, 302 gelten als erreichbar
    return in_array($httpCode, [200, 301, 302], true);
}
