<?php
$host = 'localhost';
$user = 'root';
$password = '';

$api ='ii6sQ0r9vb85vqRE';

// Load existing database or start empty
if (file_exists("database.json")) {
    $database = json_decode(file_get_contents("database.json"), true);
} else {
    $database = [];
}

if (isset($_POST['faction']) && isset($_POST['api'])) {
    $id = $_POST['faction'];
    
}else{$id=51067;};

$curl = curl_init();
$url = "https://api.torn.com/faction/$id?selection=profiles&key=ii6sQ0r9vb85vqRE";
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
curl_close($curl);
$faction = json_decode($response, true);
foreach ($faction["members"] as $member){
    echo $member["name"];};

echo "<header><h1>tornfacmemberstracking</h1> <a href='charter.php' style='color:#8cf;text-decoration:none;font-size:0.95rem;'>View activity charts</a></header><body style='display:flex;height:100vh;width:100vw;align-items:center;justify-content:space-around;background-color:black;flex-direction:column;color:grey;padding:0px'>
<form method='post' style='display:flex;flex-direction:column;border:1px solid grey;border-radius:10px;padding:20px;height:70%;align-items:center;justify-content:center'>
    <input name='api' type='text' placeholder='api key'>
    <input name='faction' type='text' placeholder='faction name'>
    <input type='submit'>
</form>";

echo "<table style='border:1px solid grey;border-radius:10px;padding:20px;height:70%;align-items:center;justify-content:center'><tr><td>faction name</td><td>members</td><td>status</td><td>last online</td><td>graph</td></tr>";

foreach ($faction["members"] as $member){
    $anchor = urlencode($member["id"] ?? $member["name"] ?? '');
    echo "<tr><td>".$faction["name"]."</td><td>".$member["name"]."</td><td>".$member["level"]."</td><td>".$member["last_action"]["status"]."</td><td>".$member["last_action"]["relative"]."</td><td><a href='charter.php#member-".$anchor."'>charts</a></td></tr>";

    $memberName = $member['name'] ?? null;
    $memberId = $member['id'] ?? $member['player_id'] ?? $memberName;
    if ($memberId === null) {
        continue;
    }

    $existingIndex = null;
    foreach ($database as $idx => $existing) {
        if (($existing['id'] ?? null) === $memberId || ($existing['name'] ?? null) === $memberName) {
            $existingIndex = $idx;
            break;
        }
    }

    $currentStatus = $member['last_action']['status'] ?? '';
    $currentRelative = $member['last_action']['relative'] ?? '';

    if ($existingIndex === null) {
        $database[] = [
            'id' => $memberId,
            'name' => $member['name'] ?? '',
            'level' => $member['level'] ?? 0,
            'status' => $currentStatus,
            'relative' => $currentRelative,
            'timestamps' => []
        ];
        $existingIndex = count($database) - 1;
    }

    if (!isset($database[$existingIndex]['timestamps']) || !is_array($database[$existingIndex]['timestamps'])) {
        $database[$existingIndex]['timestamps'] = [];
    }

    $database[$existingIndex]['timestamps'][] = [
        'status' => $currentStatus,
        'relative' => $currentRelative,
        'time' => time()
    ];
}

file_put_contents("database.json", json_encode($database));

echo "<script>window.addEventListener('load', function() { fetch('monitor.php', { method: 'POST' }).catch(function(err){ console.error('monitor failed', err); }); });</script>";
