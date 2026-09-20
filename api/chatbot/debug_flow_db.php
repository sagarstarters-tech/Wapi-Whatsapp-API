<?php
require_once __DIR__ . '/../../config/config.php';

$map = [
    'media_69d1302e3c8ea_1775317038.jpg' => 'https://www.sagarstarters.com/uploads/cms/wapi-team.jpg',
    'media_69d74e03afe2b_1775717891.jpeg' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f26b29e15a.webp',
    'media_69d75033c9727_1775718451.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f26b29e15a.webp',
    'media_69d75181eb385_1775718785.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f264aba7ad.webp',
    'media_69d75297556b4_1775719063.jpeg' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f258c4c5a1.webp',
    'media_69d75397766e2_1775719319.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f258c4c5a1.webp',
    'media_69d753f820f8e_1775719416.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f2282f3eb9.webp',
    'media_69d7565b6eeea_1775720027.jpeg' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f25406ab19.webp',
    'media_69d756cbbc3bf_1775720139.jpeg' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f24df64245.webp',
    'media_69d757fe65cb8_1775720446.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f25406ab19.webp',
    'media_69d75855c4d39_1775720533.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f2560f06e8.webp',
    'media_69d758d66738e_1775720662.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f24df64245.webp',
    'media_69d7593507e6b_1775720757.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f24df64245.webp',
    'media_69d75a2542a5d_1775720997.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f22ace7b9f.webp',
    'media_69d75b89c0f6e_1775721353.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f22ace7b9f.webp',
    'media_69d75c0db2836_1775721485.png' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f22c9ae068.webp',
    'media_69e365f7cc500_1776510455.jpg' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a87ffc08d565.webp',
    'media_69e3679582984_1776510869.jpg' => 'https://www.sagarstarters.com/uploads/media/images/prod_6a7f22ed9f004.jpg'
];

$targetDir = dirname(__DIR__, 2) . '/uploads/chatbot/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$results = [];
foreach ($map as $filename => $sourceUrl) {
    $targetPath = $targetDir . $filename;
    
    // Fetch source image with user agent & referer
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\nReferer: https://www.sagarstarters.com/\r\n"
        ]
    ]);
    $raw = @file_get_contents($sourceUrl, false, $ctx);
    if (!$raw) {
        $results[$filename] = ['status' => 'error', 'message' => 'Failed to fetch source: ' . $sourceUrl];
        continue;
    }
    
    // Target extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Check if source is webp
    $isWebp = (strpos($sourceUrl, '.webp') !== false) || (substr($raw, 8, 4) === 'WEBP');
    
    if ($isWebp && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromstring($raw);
        if (!$img) {
            // Write raw as fallback
            file_put_contents($targetPath, $raw);
            $results[$filename] = ['status' => 'saved_raw', 'size' => strlen($raw)];
            continue;
        }
        
        if ($ext === 'png') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagepng($img, $targetPath, 8);
        } else {
            // jpg or jpeg: fill white background if transparency exists
            $w = imagesx($img);
            $h = imagesy($img);
            $bg = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $img, 0, 0, 0, 0, $w, $h);
            imagejpeg($bg, $targetPath, 90);
            imagedestroy($bg);
        }
        imagedestroy($img);
        $results[$filename] = ['status' => 'converted_and_saved', 'size' => filesize($targetPath)];
    } else {
        file_put_contents($targetPath, $raw);
        $results[$filename] = ['status' => 'saved_direct', 'size' => filesize($targetPath)];
    }
    @chmod($targetPath, 0644);
}

header('Content-Type: application/json');
echo json_encode(['results' => $results], JSON_PRETTY_PRINT);
