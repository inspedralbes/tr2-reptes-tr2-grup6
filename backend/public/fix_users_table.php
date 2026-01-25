<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = new \Services\Database();
    $conn = $db->connect();
    
    echo "Connected. Checking users table...\n";
    
    // Check if column exists
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'center_id'");
    if ($stmt->fetch()) {
        echo "Column center_id already exists.\n";
    } else {
        echo "Adding center_id column...\n";
        $sql = "ALTER TABLE users ADD COLUMN center_id INT AFTER role";
        $conn->exec($sql);
        echo "Column added.\n";
        
        echo "Adding Foreign Key...\n";
        $sql = "ALTER TABLE users ADD CONSTRAINT fk_users_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE SET NULL";
        $conn->exec($sql);
        echo "Foreign Key added.\n";
    }
    
    echo "Fix complete.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
