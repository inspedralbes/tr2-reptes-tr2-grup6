<?php
// backend/api/admin_talleres.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// --- POST: CREAR NUEVO TALLER ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->nom) && !empty($data->modalitat)) {
        $query = "INSERT INTO tallers (nom, descripcio, modalitat, durada_minuts, capacitat_max, imatge_url, sector_id, estat)
                  VALUES (:nom, :desc, :mod, :dur, :cap, :img, :sec, 'actiu')";
        
        $stmt = $db->prepare($query);
        
        // Asignar valores
        $stmt->bindParam(':nom', $data->nom);
        $stmt->bindParam(':desc', $data->descripcio);
        $stmt->bindParam(':mod', $data->modalitat);
        $stmt->bindParam(':dur', $data->durada);
        $stmt->bindParam(':cap', $data->capacitat);
        $stmt->bindParam(':img', $data->imatge);
        $stmt->bindParam(':sec', $data->sector_id);

        if($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Taller creat correctament"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al crear"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Falten dades obligatòries"]);
    }
}

// --- DELETE: ELIMINAR TALLER (Soft Delete) ---
if ($method === 'DELETE') {
    // Obtenemos ID de la URL: admin_talleres.php?id=5
    $id = isset($_GET['id']) ? $_GET['id'] : die();

    // No borramos la fila, solo cambiamos estado a 'inactiu' para no romper historiales
    $query = "UPDATE tallers SET estat = 'inactiu' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);

    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Taller eliminat (inactiu)"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar"]);
    }
}
?>
