<?php
require_once __DIR__ . '/../../config/config.php';

$urls = [
    '72' => 'https://www.sagarstarters.com/product/1773370897-3-hp-3-phase-semi-automatic-motor-starter',
    '73' => 'https://www.sagarstarters.com/product/1773370426-3-hp-3-phase-automatic-motor-starter',
    '74' => 'https://www.sagarstarters.com/product/1773371765-up-to-7-5-hp-3-phase-semi-automatic-motor-starter',
    '75' => 'https://www.sagarstarters.com/product/1773371765-up-to-7-5-hp-3-phase-semi-automatic-motor-starter',
    '76' => 'https://www.sagarstarters.com/shop.php?category=11',
    '77' => 'https://www.sagarstarters.com/product/1773064855-30-hp-automatic-star-delta-motor-starter',
    '79' => 'https://www.sagarstarters.com/shop.php?category=7',
    '80' => 'https://www.sagarstarters.com/shop.php?category=8',
    '47' => 'https://www.sagarstarters.com/page.php?slug=1773469568-support'
];

$images = [];
foreach ($urls as $nodeId => $url) {
    $html = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 4, 'header' => "User-Agent: Mozilla/5.0\r\n"]
    ]));
    if ($html) {
        if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
            $images[$nodeId] = html_entity_decode($m[1]);
        } elseif (preg_match('/src="([^"]*uploads\/media\/images\/[^"]+)"/i', $html, $m2)) {
            $img = $m2[1];
            if (strpos($img, 'http') !== 0) $img = 'https://www.sagarstarters.com/' . ltrim($img, '/');
            $images[$nodeId] = $img;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($images, JSON_PRETTY_PRINT);
