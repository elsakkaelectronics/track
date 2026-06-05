<?php
ignore_user_abort(true);
set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');

$apiKey = 'ii6sQ0r9vb85vqRE';
$dbFile = __DIR__ . '/database.json';

if (!file_exists($dbFile)) {
    echo json_encode(['error' => 'database file not found']);
    exit;
}

$database = json_decode(file_get_contents($dbFile), true);
if (!is_array($database)) {
    echo json_encode(['error' => 'invalid database file']);
    exit;
}

foreach ($database as $idx => $member) {
    $memberId = $member['id'] ?? $member['name'] ?? null;
    if ($memberId === null) {
        continue;
    }

    $curl = curl_init();
    $url = 'https://api.torn.com/user/' . urlencode($memberId) . '?selection=last_action&key=' . urlencode($apiKey);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    $data = json_decode($response, true);
    $currentStatus = $data['last_action']['status'] ?? ($member['status'] ?? '');
    $currentRelative = $data['last_action']['relative'] ?? ($member['relative'] ?? '');

    $database[$idx]['status'] = $currentStatus;
    $database[$idx]['relative'] = $currentRelative;

    if (!isset($database[$idx]['timestamps']) || !is_array($database[$idx]['timestamps'])) {
        $database[$idx]['timestamps'] = [];
    }

    $database[$idx]['timestamps'][] = [
        'status' => $currentStatus,
        'relative' => $currentRelative,
        'time' => time(),
    ];
}

file_put_contents($dbFile, json_encode($database));

echo json_encode(['status' => 'ok', 'members' => count($database)]);
