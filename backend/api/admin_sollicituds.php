<?php
// backend/api/admin_sollicituds.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// --- GET: OBTENER TODAS LAS SOLICITUDES ---
if ($method === 'GET') {
    // JOINs para obtener nombre del centro y del taller
    $query = "SELECT s.id, s.data_preferent, s.nombre_alumnes, s.comentaris, s.estat, s.data_creacio,
                     u.nom_complet as nom_centre, 
                     t.nom as nom_taller, t.modalitat
              FROM sollicituds s
              JOIN usuaris u ON s.centre_id = u.id
              JOIN tallers t ON s.taller_id = t.id
              ORDER BY s.data_creacio DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $sollicituds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sollicituds);
}

// --- PUT: ASIGNAR PROFESOR Y APROBAR ---
else if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    // Caso 1: Solo rechazar (no requiere profesor)
    if($data->estat === 'rebutjada') {
        $query = "UPDATE sollicituds SET estat = 'rebutjada' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id);
        $stmt->execute();
        echo json_encode(["success" => true, "message" => "Rebutjada"]);
        exit();
    }

    // Caso 2: Aprobar y Asignar (Requiere professor_id)
    if($data->estat === 'assignada' && !empty($data->professor_id)) {
        try {
            $db->beginTransaction(); // Iniciamos transacción segura

            // 1. Actualizar estado solicitud
            $query1 = "UPDATE sollicituds SET estat = 'assignada' WHERE id = :id";
            $stmt1 = $db->prepare($query1);
            $stmt1->bindParam(':id', $data->id);
            $stmt1->execute();

            // 2. Crear asignación
            $query2 = "INSERT INTO assignacions (sollicitud_id, professor_id) VALUES (:sol_id, :prof_id)";
            $stmt2 = $db->prepare($query2);
            $stmt2->bindParam(':sol_id', $data->id);
            $stmt2->bindParam(':prof_id', $data->professor_id);
            $stmt2->execute();

            $db->commit(); // Guardamos cambios
            echo json_encode(["success" => true, "message" => "Assignat correctament"]);

        } catch (Exception $e) {
            $db->rollBack(); // Si falla, deshacemos todo
            echo json_encode(["success" => false, "message" => "Error en l'assignació: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Falten dades"]);
    }
}
?>
