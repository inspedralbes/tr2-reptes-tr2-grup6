<?php
require_once __DIR__ . '/../config/db.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE workshops");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
