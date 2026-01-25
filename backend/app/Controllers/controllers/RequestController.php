<?php
/**
 * RequestController - CRUD for workshop requests
 */
require_once __DIR__ . '/../config/Database.php';

class RequestController {
    
    public function list() {
        try {
            $db = Database::getInstance();
            $sql = 'SELECT r.id, r.center_id, r.workshop_id, r.period_id, r.num_students, 
                           r.priority_level, r.status, r.created_at, r.updated_at,
                           c.name as center_name, c.code as center_code,
                           w.name as workshop_name
                    FROM requests r
                    LEFT JOIN centers c ON c.id = r.center_id
                    LEFT JOIN workshops w ON w.id = r.workshop_id
                    ORDER BY r.created_at DESC';
            $stmt = $db->query($sql);
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
                'message' => 'Error listing requests',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function listByCenter($center_id) {
        try {
            $db = Database::getInstance();
            $sql = 'SELECT r.id, r.center_id, r.workshop_id, r.period_id, r.num_students, 
                           r.priority_level, r.status, r.created_at, r.updated_at,
                           c.name as center_name, c.code as center_code,
                           w.name as workshop_name
                    FROM requests r
                    LEFT JOIN centers c ON c.id = r.center_id
                    LEFT JOIN workshops w ON w.id = r.workshop_id
                    WHERE r.center_id = ?
                    ORDER BY r.created_at DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute([$center_id]);
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
                'message' => 'Error listing center requests',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function show($id) {
        try {
            $db = Database::getInstance();
            $sql = 'SELECT r.id, r.center_id, r.workshop_id, r.period_id, r.num_students, 
                           r.priority_level, r.status, r.rejection_reason, r.created_at, r.updated_at,
                           c.name as center_name, c.code as center_code,
                           w.name as workshop_name, w.description as workshop_description
                    FROM requests r
                    LEFT JOIN centers c ON c.id = r.center_id
                    LEFT JOIN workshops w ON w.id = r.workshop_id
                    WHERE r.id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => $row]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching request',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (empty($input['center_id']) || empty($input['workshop_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'center_id and workshop_id are required']);
            return;
        }
        
        try {
            $db = Database::getInstance();
            
            // Check if request already exists
            $check = $db->prepare('SELECT id FROM requests WHERE center_id = ? AND workshop_id = ? AND status != "rejected"');
            $check->execute([$input['center_id'], $input['workshop_id']]);
            if ($check->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Request already exists for this center and workshop']);
                return;
            }
            
            $sql = 'INSERT INTO requests (center_id, workshop_id, period_id, num_students, priority_level, status) 
                    VALUES (?, ?, ?, ?, ?, ?)';
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $input['center_id'],
                $input['workshop_id'],
                $input['period_id'] ?? null,
                $input['num_students'] ?? 1,
                $input['priority_level'] ?? 0,
                'pending'
            ]);
            
            if ($result) {
                $id = $db->lastInsertId();
                $this->show($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to create request']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating request',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function updateStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (empty($input['status'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'status is required']);
            return;
        }
        
        try {
            $db = Database::getInstance();
            $status = $input['status']; // pending, assigned, rejected, cancelled
            $reason = $input['rejection_reason'] ?? null;
            
            $sql = 'UPDATE requests SET status = ?, rejection_reason = ? WHERE id = ?';
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$status, $reason, $id]);
            
            if ($result) {
                $this->show($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to update request']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating request',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function delete($id) {
        try {
            $db = Database::getInstance();
            
            // Check if exists
            $check = $db->prepare('SELECT id FROM requests WHERE id = ?');
            $check->execute([$id]);
            if (!$check->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                return;
            }
            
            $stmt = $db->prepare('DELETE FROM requests WHERE id = ?');
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Request deleted']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to delete request']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting request',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getStats() {
        try {
            $db = Database::getInstance();
            
            $sql = 'SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as assigned,
                        SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
                    FROM requests';
            $stmt = $db->query($sql);
            $stats = $stmt->fetch();
            
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching stats',
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
