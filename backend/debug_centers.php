<?php
require_once 'config/Config.php';
require_once 'config/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM centers");
    $centers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($centers) . "\n";
    print_r($centers);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
