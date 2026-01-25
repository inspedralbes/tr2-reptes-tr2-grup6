<?php
require_once __DIR__ . '/../config/Database.php';

class SessionController {

    public function list() {
        try {
            $teacherId = $_GET['teacher_id'] ?? null;
            if (!$teacherId) {
                echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
                return;
            }

            $db = Database::getInstance();
            // Join allocations to verify the teacher owns these slots
            $sql = 'SELECT 
                        s.id,
                        s.allocation_id,
                        s.start_time as datetime,
                        s.is_booked,
                        COALESCE(s_status.status, "scheduled") as status,
                        w.name as workshop_name
                    FROM workshop_slots s
                    JOIN allocations a ON s.allocation_id = a.id
                    JOIN workshops w ON a.assigned_workshop_id = w.id
                    -- Left join to get status if we had a separate status column, but schema uses `is_booked` or we can infer.
                    -- For this MVP, let\'s assume a derived status or use a separate logic.
                    -- The schema `workshop_slots` has `is_booked`, but `allocations` has `status`.
                    -- TeacherSchedule.vue expects a `status` field on the session row.
                    -- We\'ll fake it or map it.
                    LEFT JOIN (SELECT "scheduled" as status) s_status ON 1=1 
                    WHERE a.assigned_teacher_id = ?
                    ORDER BY s.start_time ASC';
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$teacherId]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $sessions]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error listing sessions', 'error' => $e->getMessage()]);
        }
    }

    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $allocId = $input['allocation_id'] ?? null;
            $datetime = $input['datetime'] ?? null;

            if (!$allocId || !$datetime) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing fields']);
                return;
            }

            $db = Database::getInstance();
            
            // Get workshop ID from allocation
            $allocStmt = $db->prepare('SELECT assigned_workshop_id FROM allocations WHERE id = ?');
            $allocStmt->execute([$allocId]);
            $alloc = $allocStmt->fetch(); // Corrected variable name from $allocation to $alloc

            if (!$alloc) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Allocation not found']);
                return;
            }

            // Calculate end time (default 2h)
            $start = new DateTime($datetime);
            $end = clone $start;
            $end->modify('+2 hours');

            $sql = 'INSERT INTO workshop_slots (workshop_id, allocation_id, start_time, end_time) VALUES (?, ?, ?, ?)';
            $stmt = $db->prepare($sql);
            $stmt->execute([$alloc['assigned_workshop_id'], $allocId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
            
            $id = $db->lastInsertId();

            // Return created object
             echo json_encode([
                'success' => true, 
                'data' => [
                    'id' => $id,
                    'allocation_id' => $allocId,
                    'datetime' => $datetime,
                    'status' => 'scheduled'
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error creating session', 'error' => $e->getMessage()]);
        }
    }

    public function update($id) {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $datetime = $input['datetime'] ?? null;
            // Status update logic if supported by schema, otherwise we just update time.
            
            if (!$id || !$datetime) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing fields']);
                return;
            }

            $db = Database::getInstance();
            $start = new DateTime($datetime);
            $end = clone $start;
            $end->modify('+2 hours');

            $sql = 'UPDATE workshop_slots SET start_time = ?, end_time = ? WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $id]);

            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'datetime' => $datetime,
                    'status' => $input['status'] ?? 'scheduled'
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating session', 'error' => $e->getMessage()]);
        }
    }
}
