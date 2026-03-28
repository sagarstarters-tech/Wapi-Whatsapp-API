<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/chatbot-engine/functions.php';

$db = Database::getInstance();
$flowJson = json_encode([
    'drawflow' => [
        'Home' => [
            'data' => [
                "1" => [
                    "name" => "start",
                    "data" => ["keywords" => "hi, hello"],
                    "inputs" => ["input_1" => ["connections" => []]],
                    "outputs" => ["output_1" => ["connections" => [ ["node" => "2", "output" => "input_1"] ]]]
                ],
                "2" => [
                    "name" => "text",
                    "data" => ["text" => "Welcome to the bot!"],
                    "inputs" => ["input_1" => ["connections" => [ ["node" => "1", "input" => "output_1"] ]]],
                    "outputs" => ["output_1" => ["connections" => []]]
                ]
            ]
        ]
    ]
]);

$nodes = json_decode($flowJson, true)['drawflow']['Home']['data'];
$isTrigger = false; 
$startNodeId = null;
$textBody = "hi";

foreach ($nodes as $nId => $nData) {
    if ($nData['name'] === 'start') {
        $keywords = strtolower($nData['data']['keywords'] ?? '');
        $keywordArr = array_map('trim', explode(',', $keywords));
        
        echo "Keywords String: '$keywords'\n";
        print_r($keywordArr);
        
        if ((empty($keywords) && in_array($textBody, ['hi', 'hello', 'start', 'menu'])) || in_array($textBody, $keywordArr)) {
            $isTrigger = true; $startNodeId = $nId; break;
        }
    }
}

if ($isTrigger) {
    echo "TRIGGER SUCCESSFUL. Start node is $startNodeId.\n";
    try {
        runFlow("919000000000", 999, 999, $startNodeId, "111", "token");
        echo "runFlow completed.\n";
    } catch (Exception $e) {
        echo "runFlow CRASHED: " . $e->getMessage() . "\n";
    }
} else {
    echo "NOT TRIGGERED.\n";
}
