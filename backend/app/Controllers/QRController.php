<?php
/**
 * QR Controller
 * Path: /backend/app/Controllers/QrController.php
 * 
 * Handles QR generation and scanning for attendance
 */

namespace Controllers;

use Services\Database;
use Models\Allocation;

class QrController {

    /**
     * Lazy init tables if not exist
     */
    private function ensureTablesExist() {
        $db = Database::getInstance();
        
        // Check if table exists (simple try catch select)
        try {
            $db->query("SELECT 1 FROM workshop_qr_codes LIMIT 1");
        } catch (\Exception $e) {
            // Table doesn't exist, create them
            // Note: Using allocations table (not workshop_assignments)
            $sql1 = "CREATE TABLE IF NOT EXISTS workshop_qr_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                allocation_id INT NOT NULL,
                qr_code VARCHAR(100) UNIQUE NOT NULL,
                qr_data TEXT NOT NULL,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                scan_count INT DEFAULT 0,
                last_scanned_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_qr_code (qr_code),
                INDEX idx_allocation (allocation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $sql2 = "CREATE TABLE IF NOT EXISTS qr_scans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                qr_code_id INT NOT NULL,
                allocation_id INT NOT NULL,
                center_id INT NOT NULL,
                scan_type ENUM('check_in', 'check_out', 'verification') DEFAULT 'check_in',
                scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                scanned_by_user_id INT NULL,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_qr_code (qr_code_id),
                INDEX idx_allocation (allocation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $db->query($sql1);
            $db->query($sql2);
        }
    }

    /**
     * Generate a QR code for an assignment
     * POST /api/qr/generate
     */
    public function generate() {
        try {
            $this->ensureTablesExist();
            
            $input = json_decode(file_get_contents('php://input'), true);
            $allocation_id = $input['allocation_id'] ?? null;

            if (!$allocation_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing allocation_id']);
                return;
            }

            // Verify allocation exists
            $allocation = Allocation::findById($allocation_id);
            if (!$allocation) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Allocation not found']);
                return;
            }

            // Generate unique code: QR-{HASH}
            $uniqueString = $allocation_id . time() . rand(1000,9999);
            $qrCode = 'QR-' . strtoupper(substr(md5($uniqueString), 0, 12));
            
            $qrData = json_encode([
                'type' => 'workshop_attendance',
                'assignment_id' => $allocation->id,
                'qr_code' => $qrCode,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            
            $db = Database::getInstance();
            
            // Deactivate previous active QRs for this allocation
            $db->query("UPDATE workshop_qr_codes SET is_active = 0 WHERE allocation_id = ?", [$allocation->id]);
            
            // Insert new QR
            $db->query("INSERT INTO workshop_qr_codes (allocation_id, qr_code, qr_data, is_active) VALUES (?, ?, ?, 1)", 
                [$allocation->id, $qrCode, $qrData]);

            $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrCode);

            echo json_encode([
                'success' => true,
                'data' => [
                    'qr_code' => $qrCode,
                    'qr_image' => $qrImageUrl,
                    'allocation' => $allocation->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Process a scanned QR code
     * POST /api/qr/scan
     */
    public function scan() {
        try {
            $this->ensureTablesExist();
            
            $input = json_decode(file_get_contents('php://input'), true);
            $qr_code = $input['qr_code'] ?? null;
            $scan_type = $input['scan_type'] ?? 'check_in';
            $user_id = $input['user_id'] ?? null;

            if (!$qr_code) throw new \Exception("Codi QR buit");

            $db = Database::getInstance();
            
            // Validate QR
            // Using fetchOne logic (assuming fetch logic implemented in Database class properly now)
            // But avoiding raw fetchOne if params binding logic differs.
            // Let's use standard query+fetch approach.
            $stmt = $db->query("SELECT * FROM workshop_qr_codes WHERE qr_code = ? LIMIT 1", [$qr_code]);
            $qrRecord = $stmt->fetch();
            
            if (!$qrRecord) throw new \Exception("Codi QR no vàlid o no trobat");
            if (!$qrRecord['is_active']) throw new \Exception("Aquest codi QR ha expirat o està desactivat");

            $allocation = Allocation::findById($qrRecord['allocation_id']);
            if (!$allocation) throw new \Exception("Assignació associada no trobada");

            // Record Scan
            $db->query("INSERT INTO qr_scans (qr_code_id, allocation_id, center_id, scan_type, scanned_by_user_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)", 
                [$qrRecord['id'], $allocation->id, $allocation->assigned_center_id ?? 0, $scan_type, $user_id, $_SERVER['REMOTE_ADDR'] ?? '']);
            
            // Increment count
            $db->query("UPDATE workshop_qr_codes SET scan_count = scan_count + 1, last_scanned_at = NOW() WHERE id = ?", [$qrRecord['id']]);
            
            // Fetch names
            $workshop = \Models\Workshop::findById($allocation->assigned_workshop_id);

            echo json_encode([
                'success' => true,
                'message' => 'Assistència registrada correctament',
                'data' => [
                    'scan' => ['scan_type' => $scan_type, 'scanned_at' => date('Y-m-d H:i:s')],
                    'qr_data' => [
                        'workshop_title' => $workshop ? $workshop->name : 'Unknown',
                        'assignment_id' => $allocation->id,
                        'workshop_id' => $workshop ? $workshop->id : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get scan history
     * GET /api/qr/history
     */
    public function history() {
        try {
            $this->ensureTablesExist();
            $db = Database::getInstance();
            
            // Simple History Query
            $sql = "SELECT s.*, w.name as workshop_name, wqr.qr_code 
                    FROM qr_scans s
                    LEFT JOIN workshop_qr_codes wqr ON s.qr_code_id = wqr.id
                    LEFT JOIN allocations a ON s.allocation_id = a.id
                    LEFT JOIN workshops w ON a.assigned_workshop_id = w.id
                    ORDER BY s.scanned_at DESC LIMIT 50";
            
            $stmt = $db->query($sql);
            $results = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
             echo json_encode(['success' => true, 'data' => []]);
        }
    }
}
