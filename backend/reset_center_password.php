<?php
/**
 * Reset password for center coordinator
 * Run in browser: http://localhost:8000/reset_center_password.php?email=coord@escola.cat
 */

define('BASE_DIR', __DIR__);
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', BASE_DIR . '/config');

require_once CONFIG_DIR . '/Config.php';

spl_autoload_register(function ($class) {
    $file = APP_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

header('Content-Type: application/json');

$email = $_GET['email'] ?? 'coord@escola.cat';
$newPassword = 'kairos123'; // Simple password for testing

try {
    $db = \Services\Database::getInstance();
    
    // Check if user exists
    $stmt = $db->prepare('SELECT id, email, full_name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Update password
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $updateStmt->execute([$hash, $email]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully',
        'email' => $email,
        'password' => $newPassword,
        'user' => $user
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
