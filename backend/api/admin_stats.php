<?php
// backend/api/admin_stats.php
include_once '../config/Cors.php';
include_once '../config/Database.php';

habilitarCORS();

$database = new Database();
$db = $database->getConnection();

// 1. KPIs Generales
$stmt = $db->query("SELECT COUNT(*) as total FROM sollicituds");
$total_solicitudes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT SUM(nombre_alumnes) as total FROM sollicituds WHERE estat = 'assignada'");
$total_alumnos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as asignadas FROM sollicituds WHERE estat = 'assignada'");
$total_asignadas = $stmt->fetch(PDO::FETCH_ASSOC)['asignadas'];
$tasa_exito = $total_solicitudes > 0 ? round(($total_asignadas / $total_solicitudes) * 100, 1) : 0;

// 2. Demanda por SECTOR
$query_sectores = "SELECT sec.nom, sec.color, COUNT(s.id) as cantidad
                   FROM sollicituds s
                   JOIN tallers t ON s.taller_id = t.id
                   JOIN sectors sec ON t.sector_id = sec.id
                   GROUP BY sec.id, sec.nom, sec.color
                   ORDER BY cantidad DESC";
$stmt = $db->prepare($query_sectores);
$stmt->execute();
$stats_sectores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Demanda por Mes
$query_mes = "SELECT MONTH(data_creacio) as mes, COUNT(*) as cantidad 
              FROM sollicituds 
              GROUP BY mes 
              ORDER BY mes ASC";
$stmt = $db->prepare($query_mes);
$stmt->execute();
$stats_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "kpis" => [
        "solicitudes" => $total_solicitudes,
        "alumnos" => $total_alumnos,
        "tasa_exito" => $tasa_exito
    ],
    "sectores" => $stats_sectores,
    "evolucion" => $stats_mes
]);
?>
