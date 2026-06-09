<?php
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h3>Diagnostic: ai_conversations Columns</h3>";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_conversations`");
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Diagnostic: ai_credits Columns</h3>";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_credits`");
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
