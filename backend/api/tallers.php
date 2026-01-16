<?php
// backend/api/tallers.php
include_once '../config/Cors.php';
include_once '../config/Database.php';
include_once '../models/Taller.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();
$taller = new Taller($db);

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $taller->read();
    $num = $stmt->rowCount();

    if($num > 0) {
        $tallers_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            $item = array(
                "id" => $id,
                "nom" => $nom,
                "descripcio" => $descripcio,
                "modalitat" => $modalitat,
                "durada_minuts" => $durada_minuts,
                "capacitat_max" => $capacitat_max,
                "imatge" => $imatge_url,
                "data" => $data,
                "sector" => array(
                    "id" => $sector_id,
                    "nom" => $sector_nom,
                    "color" => $sector_color,
                    "icona" => $sector_icona
                )
            );
            array_push($tallers_arr, $item);
        }
        echo json_encode($tallers_arr);
    } else {
        echo json_encode(array());
    }
}
?>
