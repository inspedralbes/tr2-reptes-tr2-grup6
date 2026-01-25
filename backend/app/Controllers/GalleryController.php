<?php

namespace Controllers;

use Services\Database;
use Exception;

class GalleryController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // LIST PUBLIC (Approved photos)
    public function listPublic() {
        try {
            $isFeatured = isset($_GET['is_featured']) && $_GET['is_featured'] === 'true';
            
            $sql = "SELECT wg.*, 
                           w.name as workshop_title, 
                           c.name as center_name 
                    FROM workshop_gallery wg
                    LEFT JOIN workshops w ON wg.workshop_id = w.id
                    LEFT JOIN centers c ON wg.center_id = c.id
                    WHERE wg.is_public = 1 AND wg.is_approved = 1"; // Only approved photos
            
            if ($isFeatured) {
                $sql .= " AND wg.is_featured = 1";
            }
            
            $sql .= " ORDER BY wg.created_at DESC";
            
            $stmt = $this->db->query($sql);
            $photos = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $photos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // LIST PENDING (For Admin)
    public function listPending() {
        try {
            // Verify Admin (Ideally done via middleware, but checking here for safety if route protection fails)
            // Implementation mainly relies on route protection in index.php
            
            $sql = "SELECT wg.*, 
                           w.name as workshop_title, 
                           c.name as center_name,
                           u.full_name as uploader_name
                    FROM workshop_gallery wg
                    LEFT JOIN workshops w ON wg.workshop_id = w.id
                    LEFT JOIN centers c ON wg.center_id = c.id
                    LEFT JOIN users u ON wg.uploaded_by_user_id = u.id
                    WHERE wg.is_approved = 0
                    ORDER BY wg.created_at DESC";
            
            $stmt = $this->db->query($sql);
            $photos = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $photos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // UPLOAD PHOTO
    public function upload() {
        try {
            if (!isset($_FILES['image'])) {
                throw new Exception("No image uploaded (Key missing)");
            }
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $code = $_FILES['image']['error'];
                $msg = "Upload error code: $code";
                // Map common codes
                if ($code === UPLOAD_ERR_INI_SIZE) $msg = "File too large (server limit)";
                if ($code === UPLOAD_ERR_FORM_SIZE) $msg = "File too large (form limit)";
                if ($code === UPLOAD_ERR_PARTIAL) $msg = "File only partially uploaded";
                 if ($code === UPLOAD_ERR_NO_FILE) $msg = "No file was uploaded";
                throw new Exception($msg);
            }
            
            // Get text fields
            $title = $_POST['title'] ?? 'Sense títol';
            $description = $_POST['description'] ?? '';
            $workshopId = $_POST['workshop_id'] ?? null;
            $centerId = $_POST['center_id'] ?? null;
            $userId = $_POST['user_id'] ?? null;
            $photographerName = $_POST['photographer_name'] ?? '';

            if (!$workshopId || !$centerId) {
                throw new Exception("Workshop ID and Center ID are required");
            }
            
            // Handle File
            $uploadDir = BASE_DIR . '/public/uploads/gallery/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($fileExt, $allowed)) {
                throw new Exception("Invalid file type. Allowed: jpg, png, webp");
            }
            
            $filename = uniqid('gallery_') . '.' . $fileExt;
            $destination = $uploadDir . $filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                throw new Exception("Failed to move uploaded file");
            }
            
            $filePath = '/uploads/gallery/' . $filename;
            
            // Insert into DB
            $sql = "INSERT INTO workshop_gallery 
                    (workshop_id, center_id, filename, file_path, title, description, uploaded_by_user_id, photographer_name, is_public, is_approved, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $workshopId, 
                $centerId, 
                $filename, 
                $filePath, 
                $title, 
                $description, 
                $userId, 
                $photographerName
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Foto pujada correctament. Pendent d\'aprovació.',
                'id' => $this->db->lastInsertId()
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // APPROVE PHOTO
    public function approve($id) {
        try {
            // $adminId = $_POST['admin_id']; // Optionally track who approved
            $sql = "UPDATE workshop_gallery SET is_approved = 1, approved_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Foto aprovada']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // REJECT PHOTO
    public function reject($id) {
        try {
            // Get file path first to delete it
            $stmt = $this->db->prepare("SELECT file_path FROM workshop_gallery WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch();
            
            if ($photo) {
                $fullPath = BASE_DIR . '/public' . $photo['file_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            $sql = "DELETE FROM workshop_gallery WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Foto rebutjada i eliminada']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // STATS
    public function stats() {
        try {
            $stats = [
                'total_images' => 0,
                'total_views' => 0,
                'public_images' => 0
            ];
            
            $sql = "SELECT COUNT(*) as total, SUM(view_count) as views FROM workshop_gallery WHERE is_approved = 1";
            $row = $this->db->query($sql)->fetch();
            
            $stats['total_images'] = $row['total'] ?? 0;
            $stats['total_views'] = $row['views'] ?? 0;
            
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'data' => []]);
        }
    }

    // INCREMENT VIEW COUNT
    public function increment($id) {
        try {
            $sql = "UPDATE workshop_gallery SET view_count = view_count + 1 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'View incremented']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // TOGGLE FEATURED
    public function toggleFeatured($id) {
        try {
            // Check current status
            $stmt = $this->db->prepare("SELECT is_featured FROM workshop_gallery WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch();
            
            if (!$photo) throw new Exception("Photo not found");
            
            $newState = $photo['is_featured'] ? 0 : 1;
            
            $sql = "UPDATE workshop_gallery SET is_featured = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newState, $id]);
            
            echo json_encode([
                'success' => true, 
                'message' => $newState ? 'Foto destacada' : 'Foto no destacada',
                'is_featured' => (bool)$newState
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function listAlbums() {
        try {
            // Aggregate photos by workshop to create virtual albums
            $sql = "SELECT 
                        w.id,
                        w.name as title,
                        CONCAT('Taller de ', w.name) as description,
                        '2024-2025' as academic_year,
                        COUNT(wg.id) as image_count,
                        SUM(wg.view_count) as view_count,
                        (SELECT file_path FROM workshop_gallery WHERE workshop_id = w.id AND is_approved = 1 ORDER BY is_featured DESC, created_at DESC LIMIT 1) as cover_image
                    FROM workshop_gallery wg
                    JOIN workshops w ON wg.workshop_id = w.id
                    WHERE wg.is_approved = 1 AND wg.is_public = 1
                    GROUP BY w.id, w.name, w.description";
            
             $stmt = $this->db->query($sql);
             $albums = $stmt->fetchAll();
             
             // Transform for frontend if needed (matches Vue expectation)
             echo json_encode(['success' => true, 'data' => $albums]);
        } catch(Exception $e) {
             // Fallback/Empty
             echo json_encode(['success' => false, 'data' => [], 'error' => $e->getMessage()]); 
        }
    }
}
