<?php
// backend/api/download_evidencia.php
// API per descarregar fitxers d'evidències

session_start();

// Validar sessió
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('No autoritzat');
}

require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$pdo = $database->getConnection();

$usuari_id = $_SESSION['user_id'];
$rol_id = $_SESSION['rol_id'];

try {
    if (!isset($_GET['file'])) {
        http_response_code(400);
        die('Paràmetre file requerit');
    }
    
    $file_url = $_GET['file'];
    
    // Validar que el fitxer existeix i l'usuari té permisos
    $sql_check = "SELECT c.sollicitud_id, s.centre_id, c.fitxer_url
                  FROM checklist_items c
                  JOIN sollicituds s ON c.sollicitud_id = s.id
                  WHERE c.fitxer_url = :file_url";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':file_url' => $file_url]);
    $item = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        http_response_code(404);
        die('Fitxer no trobat');
    }
    
    // Verificar permisos (admin o centre propietari)
    if ($rol_id != 1 && $item['centre_id'] != $usuari_id) {
        http_response_code(403);
        die('No tens permisos per descarregar aquest fitxer');
    }
    
    // Construir ruta del fitxer
    $file_path = __DIR__ . '/../../' . ltrim($file_url, '/');
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('Fitxer no trobat al servidor');
    }
    
    // Obtenir informació del fitxer
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    
    $filename = basename($file_path);
    
    // Enviar headers per descarregar
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    
    // Llegir i enviar el fitxer
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
