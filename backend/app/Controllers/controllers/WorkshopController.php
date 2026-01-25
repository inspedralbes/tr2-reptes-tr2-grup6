<?php
/**
 * WorkshopController - Llistat de tallers
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Workshop.php';

class WorkshopController {
    public function list() {
        try {
            $workshopModel = new Workshop();
            $rows = $workshopModel->getAll();

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => count($rows)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint tallers',
                'error' => $e->getMessage()
            ]);
        }
    }
}
