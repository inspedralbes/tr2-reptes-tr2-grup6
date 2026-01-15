<?php
/**
 * API: admin_assignacion.php
 * 
 * FASE 3: Algoritmo de Asignación Inteligente
 * 
 * Endpoints:
 * GET /api/admin_assignacion.php?action=calcular_prioridades
 *     - Calcula puntuaciones de prioridad para todas las solicitudes pendientes
 *     - Retorna: {success: true, solicitudes_evaluadas: N, resultado: [...]}
 * 
 * POST /api/admin_assignacion.php?action=ejecutar_asignacion
 *     - Ejecuta el algoritmo de asignación inteligente
 *     - Retorna: {success: true, asignaciones_creadas: N, rechazadas: N}
 * 
 * GET /api/admin_assignacion.php?action=ver_estado_asignacion
 *     - Retorna estado actual de asignaciones (gráficos tipo donut)
 */

session_start();
header('Content-Type: application/json');

// Validar sesión admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../config/Database.php';

class AsignadorInteligente {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * PASO 1: Calcular puntuaciones de prioridad
     */
    public function calcularPrioridades() {
        try {
            $sql = "SELECT 
                        s.id as sollicitud_id,
                        s.centre_id,
                        s.taller_id,
                        t.nom as taller_nom,
                        c.nom_complet as centre_nom,
                        t.capacitat_max,
                        COALESCE(h.vegades_realitzat, 0) as vegades_realitzat,
                        COALESCE(h.anys_sense_fer, 999) as anys_sense_fer,
                        CASE 
                            WHEN h.vegades_realitzat IS NULL THEN 80
                            WHEN h.anys_sense_fer >= 2 THEN 60
                            WHEN h.anys_sense_fer = 1 THEN 40
                            ELSE 20
                        END as puntuacion_equidad,
                        DATEDIFF(CURDATE(), s.data_creacio) as dias_desde_solicitud,
                        CASE 
                            WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 7 THEN 10
                            WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 30 THEN 5
                            ELSE 0
                        END as puntuacion_rapidez,
                        CASE 
                            WHEN h.vegades_realitzat IS NULL THEN 80
                            WHEN h.anys_sense_fer >= 2 THEN 60
                            WHEN h.anys_sense_fer = 1 THEN 40
                            ELSE 20
                        END + CASE 
                            WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 7 THEN 10
                            WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 30 THEN 5
                            ELSE 0
                        END as puntuacion_total
                    FROM sollicituds s
                    LEFT JOIN tallers t ON s.taller_id = t.id
                    LEFT JOIN usuaris c ON s.centre_id = c.id
                    LEFT JOIN vw_consumo_centre_taller h ON s.centre_id = h.centre_id AND s.taller_id = h.taller_id
                    WHERE s.estat = 'pendent'
                    ORDER BY s.taller_id, puntuacion_total DESC, s.data_creacio ASC";
            
            $stmt = $this->pdo->query($sql);
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'solicitudes_evaluadas' => count($solicitudes),
                'resultado' => $solicitudes
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * PASO 2: Ejecutar asignación inteligente
     * 
     * Lógica:
     * 1. Agrupar solicitudes por taller
     * 2. Para cada taller, ordenar por puntuación
     * 3. Asignar hasta llenar capacidad
     * 4. Rechazar resto
     */
    public function ejecutarAsignacion() {
        try {
            $this->pdo->beginTransaction();
            
            $asignaciones_creadas = 0;
            $rechazadas = 0;
            
            // 1. Obtener todos los talleres con solicitudes pendientes
            $sql_talleres = "SELECT DISTINCT t.id, t.nom, t.capacitat_max
                            FROM tallers t
                            INNER JOIN sollicituds s ON t.id = s.taller_id
                            WHERE s.estat = 'pendent'
                            ORDER BY t.id";
            
            $stmt_talleres = $this->pdo->query($sql_talleres);
            $talleres = $stmt_talleres->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($talleres as $taller) {
                $capacidad = $taller['capacitat_max'];
                $taller_id = $taller['id'];
                
                // 2. Obtener solicitudes ordenadas por prioridad
                $sql_sols = "SELECT s.id, s.centre_id, 
                                    CASE 
                                        WHEN h.vegades_realitzat IS NULL THEN 80
                                        WHEN h.anys_sense_fer >= 2 THEN 60
                                        WHEN h.anys_sense_fer = 1 THEN 40
                                        ELSE 20
                                    END + CASE 
                                        WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 7 THEN 10
                                        WHEN DATEDIFF(CURDATE(), s.data_creacio) <= 30 THEN 5
                                        ELSE 0
                                    END as puntuacion
                            FROM sollicituds s
                            LEFT JOIN vw_consumo_centre_taller h ON s.centre_id = h.centre_id AND s.taller_id = h.taller_id
                            WHERE s.taller_id = :taller_id AND s.estat = 'pendent'
                            ORDER BY puntuacion DESC, s.data_creacio ASC";
                
                $stmt_sols = $this->pdo->prepare($sql_sols);
                $stmt_sols->execute([':taller_id' => $taller_id]);
                $solicitudes = $stmt_sols->fetchAll(PDO::FETCH_ASSOC);
                
                // 3. Asignar hasta llenar capacidad
                foreach ($solicitudes as $i => $sol) {
                    if ($i < $capacidad) {
                        // ASIGNAR: Crear registro en assignacions (sin profesor específico aún)
                        // El profesor será seleccionado en FASE 4 (Agendamiento)
                        $sql_assign = "INSERT INTO assignacions (sollicitud_id, professor_id, es_principal, data_assignacio)
                                      VALUES (:sollicitud_id, 1, 1, NOW())";
                        
                        $stmt_assign = $this->pdo->prepare($sql_assign);
                        $stmt_assign->execute([':sollicitud_id' => $sol['id']]);
                        
                        // Actualizar estado sollicitud
                        $sql_upd = "UPDATE sollicituds SET estat = 'assignada' WHERE id = :id";
                        $stmt_upd = $this->pdo->prepare($sql_upd);
                        $stmt_upd->execute([':id' => $sol['id']]);
                        
                        // INTEGRACIÓN: Enviar notificación y email
                        require_once __DIR__ . '/../utils/NotificationService.php';
                        require_once __DIR__ . '/../utils/EmailService.php';
                        
                        $notificationService = new NotificationService();
                        $emailService = new EmailService();
                        
                        $notificationService->notificarAsignacio($sol['id']);
                        
                        // Obtener datos del centre per enviar email
                        $sql_centre = "SELECT u.email, u.nom_complet, t.nom as taller_nom
                                      FROM sollicituds s
                                      JOIN usuaris u ON s.centre_id = u.id
                                      JOIN tallers t ON s.taller_id = t.id
                                      WHERE s.id = :id";
                        $stmt_centre = $this->pdo->prepare($sql_centre);
                        $stmt_centre->execute([':id' => $sol['id']]);
                        $centre_data = $stmt_centre->fetch(PDO::FETCH_ASSOC);
                        
                        if ($centre_data) {
                            $emailService->enviarEmailAsignacio(
                                $centre_data['email'],
                                $centre_data['nom_complet'],
                                $centre_data['taller_nom']
                            );
                        }
                        
                        $asignaciones_creadas++;
                    } else {
                        // RECHAZAR
                        $sql_rej = "UPDATE sollicituds SET estat = 'rebutjada' WHERE id = :id";
                        $stmt_rej = $this->pdo->prepare($sql_rej);
                        $stmt_rej->execute([':id' => $sol['id']]);
                        
                        // INTEGRACIÓN: Enviar notificación y email de rechazo
                        require_once __DIR__ . '/../utils/NotificationService.php';
                        require_once __DIR__ . '/../utils/EmailService.php';
                        
                        $notificationService = new NotificationService();
                        $emailService = new EmailService();
                        
                        $notificationService->notificarRebuig($sol['id'], 'Capacitat completa');
                        
                        // Obtener datos del centre per enviar email
                        $sql_centre = "SELECT u.email, u.nom_complet, t.nom as taller_nom
                                      FROM sollicituds s
                                      JOIN usuaris u ON s.centre_id = u.id
                                      JOIN tallers t ON s.taller_id = t.id
                                      WHERE s.id = :id";
                        $stmt_centre = $this->pdo->prepare($sql_centre);
                        $stmt_centre->execute([':id' => $sol['id']]);
                        $centre_data = $stmt_centre->fetch(PDO::FETCH_ASSOC);
                        
                        if ($centre_data) {
                            $emailService->enviarEmailRebuig(
                                $centre_data['email'],
                                $centre_data['nom_complet'],
                                $centre_data['taller_nom'],
                                'Capacitat completa'
                            );
                        }
                        
                        $rechazadas++;
                    }
                }
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'asignaciones_creadas' => $asignaciones_creadas,
                'rechazadas' => $rechazadas
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Ver estado actual de asignaciones
     */
    public function verEstadoAsignacion() {
        try {
            $sql = "SELECT 
                        COUNT(CASE WHEN s.estat = 'pendent' THEN 1 END) as pendentes,
                        COUNT(CASE WHEN s.estat = 'assignada' THEN 1 END) as asignadas,
                        COUNT(CASE WHEN s.estat = 'rebutjada' THEN 1 END) as rebutjades,
                        COUNT(CASE WHEN s.estat = 'realitzada' THEN 1 END) as realitzades
                    FROM sollicituds s";
            
            $stmt = $this->pdo->query($sql);
            $estado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Datos por sector
            $sql_sector = "SELECT 
                            se.nom as sector,
                            se.color,
                            COUNT(CASE WHEN s.estat = 'pendent' THEN 1 END) as pendentes,
                            COUNT(CASE WHEN s.estat = 'assignada' THEN 1 END) as asignadas,
                            COUNT(CASE WHEN s.estat = 'rebutjada' THEN 1 END) as rebutjades
                        FROM sollicituds s
                        LEFT JOIN tallers t ON s.taller_id = t.id
                        LEFT JOIN sectors se ON t.sector_id = se.id
                        GROUP BY t.sector_id, se.nom, se.color";
            
            $stmt_sector = $this->pdo->query($sql_sector);
            $por_sector = $stmt_sector->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estado_global' => $estado,
                'por_sector' => $por_sector
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

// Instanciar
$asignador = new AsignadorInteligente($pdo);

// Procesar acción
$action = $_GET['action'] ?? null;

switch ($action) {
    case 'calcular_prioridades':
        echo json_encode($asignador->calcularPrioridades());
        break;
    
    case 'ejecutar_asignacion':
        echo json_encode($asignador->ejecutarAsignacion());
        break;
    
    case 'ver_estado_asignacion':
        echo json_encode($asignador->verEstadoAsignacion());
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no especificada']);
}
?>
