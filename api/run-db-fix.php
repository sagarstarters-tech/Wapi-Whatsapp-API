<?php
/**
 * WAPI SaaS - Database Schema Fix Script
 * Run this by visiting: https://yourdomain.com/api/run-db-fix.php
 */
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h3>Running DB Schema Fixes...</h3>";

function checkAndAddColumn($table, $column, $definition) {
    global $db, $pdo;
    try {
        $columns = $db->fetchAll("SHOW COLUMNS FROM `{$table}`");
        $exists = false;
        foreach ($columns as $col) {
            if (($col['Field'] ?? $col['field'] ?? '') === $column) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $pdo->exec("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
            echo "<span style='color:green;'>[SUCCESS]</span> Added column `{$column}` to table `{$table}`.<br>";
        } else {
            echo "<span style='color:blue;'>[INFO]</span> Column `{$column}` already exists in table `{$table}`.<br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red;'>[ERROR]</span> Error checking/adding column `{$column}` in table `{$table}`: " . $e->getMessage() . "<br>";
    }
}

// 1. Check if ai_conversations table exists and fix updated_at
checkAndAddColumn('ai_conversations', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

// 2. Check if ai_credits table exists and fix updated_at
checkAndAddColumn('ai_credits', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

echo "<br><b>Database fixes completed. Please delete this file after execution for security.</b>";
