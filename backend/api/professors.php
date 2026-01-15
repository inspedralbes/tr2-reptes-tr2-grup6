<?php
// backend/api/professors.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();
$db = (new Database())->getConnection();

// Obtener solo usuarios con rol 3 (Profesores)
$query = "SELECT id, nom_complet FROM usuaris WHERE rol_id = 3 AND actiu = 1 ORDER BY nom_complet ASC";
$stmt = $db->prepare($query);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
