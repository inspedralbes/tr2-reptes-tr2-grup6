<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Config.php';

use Services\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getPDO();

    $email = 'bryannrg10@gmail.com';
    echo "Checking user: $email\n";

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found\n");
    }

    print_r($user);
    $centerId = $user['center_id'];

    if (!$centerId) {
        die("User has no center_id\n");
    }

    echo "\nStats for Center ID: $centerId\n";

    // 1. Requests
    $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE center_id = $centerId");
    echo "Total Requests: " . $stmt->fetchColumn() . "\n";

    // 2. Teachers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE center_id = $centerId AND role = 'teacher'");
    echo "Total Teachers: " . $stmt->fetchColumn() . "\n";

    // 3. Completed
    $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE center_id = $centerId AND status = 'completed'");
    echo "Completed Requests: " . $stmt->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
