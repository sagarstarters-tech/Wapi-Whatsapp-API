<?php
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();

echo "ai_conversations: ";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_conversations`");
    $names = array_map(function($col) { return $col['Field'] ?? $col['field']; }, $columns);
    echo implode(', ', $names);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "\n\nai_credits: ";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_credits`");
    $names = array_map(function($col) { return $col['Field'] ?? $col['field']; }, $columns);
    echo implode(', ', $names);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
