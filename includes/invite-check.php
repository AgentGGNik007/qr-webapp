<?php
declare(strict_types=1);

function checkInviteUrl(string $url): bool {
    if (empty($url)) return false;

    // Invite-Code aus URL extrahieren
    if (!preg_match('#discord\.gg/([a-zA-Z0-9-]+)$#', $url, $matches)) {
        return false;
    }
    $code = $matches[1];

    // Discord API abfragen
    $ch = curl_init('https://discord.com/api/v9/invites/' . $code);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; FramenodeQR/1.0)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $res  = curl_exec($ch);
    $code_http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code_http !== 200 || empty($res)) return false;

    $data    = json_decode($res, true);
    $guildId = $data['guild']['id'] ?? null;

    $expectedGuildId = $_ENV['DISCORD_GUILD_ID'] ?? '';

    return $guildId === $expectedGuildId;
}
