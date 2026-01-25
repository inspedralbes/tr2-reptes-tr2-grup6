<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Config.php';

use Services\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getPDO();

    // 1. Check Request 4
    echo "--- Checking Request ID 4 ---\n";
    $stmt = $pdo->query("SELECT * FROM requests WHERE id = 4");
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($req) {
        print_r($req);
    } else {
        echo "Request 4 NOT FOUND\n";
    }

    // 2. Check Allocation for Request 4
    echo "\n--- Checking Allocations for Request 4 ---\n";
    $stmt = $pdo->query("SELECT * FROM allocations WHERE request_id = 4");
    $allocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($allocs);

    // 3. Check All Allocations
    echo "\n--- All Allocations ---\n";
    $stmt = $pdo->query("SELECT * FROM allocations");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($all) . "\n";
    if (count($all) > 0) print_r($all[0]);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
