<?php
// backend/api/alumnes.php
// API REST per gestionar alumnes inscrits

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

$method = $_SERVER['REQUEST_METHOD'];
$usuari_id = $_SESSION['user_id'];
$rol_id = $_SESSION['rol_id'];

try {
    switch ($method) {
        case 'GET':
            // Obtenir alumnes d'una sol·licitud
            if (!isset($_GET['sollicitud_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'sollicitud_id requerit']);
                exit;
            }
            
            $sollicitud_id = (int)$_GET['sollicitud_id'];
            
            // Verificar permisos (admin o centre propietari)
            if ($rol_id != 1) { // No és admin
                $sql_check = "SELECT centre_id FROM sollicituds WHERE id = :id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([':id' => $sollicitud_id]);
                $sollicitud = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$sollicitud || $sollicitud['centre_id'] != $usuari_id) {
                    http_response_code(403);
                    echo json_encode(['error' => 'No tens permisos']);
                    exit;
                }
            }
            
            // Obtenir alumnes
            $sql = "SELECT * FROM alumnes_inscrits 
                    WHERE sollicitud_id = :sollicitud_id 
                    ORDER BY cognoms, nom";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sollicitud_id' => $sollicitud_id]);
            $alumnes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'alumnes' => $alumnes,
                'total' => count($alumnes)
            ]);
            break;
        
        case 'POST':
            // Crear alumne o importar CSV
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['sollicitud_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'sollicitud_id requerit']);
                exit;
            }
            
            $sollicitud_id = (int)$data['sollicitud_id'];
            
            // Verificar permisos
            if ($rol_id != 1) {
                $sql_check = "SELECT centre_id FROM sollicituds WHERE id = :id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([':id' => $sollicitud_id]);
                $sollicitud = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$sollicitud || $sollicitud['centre_id'] != $usuari_id) {
                    http_response_code(403);
                    echo json_encode(['error' => 'No tens permisos']);
                    exit;
                }
            }
            
            // Importació massiva (array d'alumnes)
            if (isset($data['alumnes']) && is_array($data['alumnes'])) {
                $pdo->beginTransaction();
                $inserted = 0;
                
                foreach ($data['alumnes'] as $alumne) {
                    if (!isset($alumne['nom']) || !isset($alumne['cognoms'])) {
                        continue;
                    }
                    
                    $sql = "INSERT INTO alumnes_inscrits 
                            (sollicitud_id, nom, cognoms, curs, grup, email, observacions) 
                            VALUES (:sollicitud_id, :nom, :cognoms, :curs, :grup, :email, :observacions)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':sollicitud_id' => $sollicitud_id,
                        ':nom' => $alumne['nom'],
                        ':cognoms' => $alumne['cognoms'],
                        ':curs' => $alumne['curs'] ?? null,
                        ':grup' => $alumne['grup'] ?? null,
                        ':email' => $alumne['email'] ?? null,
                        ':observacions' => $alumne['observacions'] ?? null
                    ]);
                    
                    $inserted++;
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'inserted' => $inserted,
                    'message' => "S'han afegit {$inserted} alumnes"
                ]);
            } else {
                // Crear alumne individual
                if (!isset($data['nom']) || !isset($data['cognoms'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'nom i cognoms requerits']);
                    exit;
                }
                
                $sql = "INSERT INTO alumnes_inscrits 
                        (sollicitud_id, nom, cognoms, curs, grup, email, observacions) 
                        VALUES (:sollicitud_id, :nom, :cognoms, :curs, :grup, :email, :observacions)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':sollicitud_id' => $sollicitud_id,
                    ':nom' => $data['nom'],
                    ':cognoms' => $data['cognoms'],
                    ':curs' => $data['curs'] ?? null,
                    ':grup' => $data['grup'] ?? null,
                    ':email' => $data['email'] ?? null,
                    ':observacions' => $data['observacions'] ?? null
                ]);
                
                echo json_encode([
                    'success' => true,
                    'alumne_id' => $pdo->lastInsertId(),
                    'message' => 'Alumne afegit correctament'
                ]);
            }
            break;
        
        case 'PUT':
            // Actualitzar alumne
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id requerit']);
                exit;
            }
            
            $alumne_id = (int)$_GET['id'];
            
            // Verificar permisos
            if ($rol_id != 1) {
                $sql_check = "SELECT s.centre_id 
                              FROM alumnes_inscrits a
                              JOIN sollicituds s ON a.sollicitud_id = s.id
                              WHERE a.id = :id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([':id' => $alumne_id]);
                $alumne = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$alumne || $alumne['centre_id'] != $usuari_id) {
                    http_response_code(403);
                    echo json_encode(['error' => 'No tens permisos']);
                    exit;
                }
            }
            
            $sql = "UPDATE alumnes_inscrits 
                    SET nom = :nom, 
                        cognoms = :cognoms, 
                        curs = :curs, 
                        grup = :grup, 
                        email = :email,
                        observacions = :observacions
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $alumne_id,
                ':nom' => $data['nom'],
                ':cognoms' => $data['cognoms'],
                ':curs' => $data['curs'] ?? null,
                ':grup' => $data['grup'] ?? null,
                ':email' => $data['email'] ?? null,
                ':observacions' => $data['observacions'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Alumne actualitzat correctament'
            ]);
            break;
        
        case 'DELETE':
            // Eliminar alumne
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id requerit']);
                exit;
            }
            
            $alumne_id = (int)$_GET['id'];
            
            // Verificar permisos
            if ($rol_id != 1) {
                $sql_check = "SELECT s.centre_id 
                              FROM alumnes_inscrits a
                              JOIN sollicituds s ON a.sollicitud_id = s.id
                              WHERE a.id = :id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([':id' => $alumne_id]);
                $alumne = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$alumne || $alumne['centre_id'] != $usuari_id) {
                    http_response_code(403);
                    echo json_encode(['error' => 'No tens permisos']);
                    exit;
                }
            }
            
            $sql = "DELETE FROM alumnes_inscrits WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $alumne_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Alumne eliminat correctament'
            ]);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Mètode no permès']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
