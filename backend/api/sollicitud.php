<?php
// backend/api/sollicitud.php
include_once '../config/Cors.php';
include_once '../config/Database.php';
include_once '../models/Sollicitud.php';

habilitarCORS();

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$sollicitud = new Sollicitud($db);

$data = json_decode(file_get_contents("php://input"));

// Validar que llegan los datos obligatorios
if(
    !empty($data->centre_id) &&
    !empty($data->taller_id) &&
    !empty($data->nombre_alumnes)
){
    $sollicitud->centre_id = $data->centre_id;
    $sollicitud->taller_id = $data->taller_id;
    $sollicitud->data_preferent = $data->data_preferent; // Puede ser null
    $sollicitud->nombre_alumnes = $data->nombre_alumnes;
    $sollicitud->comentaris = $data->comentaris;
    $sollicitud->curs_grup = isset($data->curs_grup) ? $data->curs_grup : '';
    $sollicitud->necessitats = isset($data->necessitats_especifiques) ? $data->necessitats_especifiques : '';
    $sollicitud->preferencia_dates = isset($data->preferencia_dates) ? $data->preferencia_dates : '';

    if($sollicitud->create()){
        http_response_code(201); // Created
        echo json_encode(array("message" => "Sol·licitud creada correctament.", "success" => true));
    } else {
        http_response_code(503); // Service Unavailable
        echo json_encode(array("message" => "No s'ha pogut crear la sol·licitud.", "success" => false));
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Falten dades.", "success" => false));
}
?>
