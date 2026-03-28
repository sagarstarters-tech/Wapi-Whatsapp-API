<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/chatbot-engine/functions.php';

try {
    echo "Testing setSession with dummy data...\n";
    setSession("123456789", 1, 1, "test_node", "active");
    echo "setSession succeeded.\n";
} catch (Exception $e) {
    echo "setSession CRASHED: " . $e->getMessage() . "\n";
}
