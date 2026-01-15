<?php
// backend/api/prof_agenda.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_GET['prof_id'])) {
        http_response_code(400);
        exit();
    }

    $prof_id = $_GET['prof_id'];

    $query = "SELECT a.id as assignacio_id, a.data_realitzacio, a.hora_realitzacio, a.estat_execucio,
                     t.nom as nom_taller, t.modalitat,
                     s.curs_grup, s.nombre_alumnes, s.necessitats_especifiques,
                     u.nom_complet as nom_centre, u.email as email_centre
              FROM assignacions a
              JOIN sollicituds s ON a.sollicitud_id = s.id
              JOIN tallers t ON s.taller_id = t.id
              JOIN usuaris u ON s.centre_id = u.id
              WHERE a.professor_id = :pid
              ORDER BY a.data_realitzacio ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':pid', $prof_id);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->assignacio_id) && !empty($data->estat)) {
        $query = "UPDATE assignacions SET estat_execucio = :estat, observacions_prof = :obs WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estat', $data->estat);
        $stmt->bindParam(':obs', $data->observacions);
        $stmt->bindParam(':id', $data->assignacio_id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Error SQL"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Falten dades"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Mètode no permès"]);
?>
