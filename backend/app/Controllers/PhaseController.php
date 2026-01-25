<?php

namespace Controllers;

require_once __DIR__ . '/../../config/Database.php';

class PhaseController {
    private $db;
    private $conn;

    public function __construct() {
        try {
            $this->db = new \Database();
            $this->conn = $this->db->connect();
            // Ensure UTF-8 encoding
            $this->conn->exec("SET NAMES utf8mb4");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
        } catch (\Throwable $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Controller Init Error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
        }
    }

    /**
     * Actualitza l'estat de totes les fases basant-se en les dates actuals
     */
    private function updatePhaseStatuses() {
        $today = date('Y-m-d');
        
        // Fases completades (end_date < avui)
        $sql = "UPDATE phases SET status = 'completed' WHERE end_date < :today";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['today' => $today]);
        
        // Fases actives (start_date <= avui <= end_date)
        $sql = "UPDATE phases SET status = 'active' 
                WHERE start_date <= :today1 AND end_date >= :today2";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['today1' => $today, 'today2' => $today]);
        
        // Fases properes (start_date > avui)
        $sql = "UPDATE phases SET status = 'upcoming' WHERE start_date > :today";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['today' => $today]);
    }

    /**
     * Helper to seed phases if missing
     */
    private function seedPhases() {
        $sql = "INSERT INTO phases (id, name, description, start_date, end_date, features) VALUES
        (1, 'Fase 1: Exploració', 
         'Els centres poden explorar el catàleg de tallers disponibles',
         '2025-09-01', '2025-10-15',
         '{\"marketplace_view\": true, \"add_to_cart\": false, \"submit_requests\": false, \"view_allocations\": false, \"schedule_teachers\": false, \"feedback\": false}'),

        (2, 'Fase 2: Llista de Desitjos',
         'Els centres poden crear el seu carret i enviar sol·licituds de tallers',
         '2025-10-16', '2025-11-30',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": false, \"schedule_teachers\": false, \"feedback\": false}'),

        (3, 'Fase 3: La Concordança',
         'Els coordinadors d\'entre tallers i professors assignats',
         '2025-12-01', '2026-01-15',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": false, \"feedback\": false}'),

        (4, 'Fase 4: Calendari',
         'Docents poden agafar els tallers assignats',
         '2026-01-16', '2026-03-31',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": true, \"feedback\": false}'),

        (5, 'Fase 5: Execució',
         'Execució dels tallers programats',
         '2026-04-01', '2026-05-31',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": true, \"feedback\": false}'),

        (6, 'Fase 6: Avaluació',
         'Recollida de feedback i avaluació dels tallers',
         '2026-06-01', '2026-06-30',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": true, \"feedback\": true}')";
         
        $this->conn->exec($sql);
    }

    /**
     * Obté totes les fases
     */
    public function getAll() {
        // Check if empty and seed
        $count = $this->conn->query("SELECT COUNT(*) FROM phases")->fetchColumn();
        if ($count == 0) {
            $this->seedPhases();
        }

        $this->updatePhaseStatuses();
        
        $sql = "SELECT id, name, description, start_date AS startDate, 
                       end_date AS endDate, status, features
                FROM phases
                ORDER BY id ASC";
        
        $stmt = $this->conn->query($sql);
        $phases = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row['features'] = json_decode($row['features'], true);
            $row['featureCount'] = count(array_filter($row['features']));
            
            // Calcular dies restants
            $endDate = new \DateTime($row['endDate']);
            $today = new \DateTime();
            $diff = $today->diff($endDate);
            $row['daysRemaining'] = $diff->invert ? 0 : $diff->days;
            
            $phases[] = $row;
        }
        
        // Calcular estadístiques
        $stats = [
            'totalPhases' => count($phases),
            'completedPhases' => count(array_filter($phases, fn($p) => $p['status'] === 'completed')),
            'activePhases' => count(array_filter($phases, fn($p) => $p['status'] === 'active')),
            'upcomingPhases' => count(array_filter($phases, fn($p) => $p['status'] === 'upcoming'))
        ];
        
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $phases,
            'stats' => $stats
        ]);
    }

    /**
     * Obté la fase actual
     */
    public function getCurrent() {
        $this->updatePhaseStatuses();
        
        $sql = "SELECT id, name, description, start_date AS startDate, 
                       end_date AS endDate, status, features
                FROM phases
                WHERE status = 'active'
                ORDER BY id ASC
                LIMIT 1";
        
        $stmt = $this->conn->query($sql);
        $phase = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$phase) {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No hi ha cap fase activa actualment'
            ]);
            return;
        }
        
        $phase['features'] = json_decode($phase['features'], true);
        
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'phase' => $phase
            ]
        ]);
    }

    /**
     * Obté el roadmap de fases per a timeline
     */
    public function getRoadmap() {
        $this->updatePhaseStatuses();
        
        $sql = "SELECT id, name, description, start_date AS startDate, 
                       end_date AS endDate, status, features
                FROM phases
                ORDER BY id ASC";
        
        $stmt = $this->conn->query($sql);
        $roadmap = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row['features'] = json_decode($row['features'], true);
            $row['featureCount'] = count(array_filter($row['features']));
            
            // Calcular dies restants
            $endDate = new \DateTime($row['endDate']);
            $today = new \DateTime();
            $diff = $today->diff($endDate);
            $row['daysRemaining'] = $diff->invert ? 0 : $diff->days;
            
            $roadmap[] = $row;
        }
        
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $roadmap
        ]);
    }

    /**
     * Actualitza les dates d'una fase (només admin)
     */
    public function updateDates($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        error_log("PhaseController::updateDates called for ID: $id");
        error_log("Input data: " . print_r($input, true));
        
        if (!isset($input['startDate']) || !isset($input['endDate'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Falten les dates (startDate i endDate)'
            ]);
            return;
        }
        
        $startDate = $input['startDate'];
        $endDate = $input['endDate'];
        
        // Validar que endDate > startDate
        if (strtotime($endDate) <= strtotime($startDate)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'La data de finalització ha de ser posterior a la data d\'inici'
            ]);
            return;
        }
        
        $sql = "UPDATE phases 
                SET start_date = :startDate, end_date = :endDate, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt->execute(['startDate' => $startDate, 'endDate' => $endDate, 'id' => $id])) {
            $this->updatePhaseStatuses();
            
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Dates actualitzades correctament'
            ]);
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error actualitzant les dates'
            ]);
        }
    }

    /**
     * Força actualització de l'estat de les fases (admin)
     */
    public function forceUpdate() {
        $this->updatePhaseStatuses();
        
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Estats de fases actualitzats correctament'
        ]);
    }
}
