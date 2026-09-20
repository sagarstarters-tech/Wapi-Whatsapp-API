<?php
$urls = [
    'cat11' => 'https://www.sagarstarters.com/shop.php?category=11',
    'cat7' => 'https://www.sagarstarters.com/shop.php?category=7',
    'cat8' => 'https://www.sagarstarters.com/shop.php?category=8',
];

$res = [];
foreach ($urls as $k => $u) {
    $h = @file_get_contents($u, false, stream_context_create([
        'http' => ['timeout' => 5, 'header' => "User-Agent: Mozilla/5.0\r\n", 'follow_location' => 1]
    ]));
    if ($h) {
        preg_match_all('/uploads\/media\/images\/[a-zA-Z0-9_\-\.]+/i', $h, $m);
        $res[$k] = array_values(array_unique($m[0] ?? []));
    } else {
        $res[$k] = 'failed to fetch';
    }
}
header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
