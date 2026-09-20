<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();

// Check uploads directory
$uploadDir = dirname(__DIR__, 2) . '/uploads';
$filesInUploads = [];
if (is_dir($uploadDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $filesInUploads[] = str_replace($uploadDir, '', $file->getPathname());
        }
    }
}
$result = ['uploads_files' => $filesInUploads];

$helloFlow = $db->fetch("SELECT * FROM chatbot_flows WHERE name LIKE '%hello%' OR name LIKE '%bot%' ORDER BY id DESC LIMIT 1");
if (!$helloFlow && !empty($flows)) {
    $helloFlow = $db->fetch("SELECT * FROM chatbot_flows WHERE id = ?", [$flows[0]['id']]);
}

if ($helloFlow) {
    $data = json_decode($helloFlow['flow_json'], true);
    $nodes = $data['drawflow']['Home']['data'] ?? $data['drawflow']['home']['data'] ?? [];
    $imageNodes = [];
    foreach ($nodes as $nId => $node) {
        $nData = $node['data'] ?? [];
        $img = $nData['image'] ?? $nData['url'] ?? null;
        if (!empty($img) || in_array($node['name'] ?? '', ['cta', 'interactive', 'image'])) {
            $imageNodes[] = [
                'id' => $nId,
                'name' => $node['name'] ?? '',
                'image' => $img,
                'full_data' => $nData
            ];
        }
    }
    $result['inspect_flow'] = [
        'id' => $helloFlow['id'],
        'name' => $helloFlow['name'],
        'is_active' => $helloFlow['is_active'],
        'node_count' => count($nodes),
        'image_nodes' => $imageNodes
    ];
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

