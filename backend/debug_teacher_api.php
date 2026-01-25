<?php
/**
 * Debug script para TeacherController
 * Ejecútalo con: php debug_teacher_api.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEACHER API DEBUG ===\n\n";

// Setup paths
define('BASE_DIR', __DIR__);
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', BASE_DIR . '/config');

// Load config
require_once CONFIG_DIR . '/Config.php';
echo "✓ Config loaded\n";

// Setup autoloader
spl_autoload_register(function ($class) {
    $file = APP_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});
echo "✓ Autoloader registered\n\n";

try {
    // Test 1: Load TeacherController
    echo "Test 1: Loading TeacherController...\n";
    $controller = new \Controllers\TeacherController();
    echo "✓ TeacherController loaded\n\n";
    
    // Test 2: Database connection
    echo "Test 2: Testing Database connection...\n";
    $db = \Services\Database::getInstance();
    echo "✓ Database instance created\n";
    
    $stmt = $db->prepare('SELECT 1 as test');
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✓ Database connection works: " . json_encode($result) . "\n\n";
    
    // Test 3: Test LIST method
    echo "Test 3: Testing list() method...\n";
    $_GET['center_id'] = '1';
    ob_start();
    $controller->list();
    $output = ob_get_clean();
    echo "Response: " . $output . "\n\n";
    
    // Test 4: Test CREATE method
    echo "Test 4: Testing create() method...\n";
    
    // Simulate POST data
    $testData = [
        'full_name' => 'Test Teacher Debug',
        'email' => 'test_' . time() . '@teacher.com',
        'center_id' => 1,
        'phone' => '123456789',
        'specialty' => 'Test'
    ];
    
    // We need to mock php://input
    $tempFile = tmpfile();
    fwrite($tempFile, json_encode($testData));
    fseek($tempFile, 0);
    
    // Capture output
    ob_start();
    try {
        $controller->create();
    } catch (\Exception $e) {
        echo json_encode([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    $createOutput = ob_get_clean();
    
    echo "Create Response: " . $createOutput . "\n\n";
    
    echo "=== ALL TESTS COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR OCCURRED:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
