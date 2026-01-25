<?php
/**
 * CenterController - Dashboard and center specific logic
 */
namespace Controllers;



use Services\Database;
use Models\Center;
use Models\User;
use PDO;
use Exception;

class CenterController {
    // Cache buster comment
    
    /**
     * List all centers
     * GET /api/centers
     */
    public function list() {
        try {
            $centers = Center::all();
            echo json_encode(['success' => true, 'data' => $centers]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Create center and coordinator
     * POST /api/centers
     */
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Validation
            if (empty($data['name']) || empty($data['code']) || empty($data['coordinator_email'])) {
                throw new Exception("Missing required fields");
            }

            $db = Database::getInstance();
            $db->getPDO()->beginTransaction();

            // Create Center
            $center = Center::create($data);
            if (!$center) throw new Exception("Error creating center");

            // Create Coordinator
            $password = $data['coordinator_password'] ?? 'kairos123';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $user = User::create(
                $data['coordinator_email'],
                $data['coordinator_name'],
                $passwordHash,
                'center_coord',
                $center->id
            );
            
            if (!$user) throw new Exception("Error creating coordinator user");

            $center->setCoordinator($user->id);

            $db->getPDO()->commit();

            echo json_encode(['success' => true, 'message' => 'Center created successfully', 'data' => $center]);

        } catch (Exception $e) {
            if (isset($db)) $db->getPDO()->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update center
     * PUT /api/centers/:id
     */
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $center = Center::findById($id);
            if (!$center) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Center not found']);
                return;
            }

            $center->update($data);
            echo json_encode(['success' => true, 'message' => 'Center updated']);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete center
     * DELETE /api/centers/:id
     */
    public function delete($id) {
        try {
            $center = Center::findById($id);
            if (!$center) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Center not found']);
                return;
            }

            $center->delete();
            echo json_encode(['success' => true, 'message' => 'Center deleted']);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Reset password for center coordinator
     * POST /api/centers/:id/reset-password
     */
    public function resetPassword($id) {
        try {
            $center = Center::findById($id);
            if (!$center || !$center->coordinator_id) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Center or coordinator not found']);
                return;
            }

            $user = User::findById($center->coordinator_id);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Coordinator user not found']);
                return;
            }

            $randomPart = $this->generateComplexString(10);
            $newPassword = 'kairos-' . $randomPart;
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $user->changePassword($passwordHash);

            echo json_encode([
                'success' => true, 
                'message' => 'Password reset successfully',
                'password' => $newPassword
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function generateComplexString($length = 10) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $all = $uppercase . $lowercase . $numbers . $symbols;
        
        $str = '';
        
        // Ensure at least one of each type
        $str .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $str .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $str .= $numbers[random_int(0, strlen($numbers) - 1)];
        $str .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest
        for ($i = 0; $i < $length - 4; $i++) {
            $str .= $all[random_int(0, strlen($all) - 1)];
        }
        
        return str_shuffle($str);
    }
    
    public function stats($centerId) {
        try {
            $db = Database::getInstance();
            
            // 1. Total Requests
            $stmt = $db->query('SELECT COUNT(*) as total FROM requests WHERE center_id = ?', [$centerId]);
            $totalRequests = $stmt->fetchColumn();
            
            // 2. Total Teachers
            $stmt = $db->query('SELECT COUNT(*) as total FROM users WHERE center_id = ? AND role = "teacher"', [$centerId]);
            $totalTeachers = $stmt->fetchColumn();
            
            // 3. Completed Bookings
            $stmt = $db->query('SELECT COUNT(*) as total FROM requests WHERE center_id = ? AND status = "completed"', [$centerId]);
            $completedBookings = $stmt->fetchColumn();
            
            // 4. Average Rating
            $stmt = $db->query('SELECT AVG(overall_score) as avg_rating FROM surveys WHERE respondent_role = "center" AND allocation_id IN (SELECT id FROM allocations WHERE assigned_center_id = ?)', [$centerId]);
            $avgRating = $stmt->fetchColumn();
            
            // 5. Recent Bookings (Requests)
            $sql = 'SELECT r.id, w.name as workshop_name, r.created_at, r.status, r.updated_at
                    FROM requests r
                    JOIN workshops w ON w.id = r.workshop_id
                    WHERE r.center_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 5';
            $recentBookings = $db->fetchAll($sql, [$centerId]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_bookings' => (int)$totalRequests,
                    'total_teachers' => (int)$totalTeachers,
                    'completed_bookings' => (int)$completedBookings,
                    'average_rating' => $avgRating ? number_format($avgRating, 1) : '0.0',
                    'recent_bookings' => $recentBookings
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error getting dashboard stats',
                'error' => $e->getMessage()
            ]);
        }
    }
}
