<?php
declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../../includes/config.php';

$apiKey  = $_ENV['SHLINK_API_KEY'] ?? '';
$baseUrl = 'https://shlink.qr.framenode.net/rest/v3';
$slug    = 'j';

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
$db     = getDB();
$now    = new DateTime('now', new DateTimeZone('Europe/Berlin'));
$todayStr = $now->format('Y-m-d');

// Ersten erfassten Tag ermitteln
$firstDay = $db->query("SELECT MIN(date) FROM tracking_days")->fetchColumn();
$firstDay = $firstDay ?: $todayStr;

// Zeitraum berechnen
if ($mode === 'week') {
    $dow      = (int)$now->format('w');
    $sunday   = (clone $now)->modify('-' . $dow . ' days')->modify($offset . ' weeks')->setTime(0, 0, 0);
    $saturday = (clone $sunday)->modify('+6 days')->setTime(23, 59, 59);
    $start    = $sunday;
    $end      = $saturday;
    $months_de_short = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
    $startYear = $start->format('Y');
    $endYear   = $end->format('Y');
    $startPart = $start->format('j.') . ' ' . $months_de_short[(int)$start->format('n')-1];
    $endPart   = $end->format('j.') . ' ' . $months_de_short[(int)$end->format('n')-1];
    $periodTitle = $startYear === $endYear
        ? $startPart . ' – ' . $endPart . ' ' . $endYear
        : $startPart . ' ' . $startYear . ' – ' . $endPart . ' ' . $endYear;
} else {
    $start = (clone $now)->modify($offset . ' months')->modify('first day of this month')->setTime(0, 0, 0);
    $end   = (clone $now)->modify($offset . ' months')->modify('last day of this month')->setTime(23, 59, 59);
    $months_de = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
    $periodTitle = $months_de[(int)$start->format('n')-1] . ' ' . $start->format('Y');
}

// Gesamt-Visits aus Shlink
$summary = shlinkGet($baseUrl . '/short-urls/' . $slug, $apiKey);
$total   = $summary['visitsSummary']['nonBots'] ?? 0;

// Visits im Zeitraum aus Shlink
$visitsUrl = $baseUrl . '/short-urls/' . $slug . '/visits'
    . '?startDate=' . urlencode($start->format('Y-m-d\TH:i:sP'))
    . '&endDate='   . urlencode($end->format('Y-m-d\TH:i:sP'))
    . '&excludeBots=true'
    . '&itemsPerPage=1000';

$visitsData  = shlinkGet($visitsUrl, $apiKey);
$visits      = $visitsData['visits']['data'] ?? [];
$visitsByDay = [];
$todayCount  = 0;

foreach ($visits as $visit) {
    $date = substr($visit['date'], 0, 10);
    $visitsByDay[$date] = ($visitsByDay[$date] ?? 0) + 1;
    if ($date === $todayStr) $todayCount++;
}

// Labels und Werte aufbauen
$labels = [];
$values = [];

if ($mode === 'month') {
    $daysInMonth = (int)$end->format('d');
    $year  = $start->format('Y');
    $month = $start->format('m');
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date     = $year . '-' . $month . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
        $labels[] = (string)$d;
        $values[] = $date <= $todayStr ? ($visitsByDay[$date] ?? 0) : null;
    }
} else {
    $dayNames = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    for ($i = 0; $i < 7; $i++) {
        $dt       = (clone $start)->modify('+' . $i . ' days');
        $date     = $dt->format('Y-m-d');
        $dayIndex = (int)$dt->format('w');
        $labels[] = $dayNames[$dayIndex] . ' ' . $dt->format('d.m.');
        $values[] = ($date < $firstDay || $date > $todayStr) ? null : ($visitsByDay[$date] ?? 0);
    }
}

$periodTotal = array_sum(array_filter($values, fn($v) => $v !== null));

// Navigations-Grenzen berechnen
$nowDow      = (int)$now->format('w');
$nowSunday   = (clone $now)->modify('-' . $nowDow . ' days');
$firstDt     = new DateTime($firstDay, new DateTimeZone('Europe/Berlin'));
$firstDow    = (int)$firstDt->format('w');
$firstSunday = (clone $firstDt)->modify('-' . $firstDow . ' days');
$diffDays    = (int)$nowSunday->diff($firstSunday)->days;
$minWeekOffset  = -1 * (int)($diffDays / 7);

$firstYear      = (int)$firstDt->format('Y');
$firstMonth     = (int)$firstDt->format('n');
$nowYear        = (int)$now->format('Y');
$nowMonth       = (int)$now->format('n');
$minMonthOffset = ($firstYear - $nowYear) * 12 + ($firstMonth - $nowMonth);

// Alle verfügbaren Perioden für Picker
$months_de = $months_de ?? ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
$periods = [];
if ($mode === 'month') {
    for ($i = $minMonthOffset; $i <= 0; $i++) {
        $d = (clone $now)->modify($i . ' months');
        $periods[] = [
            'offset' => $i,
            'label'  => $months_de[(int)$d->format('n')-1] . ' ' . $d->format('Y'),
            'month'  => $months_de[(int)$d->format('n')-1],
            'year'   => (int)$d->format('Y'),
        ];
    }
} else {
    for ($i = $minWeekOffset; $i <= 0; $i++) {
        $dowN = (int)$now->format('w');
        $sun  = (clone $now)->modify('-' . $dowN . ' days')->modify($i . ' weeks');
        $kw   = (int)$sun->format('W');
        $year = (int)$sun->format('o');
        $periods[] = [
            'offset'   => $i,
            'label'    => 'KW ' . $kw . ' ' . $year,
            'kw_label' => 'KW ' . $kw,
            'year'     => $year,
        ];
    }
}

echo json_encode([
    'total'            => $total,
    'period'           => $periodTotal,
    'today'            => $todayCount,
    'labels'           => $labels,
    'values'           => $values,
    'first_day'        => $firstDay,
    'has_data'         => count($labels) > 0,
    'period_title'     => $periodTitle,
    'min_week_offset'  => $minWeekOffset,
    'min_month_offset' => $minMonthOffset,
    'periods'          => array_reverse($periods),
]);
