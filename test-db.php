<?php
require_once __DIR__ . '/config/config.php';
$db = Database::getInstance();

try {
    $indices = $db->fetchAll("SHOW INDEX FROM chatbot_sessions");
    echo "INDICES IN chatbot_sessions:\n";
    foreach ($indices as $idx) {
        echo "- " . $idx['Key_name'] . " (" . $idx['Column_name'] . ")\n";
    }
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage();
}
