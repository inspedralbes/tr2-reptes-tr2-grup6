<?php
// backend/api/sectors.php
// API para obtener los 11 sectores profesionales del programa ENGINY

include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();

// Obtener todos los sectores activos
$query = "SELECT id, nom, color, icona FROM sectors WHERE actiu = 1 ORDER BY nom ASC";
$stmt = $db->prepare($query);
$stmt->execute();

$sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($sectors);
?>
