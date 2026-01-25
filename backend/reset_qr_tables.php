<?php
require_once __DIR__ . '/index.php';

$db = \Services\Database::getInstance();

try {
    // Drop old tables if they exist
    $db->query("DROP TABLE IF EXISTS qr_scans");
    $db->query("DROP TABLE IF EXISTS workshop_qr_codes");
    
    echo "Old tables dropped successfully.\n";
    echo "New tables will be created automatically on first use.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
