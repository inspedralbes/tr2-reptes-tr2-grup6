<?php
// backend/api/upload_evidencia.php
// API per pujar fitxers d'evidències

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

require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$pdo = $database->getConnection();

$usuari_id = $_SESSION['user_id'];
$rol_id = $_SESSION['rol_id'];

// Configuració
$upload_dir = __DIR__ . '/../../uploads/evidencies/';
$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$max_size = 5 * 1024 * 1024; // 5MB

// Crear directori si no existeix
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Mètode no permès']);
        exit;
    }
    
    // Validar paràmetres
    if (!isset($_POST['checklist_item_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'checklist_item_id requerit']);
        exit;
    }
    
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Fitxer requerit']);
        exit;
    }
    
    $checklist_item_id = (int)$_POST['checklist_item_id'];
    $file = $_FILES['file'];
    
    // Verificar permisos
    $sql_check = "SELECT c.sollicitud_id, s.centre_id 
                  FROM checklist_items c
                  JOIN sollicituds s ON c.sollicitud_id = s.id
                  WHERE c.id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':id' => $checklist_item_id]);
    $item = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Checklist item no trobat']);
        exit;
    }
    
    if ($rol_id != 1 && $item['centre_id'] != $usuari_id) {
        http_response_code(403);
        echo json_encode(['error' => 'No tens permisos']);
        exit;
    }
    
    // Validar fitxer
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Error en la pujada del fitxer']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'El fitxer és massa gran (màxim 5MB)']);
        exit;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipus de fitxer no permès. Només PDF, JPG, PNG i DOCX']);
        exit;
    }
    
    // Generar nom únic
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'evidencia_' . $checklist_item_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Moure fitxer
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el fitxer']);
        exit;
    }
    
    // Actualitzar checklist_item
    $file_url = '/uploads/evidencies/' . $filename;
    
    $sql_update = "UPDATE checklist_items 
                   SET fitxer_url = :fitxer_url, completat = 1 
                   WHERE id = :id";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        ':fitxer_url' => $file_url,
        ':id' => $checklist_item_id
    ]);
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'url' => $file_url,
        'message' => 'Fitxer pujat correctament'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
