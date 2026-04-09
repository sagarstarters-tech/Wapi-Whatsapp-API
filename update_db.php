<?php
require_once __DIR__ . '/config/config.php';

try {
    $db = Database::getInstance();
    
    // Check if column exists first to prevent errors
    $columns = $db->fetchAll("SHOW COLUMNS FROM templates LIKE 'variables'");
    if (empty($columns)) {
        $db->query("ALTER TABLE templates ADD COLUMN variables LONGTEXT NULL AFTER body");
        echo "Database updated successfully: Added 'variables' column.";
    } else {
        echo "Database already updated. 'variables' column exists.";
    }
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
