<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "1. Loading environment...\n";

// Definir constants com a index.php
define('BASE_DIR', __DIR__);
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', BASE_DIR . '/config');

echo "2. Requiring config...\n";
require_once CONFIG_DIR . '/Config.php';

echo "3. Setting up autoloader...\n";
spl_autoload_register(function ($class) {
    echo "Autoloading class: $class\n";
    $file = APP_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    echo "Looking for file: $file\n";
    if (file_exists($file)) {
        require_once $file;
        echo "File loaded.\n";
    } else {
        echo "File NOT found.\n";
    }
});

echo "4. Attempting to instantiate PhaseController...\n";
try {
    $controller = new \Controllers\PhaseController();
    echo "SUCCESS: Controller instantiated.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
