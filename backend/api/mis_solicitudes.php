<?php
// backend/api/mis_solicitudes.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();

// Validar que recibimos el ID del centro
if(!isset($_GET['centre_id'])) {
    http_response_code(400);
    echo json_encode(["message" => "Falta centre_id"]);
    exit();
}

$centre_id = $_GET['centre_id'];

// Consulta: Solicitudes + Nombre del Taller + Estado
$query = "SELECT s.id, s.data_preferent, s.nombre_alumnes, s.estat, s.data_creacio,
                 t.nom as nom_taller, t.modalitat, t.imatge_url
          FROM sollicituds s
          JOIN tallers t ON s.taller_id = t.id
          WHERE s.centre_id = :centre_id
          ORDER BY s.data_creacio DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':centre_id', $centre_id);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($result);
?>
