<?php
// backend/api/notificacions.php
// API REST per gestionar notificacions

session_start();
header('Content-Type: application/json');
session_start();
include_once '../config/Cors.php';
habilitarCORS();

// Validar sessió
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autoritzat']);
    exit;
}

require_once __DIR__ . '/../utils/NotificationService.php';

$notificationService = new NotificationService();
$usuari_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtenir notificacions
            $nomes_no_llegides = isset($_GET['no_llegides']) && $_GET['no_llegides'] == '1';
            
            if (isset($_GET['count'])) {
                // Només comptar no llegides
                $result = $notificationService->comptarNoLlegides($usuari_id);
            } else {
                // Obtenir llista de notificacions
                $result = $notificationService->obtenirNotificacions($usuari_id, $nomes_no_llegides);
            }
            
            echo json_encode($result);
            break;
        
        case 'PUT':
            // Marcar notificació(ons) com a llegida
            if (isset($_GET['marcar_totes']) && $_GET['marcar_totes'] == '1') {
                // Marcar totes com a llegides
                $result = $notificationService->marcarTotesLlegides($usuari_id);
            } elseif (isset($_GET['id'])) {
                // Marcar una notificació específica
                $notificacio_id = (int)$_GET['id'];
                $result = $notificationService->marcarLlegida($notificacio_id, $usuari_id);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Paràmetre id requerit']);
                exit;
            }
            
            echo json_encode($result);
            break;
        
        case 'POST':
            // Crear notificació (només per admins o sistema)
            if ($_SESSION['rol_id'] != 1) {
                http_response_code(403);
                echo json_encode(['error' => 'No tens permisos']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['usuari_id']) || !isset($data['tipus']) || !isset($data['titol'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Dades incompletes']);
                exit;
            }
            
            $result = $notificationService->crearNotificacio(
                $data['usuari_id'],
                $data['tipus'],
                $data['titol'],
                $data['missatge'] ?? '',
                $data['sollicitud_id'] ?? null
            );
            
            echo json_encode($result);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Mètode no permès']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
