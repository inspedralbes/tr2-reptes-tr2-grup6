<?php
/**
 * FeedbackController - Gestiona les valoracions dels tallers
 */
namespace Controllers;

require_once __DIR__ . '/../../config/Database.php';

use Database; // Add use statement if Database is in global namespace or Services

class FeedbackController {
    
    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $db = Database::getInstance();
            
            // Validació bàsica
            if (empty($input['assignment_id']) || empty($input['overall_rating'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Falten dades obligatòries (assignment_id, overall_rating)'
                ]);
                return;
            }

            // Comprovar si ja existeix feedback per aquesta assignació
            $checkSql = 'SELECT id FROM workshop_feedback WHERE assignment_id = ?';
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$input['assignment_id']]);
            if ($checkStmt->fetch()) {
                http_response_code(409); // Conflict
                echo json_encode([
                    'success' => false,
                    'message' => 'Ja existeix una valoració per aquest taller'
                ]);
                return;
            }

            // Obtenir center_id de l'assignació si no ve donat
            if (empty($input['center_id'])) {
                $assignSql = 'SELECT center_id, workshop_id FROM workshop_assignments WHERE id = ?';
                $assignStmt = $db->prepare($assignSql);
                $assignStmt->execute([$input['assignment_id']]);
                $assignment = $assignStmt->fetch();
                
                if (!$assignment) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Assignació no trobada']);
                    return;
                }
                $input['center_id'] = $assignment['center_id'];
                $input['workshop_id'] = $assignment['workshop_id'];
            }

            // Inserir Feedback
            $sql = 'INSERT INTO workshop_feedback (
                assignment_id, center_id, workshop_id,
                overall_rating, content_quality, teacher_performance, organization, relevance,
                would_recommend, expected_attendees, actual_attendees,
                positive_aspects, negative_aspects, suggestions, general_comments,
                is_public, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "submitted")';

            $stmt = $db->prepare($sql);
            $success = $stmt->execute([
                $input['assignment_id'],
                $input['center_id'],
                $input['workshop_id'],
                $input['overall_rating'],
                $input['content_quality'] ?? null,
                $input['teacher_performance'] ?? null,
                $input['organization'] ?? null,
                $input['relevance'] ?? null,
                $input['would_recommend'] ? 1 : 0,
                $input['expected_attendees'] ?? null,
                $input['actual_attendees'] ?? null,
                $input['positive_aspects'] ?? '',
                $input['negative_aspects'] ?? '',
                $input['suggestions'] ?? '',
                $input['general_comments'] ?? '',
                $input['is_public'] ? 1 : 0
            ]);

            if ($success) {
                $feedbackId = $db->lastInsertId();

                // Processar tags si n'hi ha
                if (!empty($input['tags']) && is_array($input['tags'])) {
                    $tagSql = 'INSERT INTO feedback_tag_relations (feedback_id, tag_id) VALUES (?, ?)';
                    $tagStmt = $db->prepare($tagSql);
                    foreach ($input['tags'] as $tagId) {
                        try {
                            $tagStmt->execute([$feedbackId, $tagId]);
                        } catch (Exception $e) {}
                    }
                }

                // Processar alumnes si n'hi ha
                if (!empty($input['students']) && is_array($input['students'])) {
                    $studentSql = 'INSERT INTO feedback_students (feedback_id, student_name) VALUES (?, ?)';
                    $studentStmt = $db->prepare($studentSql);
                    foreach ($input['students'] as $studentName) {
                        if (!empty(trim($studentName))) {
                            $studentStmt->execute([$feedbackId, trim($studentName)]);
                        }
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback enviat correctament',
                    'id' => $feedbackId
                ]);
            } else {
                throw new Exception("Error al guardar el feedback a la base de dades");
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error del servidor: ' . $e->getMessage()
            ]);
        }
    }

    public function list() {
        try {
            $db = Database::getInstance();
            $sql = "SELECT f.*, w.name as workshop_name, c.name as center_name,
                    (SELECT COUNT(*) FROM feedback_students fs WHERE fs.feedback_id = f.id) as student_count
                    FROM workshop_feedback f
                    LEFT JOIN workshop_assignments wa ON f.assignment_id = wa.id
                    LEFT JOIN workshops w ON f.workshop_id = w.id
                    LEFT JOIN centers c ON f.center_id = c.id
                    ORDER BY f.created_at DESC";
            
            $stmt = $db->query($sql);
            $feedbacks = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => $feedbacks
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint feedbacks: ' . $e->getMessage()
            ]);
        }
    }

    public function getTags() {
        try {
            $db = Database::getInstance();
            $sql = 'SELECT * FROM feedback_tags WHERE is_active = 1 ORDER BY category ASC, id ASC';
            $stmt = $db->query($sql);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $tags
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint etiquetes'
            ]);
        }
    }
}
