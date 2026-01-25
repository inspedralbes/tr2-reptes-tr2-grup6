<?php
/**
 * Bootstrap Inicial de KAIROS
 * Path: /backend/index.php
 * Entry point per a l'aplicació
 */

// Headers CORS (Robust Handling)
// Headers CORS (Robust Handling)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400"); // Cache preflight per 1 dia

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

header("Content-Type: application/json; charset=utf-8");

// Workaround for missing Authorization header in some setups (Apache/PHP-CLI-Server)
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
        $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
    } elseif (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['HTTP_X_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $requestHeaders['Authorization'];
        }
    }
}

// Handle Preflight Options immediatament
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Definir directori base
define('BASE_DIR', __DIR__);
define('APP_DIR', BASE_DIR . '/app');
define('CONFIG_DIR', BASE_DIR . '/config');

// Carregar configuració
require_once CONFIG_DIR . '/Config.php';

// Autoloader simple
spl_autoload_register(function ($class) {
    // Reemplaçar namespace separators amb ruta de fitxer
    $file = APP_DIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});





// Setup CORS handled at top of file

// Gestor d'errors
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => \Config::APP_DEBUG ? $errstr : 'Error intern del servidor',
        'error_code' => $errno
    ]);
    exit;
});

// Capturar excepcions
set_exception_handler(function ($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => \Config::APP_DEBUG ? $exception->getMessage() : 'Error intern del servidor'
    ]);
    exit;
});

/**
 * Router simple basada en PATH_INFO
 */
$request_method = $_SERVER['REQUEST_METHOD'];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_path = str_replace('/backend', '', $request_path);

// Definir rutes API (sense autenticació)
$publicRoutes = [
    'GET' => [
        '/api/health' => 'Controllers\\HealthController@check',
        '/api/debug-diagnose' => 'Controllers\\DebugDiagnoseController@run',
        '/api/debug-diagnose-2' => 'Controllers\\DebugDiagnoseTwoController@run',
        '/api/z' => 'Controllers\\ZController@run',
        '/api/center-requests' => 'Controllers\\CenterRequestController@list', // Should be protected but frontend missing auth header
        '/api/gallery' => 'Controllers\\GalleryController@list',
        '/api/gallery/albums' => 'Controllers\\GalleryController@albums',
        '/api/gallery/stats' => 'Controllers\\GalleryController@stats',
    ],
    'POST' => [
        '/api/auth/login' => 'Controllers\\AuthController@login',
        '/api/auth/register' => 'Controllers\\AuthController@register',
        '/api/gallery/([0-9]+)/view' => 'Controllers\\GalleryController@increment',
        '/api/center-requests' => 'Controllers\\CenterRequestController@create', // Public creation
    ]
];

// Definir rutes API (amb autenticació requerida)
$protectedRoutes = [
    'GET' => [

        '/api/auth/verify' => 'Controllers\\AuthController@verify',
        '/api/workshops' => 'Controllers\\WorkshopController@list',
        '/api/workshops/([0-9]+)' => 'Controllers\\WorkshopController@show',
        '/api/requests' => 'Controllers\\RequestController@list',
        '/api/allocations' => 'Controllers\\AllocationController@list',
        '/api/slots/workshop/([0-9]+)' => 'Controllers\\SlotController@show',
        '/api/assignment/analytics/([0-9]+)' => 'Controllers\\AssignmentController@analytics',
        '/api/assignment/analytics/([0-9]+)' => 'Controllers\\AssignmentController@analytics',
        // Phase routes
        '/api/phases/all' => 'Controllers\\PhaseController@getAll',
        '/api/phases/current' => 'Controllers\\PhaseController@getCurrent',
        '/api/phases/roadmap' => 'Controllers\\PhaseController@getRoadmap',
        
        '/api/admin/stats' => 'Controllers\\AdminController@stats',
        '/api/admin/stats' => 'Controllers\\AdminController@stats',
        '/api/centers' => 'Controllers\\CenterController@list',
        '/api/centers/([0-9]+)/dashboard-stats' => 'Controllers\\CenterController@stats',
        '/api/teachers' => 'Controllers\\TeacherController@list',
        '/api/center/teachers' => 'Controllers\\TeacherController@list',
        '/api/admin/gallery/pending' => 'Controllers\\GalleryController@listPending',
        '/api/qr/history' => 'Controllers\\QrController@history',
        '/api/allocations/([0-9]+)' => 'Controllers\\AllocationController@getById',
        '/api/feedback/tags' => 'Controllers\\FeedbackController@getTags',
        '/api/admin/feedback' => 'Controllers\\FeedbackController@list',
    ],
    'POST' => [
        '/api/workshops' => 'Controllers\\WorkshopController@create',
        '/api/requests' => 'Controllers\\RequestController@create',
        '/api/cart/submit' => 'Controllers\\RequestController@submitBulk',
        // '/api/center-requests' moved to public
        '/api/slots/lock' => 'Controllers\\SlotController@lock',
        '/api/slots/book' => 'Controllers\\SlotController@book',
        '/api/slots/unlock' => 'Controllers\\SlotController@unlock',
        '/api/allocations/execute' => 'Controllers\\AssignmentController@execute',
        '/api/phases' => 'Controllers\\PhaseController@create',
        '/api/teachers' => 'Controllers\\TeacherController@create',
        '/api/center/teachers/([0-9]+)/reset-password' => 'Controllers\\TeacherController@resetPassword',
        '/api/center/teachers' => 'Controllers\\TeacherController@create',
        '/api/center/teachers/([0-9]+)' => 'Controllers\\TeacherController@update', // Add update route explicitly if needed
        '/api/centers/([0-9]+)/reset-password' => 'Controllers\\CenterController@resetPassword',
        '/api/gallery/upload' => 'Controllers\\GalleryController@upload',
        '/api/admin/gallery/([0-9]+)/approve' => 'Controllers\\GalleryController@approve',
        '/api/admin/gallery/([0-9]+)/reject' => 'Controllers\\GalleryController@reject',
        '/api/admin/gallery/([0-9]+)/toggle-featured' => 'Controllers\\GalleryController@toggleFeatured',
        '/api/qr/generate' => 'Controllers\\QrController@generate',
        '/api/qr/scan' => 'Controllers\\QrController@scan',
        '/api/feedback' => 'Controllers\\FeedbackController@create',
    ],
    'PUT' => [
        '/api/workshops/([0-9]+)' => 'Controllers\\WorkshopController@update',
        '/api/requests/([0-9]+)' => 'Controllers\\RequestController@update',
        '/api/allocations/([0-9]+)' => 'Controllers\\AllocationController@update',
        '/api/phases/([0-9]+)' => 'Controllers\\PhaseController@update',
        '/api/phases/([0-9]+)/dates' => 'Controllers\\PhaseController@updateDates',
        '/api/centers/([0-9]+)' => 'Controllers\\CenterController@update',
        '/api/center-requests/([0-9]+)/approve' => 'Controllers\\CenterRequestController@approve',
        '/api/center-requests/([0-9]+)/reject' => 'Controllers\\CenterRequestController@reject',
        '/api/center/teachers/([0-9]+)' => 'Controllers\\TeacherController@update',
    ],
    'DELETE' => [
        '/api/workshops/([0-9]+)' => 'Controllers\\WorkshopController@delete',
        '/api/requests/([0-9]+)' => 'Controllers\\RequestController@delete',
        '/api/assignment/reset/([0-9]+)' => 'Controllers\\AssignmentController@reset',
        '/api/center/teachers/([0-9]+)' => 'Controllers\\TeacherController@delete',
    ]
];

// Function helper per matchejar rutes
function matchRoute($pattern, $path, &$matches = []) {
    if (preg_match('#^' . $pattern . '$#', $path, $m)) {
        $matches = array_slice($m, 1);
        return true;
    }
    return false;
}

// Intentar matchejar ruta pública
$matched = false;
$user = null;

if (isset($publicRoutes[$request_method])) {
    foreach ($publicRoutes[$request_method] as $pattern => $controller) {
        $matches = [];
        if (matchRoute($pattern, $request_path, $matches)) {
            $matched = true;
            list($controller_class, $method) = explode('@', $controller);
            
            try {
                $controller_instance = new $controller_class();
                call_user_func_array([$controller_instance, $method], $matches);
                exit;
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error controlador: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
}

// Intentar matchejar ruta protegida
if (!$matched && isset($protectedRoutes[$request_method])) {
    foreach ($protectedRoutes[$request_method] as $pattern => $controller) {
        $matches = [];
        if (matchRoute($pattern, $request_path, $matches)) {
            $matched = true;
            
            // Verificar autenticació
            $user = \Middleware\AuthMiddleware::authenticate();
            
            list($controller_class, $method) = explode('@', $controller);
            
            try {
                $controller_instance = new $controller_class();
                call_user_func_array([$controller_instance, $method], $matches);
                exit;
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error controlador: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
}

// Si no coincideix cap ruta
if (!$matched) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Ruta no trobada: ' . $request_path,
        'code' => 'NOT_FOUND'
    ]);
}
