<?php
/**
 * Test upload endpoint
 * Run: docker exec kairos_backend php debug_gallery_upload.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== GALLERY UPLOAD DEBUG ===\n\n";

define('BASE_DIR', __DIR__);
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', BASE_DIR . '/config');

require_once CONFIG_DIR . '/Config.php';

spl_autoload_register(function ($class) {
    $file = APP_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

echo "✓ Autoloader setup\n\n";

try {
    // Test 1: Load GalleryController
    echo "Test 1: Loading GalleryController...\n";
    $controller = new \Controllers\GalleryController();
    echo "✓ GalleryController loaded\n\n";
    
    // Test 2: Check upload directory
    echo "Test 2: Checking upload directory...\n";
    $uploadDir = BASE_DIR . '/public/uploads/gallery/';
    echo "Upload dir: $uploadDir\n";
    
    if (!is_dir($uploadDir)) {
        echo "⚠ Directory doesn't exist, creating...\n";
        if (mkdir($uploadDir, 0777, true)) {
            echo "✓ Directory created\n";
        } else {
            echo "✗ Failed to create directory\n";
        }
    } else {
        echo "✓ Directory exists\n";
    }
    
    if (is_writable($uploadDir)) {
        echo "✓ Directory is writable\n";
    } else {
        echo "✗ Directory is NOT writable\n";
    }
    
    echo "\n=== ALL TESTS COMPLETED ===\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR OCCURRED:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
