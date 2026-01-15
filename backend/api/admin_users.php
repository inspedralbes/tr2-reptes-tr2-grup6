<?php
// backend/api/admin_users.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = "SELECT u.id, u.nom_complet, u.email, u.rol_id, r.nom AS nom_rol
              FROM usuaris u
              JOIN roles r ON u.rol_id = r.id
              ORDER BY u.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->nom) && !empty($data->email) && !empty($data->password) && !empty($data->rol)) {
        // Verificar email único
        $check = $db->prepare("SELECT id FROM usuaris WHERE email = :email");
        $check->bindParam(':email', $data->email);
        $check->execute();
        if ($check->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Aquest email ja existeix."]);
            exit();
        }

        $query = "INSERT INTO usuaris (nom_complet, email, password_hash, rol_id) VALUES (:nom, :email, :pass, :rol)";
        $stmt = $db->prepare($query);
        $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

        $stmt->bindParam(':nom', $data->nom);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':pass', $password_hash);
        $stmt->bindParam(':rol', $data->rol);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Usuari creat correctament"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error SQL"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Falten dades"]);
    }
    exit();
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Falta id"]);
        exit();
    }
    if ($id == 1) {
        echo json_encode(["success" => false, "message" => "No pots esborrar el SuperAdmin"]);
        exit();
    }

    $query = "DELETE FROM usuaris WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al esborrar"]);
    }
    exit();
}

echo json_encode(["success" => false, "message" => "Mètode no permès"]);
?>
