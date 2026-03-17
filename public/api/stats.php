<?php
declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$apiKey   = 'webapp-api-key-version-0-0-0';
$baseUrl  = 'http://localhost:8081/rest/v3';
$slug     = 'join';

function shlinkGet(string $url, string $apiKey): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-Api-Key: ' . $apiKey],
        CURLOPT_TIMEOUT        => 5,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

$mode      = $_GET['mode']   ?? 'month';
$offset    = (int)($_GET['offset'] ?? 0);

// Zeitraum berechnen
$now = new DateTime('now', new DateTimeZone('Europe/Berlin'));

if ($mode === 'week') {
    $start = (clone $now)->modify($offset . ' weeks')->modify('monday this week')->setTime(0,0,0);
    $end   = (clone $start)->modify('+6 days')->setTime(23,59,59);
} else {
    $start = (clone $now)->modify($offset . ' months')->modify('first day of this month')->setTime(0,0,0);
    $end   = (clone $now)->modify($offset . ' months')->modify('last day of this month')->setTime(23,59,59);
}

// Gesamt-Visits
$summary = shlinkGet($baseUrl . '/short-urls/' . $slug, $apiKey);
$total   = $summary['visitsSummary']['nonBots'] ?? 0;

// Visits im Zeitraum
$visitsUrl = $baseUrl . '/short-urls/' . $slug . '/visits'
    . '?startDate=' . urlencode($start->format('Y-m-d\TH:i:sP'))
    . '&endDate='   . urlencode($end->format('Y-m-d\TH:i:sP'))
    . '&excludeBots=true'
    . '&itemsPerPage=1000';

$visitsData = shlinkGet($visitsUrl, $apiKey);
$visits     = $visitsData['visits']['data'] ?? [];

// Heute
$todayStr  = $now->format('Y-m-d');
$todayCount = 0;

// Daten pro Tag/Woche aggregieren
$buckets = [];

if ($mode === 'week') {
    for ($i = 0; $i < 7; $i++) {
        $day = (clone $start)->modify('+' . $i . ' days')->format('Y-m-d');
        $buckets[$day] = 0;
    }
} else {
    $daysInMonth = (int)$end->format('d');
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $buckets[str_pad((string)$i, 2, '0', STR_PAD_LEFT)] = 0;
    }
}

foreach ($visits as $visit) {
    $date = substr($visit['date'], 0, 10);
    if ($date === $todayStr) $todayCount++;
    if ($mode === 'week') {
        if (isset($buckets[$date])) $buckets[$date]++;
    } else {
        $day = substr($date, 8, 2);
        // führende Null entfernen für Label
        $key = str_pad($day, 2, '0', STR_PAD_LEFT);
        if (isset($buckets[$key])) $buckets[$key]++;
    }
}

// Labels und Werte
$labels = [];
$values = [];

if ($mode === 'week') {
    $dayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    $i = 0;
    foreach ($buckets as $date => $count) {
        $labels[] = $dayNames[$i++] . ' ' . substr($date, 8);
        $values[] = $count;
    }
} else {
    foreach ($buckets as $day => $count) {
        $labels[] = ltrim($day, '0') ?: '0';
        $values[] = $count;
    }
}

echo json_encode([
    'total'       => $total,
    'period'      => array_sum($values),
    'today'       => $todayCount,
    'labels'      => $labels,
    'values'      => $values,
]);
