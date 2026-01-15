<?php
// backend/api/export_alumnes.php
// API per exportar llista d'alumnes en CSV o PDF

session_start();
header('Content-Type: text/csv; charset=UTF-8');

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

try {
    if (!isset($_GET['sollicitud_id'])) {
        http_response_code(400);
        die('sollicitud_id requerit');
    }
    
    $sollicitud_id = (int)$_GET['sollicitud_id'];
    $format = $_GET['format'] ?? 'csv'; // csv o pdf
    
    // Verificar permisos
    if ($rol_id != 1) {
        $sql_check = "SELECT centre_id FROM sollicituds WHERE id = :id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':id' => $sollicitud_id]);
        $sollicitud = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$sollicitud || $sollicitud['centre_id'] != $usuari_id) {
            http_response_code(403);
            die('No tens permisos');
        }
    }
    
    // Obtenir dades de la sol·licitud i alumnes
    $sql_info = "SELECT t.nom as taller_nom, u.nom_complet as centre_nom
                 FROM sollicituds s
                 JOIN tallers t ON s.taller_id = t.id
                 JOIN usuaris u ON s.centre_id = u.id
                 WHERE s.id = :id";
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':id' => $sollicitud_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    $sql_alumnes = "SELECT nom, cognoms, curs, grup, email 
                    FROM alumnes_inscrits 
                    WHERE sollicitud_id = :sollicitud_id 
                    ORDER BY cognoms, nom";
    $stmt_alumnes = $pdo->prepare($sql_alumnes);
    $stmt_alumnes->execute([':sollicitud_id' => $sollicitud_id]);
    $alumnes = $stmt_alumnes->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        // Exportar com a CSV
        $filename = 'alumnes_' . $sollicitud_id . '_' . date('Y-m-d') . '.csv';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // BOM per UTF-8
        echo "\xEF\xBB\xBF";
        
        // Capçalera
        echo "Taller: {$info['taller_nom']}\n";
        echo "Centre: {$info['centre_nom']}\n";
        echo "Data exportació: " . date('d/m/Y H:i') . "\n\n";
        
        // Columnes
        echo "Nom,Cognoms,Curs,Grup,Email\n";
        
        // Dades
        foreach ($alumnes as $alumne) {
            echo '"' . $alumne['nom'] . '",';
            echo '"' . $alumne['cognoms'] . '",';
            echo '"' . ($alumne['curs'] ?? '') . '",';
            echo '"' . ($alumne['grup'] ?? '') . '",';
            echo '"' . ($alumne['email'] ?? '') . '"';
            echo "\n";
        }
    } else {
        // TODO: Implementar exportació PDF amb FPDF o similar
        http_response_code(501);
        die('Format PDF encara no implementat');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
