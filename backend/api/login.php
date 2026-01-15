<?php
// backend/api/login.php

// 1. Incluir configuraciones
include_once '../config/Cors.php';
include_once '../config/Database.php';
include_once '../models/Usuari.php';

// 2. Habilitar CORS
habilitarCORS();

// 3. Obtener conexión a BBDD
$database = new Database();
$db = $database->getConnection();

// 4. Instanciar objeto Usuario
$usuari = new Usuari($db);

// 5. Obtener datos enviados por Vue (JSON)
// Nota: $_POST no funciona con JSON enviado por Axios/Fetch, hay que usar file_get_contents
$data = json_decode(file_get_contents("php://input"));

// Verificar que llegan datos
if (!empty($data->email) && !empty($data->password)) {
    
    $usuari->email = $data->email;
    $usuari->password = $data->password;

    // Intentar Login
    if ($usuari->login()) {
        // LOGIN EXITOSO
        http_response_code(200);
        echo json_encode(array(
            "message" => "Login exitoso",
            "success" => true,
            "user" => array(
                "id" => $usuari->id,
                "nom" => $usuari->nom_complet,
                "email" => $usuari->email,
                "rol" => $usuari->rol_id,
                "centre" => $usuari->codi_centre
            ),
            // En un proyecto real aquí generaríamos un JWT Token
            "token" => bin2hex(random_bytes(16)) 
        ));
    } else {
        // LOGIN FALLIDO (Pass incorrecto o usuario no existe)
        http_response_code(401); // Unauthorized
        echo json_encode(array("message" => "Credencials incorrectes", "success" => false));
    }
} else {
    // DATOS INCOMPLETOS
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Falten dades", "success" => false));
}
?>
