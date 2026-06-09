<?php
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();

echo "ai_conversations columns:\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_conversations`");
    foreach ($columns as $col) {
        echo "- " . ($col['Field'] ?? $col['field']) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nai_credits columns:\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_credits`");
    foreach ($columns as $col) {
        echo "- " . ($col['Field'] ?? $col['field']) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
