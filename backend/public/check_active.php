<?php
// backend/public/check_active.php
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Force UTF-8
    $conn->exec("SET NAMES utf8mb4");

    $today = date('Y-m-d');
    echo "Today: $today\n";

    // 1. Check all phases dates
    $stmt = $conn->query("SELECT id, name, start_date, end_date, status FROM phases ORDER BY id ASC");
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nAll Phases:\n";
    foreach($phases as $p) {
        $isActive = ($p['start_date'] <= $today && $p['end_date'] >= $today);
        echo "ID: {$p['id']} - {$p['name']}\n";
        echo "   Range: {$p['start_date']} to {$p['end_date']}\n";
        echo "   DB Status: {$p['status']}\n";
        echo "   Should be Active? " . ($isActive ? "YES" : "NO") . "\n";
        echo "-------------------\n";
    }

    // 2. Simulate getCurrent query
    echo "\nQuery Result for 'getCurrent':\n";
    $sql = "SELECT id, name FROM phases WHERE status = 'active' ORDER BY id ASC LIMIT 1";
    $stmt = $conn->query($sql);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        echo "SELECTED PHASE: ID {$current['id']} - {$current['name']}\n";
    } else {
        echo "NO ACTIVE PHASE SELECTED.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
