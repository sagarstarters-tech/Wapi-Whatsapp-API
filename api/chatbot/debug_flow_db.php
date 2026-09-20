<?php
$gd = extension_loaded('gd');
$info = $gd ? gd_info() : [];
header('Content-Type: application/json');
echo json_encode([
    'gd' => $gd,
    'webp_support' => $info['WebP Support'] ?? false,
    'jpeg_support' => $info['JPEG Support'] ?? false,
    'png_support' => $info['PNG Support'] ?? false
], JSON_PRETTY_PRINT);
