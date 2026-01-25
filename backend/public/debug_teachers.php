<?php
/**
 * Script de debug para el endpoint de teachers
 * Ejecuta esto en el navegador: http://localhost:8000/debug_teachers.php
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuración y setup básico
define('BASE_DIR', __DIR__ . '/backend');
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', BASE_DIR . '/config');

require_once CONFIG_DIR . '/Config.php';

// Autoloader
spl_autoload_register(function ($class) {
    $file = APP_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        echo json_encode(['error' => 'Could not load class: ' . $class, 'file' => $file]);
        exit;
    }
});

try {
    // Test 1: Can we load TeacherController?
    echo "Test 1: Loading TeacherController...\n";
    $controller = new \Controllers\TeacherController();
    echo "✓ TeacherController loaded successfully\n\n";
    
    // Test 2: Can we get Database instance?
    echo "Test 2: Getting Database instance...\n";
    $db = \Services\Database::getInstance();
    echo "✓ Database instance obtained\n\n";
    
    // Test 3: Can we execute a simple query?
    echo "Test 3: Testing database connection...\n";
    $stmt = $db->prepare('SELECT 1 as test');
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✓ Database connection working: " . json_encode($result) . "\n\n";
    
    // Test 4: Check if teachers table exists
    echo "Test 4: Checking teachers table...\n";
    $stmt = $db->prepare('SHOW TABLES LIKE "teachers"');
    $stmt->execute();
    $tableExists = $stmt->fetch();
    if ($tableExists) {
        echo "✓ Teachers table exists\n\n";
    } else {
        echo "✗ Teachers table NOT found\n\n";
    }
    
    // Test 5: Try to fetch teachers
    echo "Test 5: Fetching teachers for center_id=1...\n";
    $_GET['center_id'] = '1';
    ob_start();
    $controller->list();
    $output = ob_get_clean();
    echo "Output: " . $output . "\n\n";
    
    echo "=== ALL TESTS COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
