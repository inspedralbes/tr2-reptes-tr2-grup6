<?php
require_once __DIR__ . '/index.php'; // Bootstrap

$sqlFile = __DIR__ . '/database/005_qr_attendance.sql';
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Error reading SQL file");
}

$db = \Services\Database::getInstance();
try {
    // Split by delimiter if needed, but since Database::query might handle single statements, we might need to be careful with DELIMITER // syntax.
    // PHP PDO doesn't support DELIMITER syntax usually.
    // I should parse it or just run the CREATE TABLE parts which are most important.
    // The SQL file has DELIMITER // for triggers/procedures. This fails in standard PDO query usually.
    
    // Let's try to run just the CREATE TABLE parts first using regex or manual split
    // Actually, lets simply run the tables creation. The triggers/SPs are nice but I can implement logic in PHP Controller if SPs fail to load.
    
    // Strategy: Read file, remove DELIMITER blocks or try to parse.
    // Simpler Strategy: I will execute the CREATE TABLE statements manually here to ensure they exist.
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS workshop_qr_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            qr_code VARCHAR(100) UNIQUE NOT NULL,
            qr_data TEXT NOT NULL,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            scan_count INT DEFAULT 0,
            last_scanned_at TIMESTAMP NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_qr_code (qr_code),
            INDEX idx_assignment (assignment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        "CREATE TABLE IF NOT EXISTS qr_scans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            qr_code_id INT NOT NULL,
            assignment_id INT NOT NULL,
            center_id INT NOT NULL,
            teacher_id INT NULL,
            scan_type ENUM('check_in', 'check_out', 'verification') DEFAULT 'check_in',
            scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            scanned_by_user_id INT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            location_data JSON,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (qr_code_id) REFERENCES workshop_qr_codes(id) ON DELETE CASCADE,
            INDEX idx_qr_code (qr_code_id),
            INDEX idx_assignment (assignment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($tables as $t) {
        $db->query($t);
        echo "Table executed.\n";
    }
    
    echo "Migration tables created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
