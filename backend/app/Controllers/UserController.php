<?php
/**
 * UserController - Llistat d'usuaris
 */
require_once __DIR__ . '/../config/Database.php';

class UserController {
    public function list() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query('SELECT id, email, role, full_name FROM users ORDER BY id ASC');
            $rows = $stmt->fetchAll();
            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => count($rows)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint usuaris',
                'error' => $e->getMessage()
            ]);
        }
    }
}
