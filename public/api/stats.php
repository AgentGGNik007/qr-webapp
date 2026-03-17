<?php
declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';

$apiKey  = 'webapp-api-key-version-0-0-0';
$baseUrl = 'https://shlink.qr.framenode.net/rest/v3';
$slug    = 'join';

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

$mode   = $_GET['mode']   ?? 'month';
$offset = (int)($_GET['offset'] ?? 0);

$db = getDB();

// Ersten erfassten Tag ermitteln
$firstDay = $db->query("SELECT MIN(date) FROM tracking_days")->fetchColumn();
$firstDay = $firstDay ?: date('Y-m-d');

$now = new DateTime('now', new DateTimeZone('Europe/Berlin'));

// Zeitraum berechnen
if ($mode === 'week') {
    // Woche beginnt Sonntag, endet Samstag
    // Letzten Sonntag berechnen (w=0 ist Sonntag)
    $base  = (clone $now)->modify($offset . ' weeks');
    $dow   = (int)$base->format('w'); // 0=So, 6=Sa
    $start = (clone $base)->modify('-' . $dow . ' days')->setTime(0,0,0);
    $end   = (clone $start)->modify('+6 days')->setTime(23,59,59);
} else {
    $start = (clone $now)->modify($offset . ' months')->modify('first day of this month')->setTime(0,0,0);
    $end   = (clone $now)->modify($offset . ' months')->modify('last day of this month')->setTime(23,59,59);
}

// Sicherstellen dass start nicht vor firstDay liegt
$firstDayDt = new DateTime($firstDay, new DateTimeZone('Europe/Berlin'));
if ($start < $firstDayDt) {
    $start = $firstDayDt;
}

// Bei Wochenansicht: end darf bis Samstag in der Zukunft liegen
// Bei Monatsansicht: end immer letzter Tag des Monats (auch zukünftig)
// Nur tracking_days begrenzt was angezeigt wird

// Gesamt-Visits aus Shlink
$summary = shlinkGet($baseUrl . '/short-urls/' . $slug, $apiKey);
$total   = $summary['visitsSummary']['nonBots'] ?? 0;

// Visits im Zeitraum aus Shlink
$visitsUrl = $baseUrl . '/short-urls/' . $slug . '/visits'
    . '?startDate=' . urlencode($start->format('Y-m-d\TH:i:sP'))
    . '&endDate='   . urlencode($end->format('Y-m-d\TH:i:sP'))
    . '&excludeBots=true'
    . '&itemsPerPage=1000';

$visitsData = shlinkGet($visitsUrl, $apiKey);
$visits     = $visitsData['visits']['data'] ?? [];

// Visits pro Tag zählen
$visitsByDay = [];
$todayStr    = $now->format('Y-m-d');
$todayCount  = 0;

foreach ($visits as $visit) {
    $date = substr($visit['date'], 0, 10);
    $visitsByDay[$date] = ($visitsByDay[$date] ?? 0) + 1;
    if ($date === $todayStr) $todayCount++;
}

$labels = [];
$values = [];

if ($mode === 'month') {
    // Alle Tage des Monats anzeigen (1 bis 28/29/30/31)
    $daysInMonth = (int)$end->format('d');
    $year        = $start->format('Y');
    $month       = $start->format('m');
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date     = $year . '-' . $month . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
        $labels[] = (string)$d;
        $values[] = $visitsByDay[$date] ?? 0;
    }
} else {
    // Wochenansicht: alle 7 Tage So-Sa
    $dayNames = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    for ($i = 0; $i < 7; $i++) {
        $dt       = (clone $start)->modify('+' . $i . ' days');
        $date     = $dt->format('Y-m-d');
        $dayIndex = (int)$dt->format('w');
        $labels[] = $dayNames[$dayIndex] . ' ' . $dt->format('d.m.');
        $values[] = $visitsByDay[$date] ?? 0;
    }
}

// Periode gesamt
$periodTotal = array_sum($values);

// Navigations-Grenzen für Frontend
$firstMonth = (new DateTime($firstDay))->format('Y-m');
$nowMonth   = $now->format('Y-m');

echo json_encode([
    'total'      => $total,
    'period'     => $periodTotal,
    'today'      => $todayCount,
    'labels'     => $labels,
    'values'     => $values,
    'first_day'  => $firstDay,
    'has_data'   => count($trackedDays) > 0,
]);
