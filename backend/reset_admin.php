<?php
// Direct DB update to reset admin password
$host = 'db';
$db   = 'kairos_db';
$user = 'kairos_user';
$pass = 'kairos_secret_password_change_this';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass);
    
    $password = 'Admin1234';
    $hash = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 2
    ]);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@kairos.cat'");
    $stmt->execute([$hash]);
    
    echo "Updated admin password successfully.\n";
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}
?>
