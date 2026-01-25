<?php
/**
 * TeacherController - CRUD per a docents
 */

namespace Controllers;

use Services\Database;

class TeacherController {
    private function buildTeacherRow($row) {
        return [
            'id' => (int)$row['id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'center_id' => $row['center_id'] !== null ? (int)$row['center_id'] : null,
            'center_name' => $row['center_name'] ?? null,
            'center_code' => $row['center_code'] ?? null,
            'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true
        ];
    }

    private function fetchTeacherById($id) {
        $db = Database::getInstance();
        $row = $db->prepare('SELECT t.id, t.full_name, t.email, t.center_id,
                              c.name AS center_name, c.code AS center_code,
                              u.is_active
                              FROM teachers t
                              LEFT JOIN centers c ON c.id = t.center_id
                              LEFT JOIN users u ON u.email = t.email COLLATE utf8mb4_unicode_ci
                              WHERE t.id = ?');
        $row->execute([$id]);
        $found = $row->fetch();
        return $found ? $this->buildTeacherRow($found) : null;
    }

    public function list() {
        try {
            $centerId = $_GET['center_id'] ?? null;
            
            // Fallback: Si no arriba per $_GET normal, intentem parsejar la QUERY_STRING
            if (!$centerId && isset($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $query);
                $centerId = $query['center_id'] ?? null;
            }
            
            // Fallback 2: Parsejar manualment des de REQUEST_URI (per si el servidor reescriu malament)
            if (!$centerId && isset($_SERVER['REQUEST_URI'])) {
                $parts = parse_url($_SERVER['REQUEST_URI']);
                if (isset($parts['query'])) {
                    parse_str($parts['query'], $query);
                    $centerId = $query['center_id'] ?? null;
                }
            }

            $db = Database::getInstance();
            $sql = 'SELECT t.id, t.full_name, t.email, t.center_id,
                    c.name AS center_name, c.code AS center_code,
                    u.is_active
                    FROM teachers t
                    LEFT JOIN centers c ON c.id = t.center_id
                    LEFT JOIN users u ON u.email = t.email COLLATE utf8mb4_unicode_ci';
            
            if ($centerId) {
                $sql .= ' WHERE t.center_id = ?';
                $params = [$centerId];
            } else {
                $params = [];
            }

            $sql .= ' ORDER BY t.id ASC';
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = array_map(fn($r) => $this->buildTeacherRow($r), $stmt->fetchAll());
            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => count($rows)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint docents',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $teacher = $this->fetchTeacherById($id);
            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Docent no trobat']);
                return;
            }
            echo json_encode(['success' => true, 'data' => $teacher]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint docent',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['full_name']) || empty($input['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nom i email són obligatoris']);
            return;
        }

        try {
            $db = Database::getInstance();
            $sql = 'INSERT INTO teachers (full_name, email, center_id) VALUES (?, ?, ?)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['full_name'],
                $input['email'],
                $input['center_id'] ?? null
            ]);
            $id = (int)$db->lastInsertId();

            // Crear usuari si no existeix
            $userCheck = $db->prepare('SELECT id FROM users WHERE email = ?');
            $userCheck->execute([$input['email']]);
            if (!$userCheck->fetch()) {
                // Generate random password if not provided
                if (!empty($input['password'])) {
                    $plainPassword = $input['password'];
                } else {
                    $plainPassword = 'kairos-' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
                }
                $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
                
                $userSql = 'INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?)';
                $db->prepare($userSql)->execute([
                    $input['email'], 
                    $passwordHash, 
                    $input['full_name'], 
                    'teacher', 
                    1
                ]);
            }

            $teacher = $this->fetchTeacherById($id);
            echo json_encode(['success' => true, 'data' => $teacher, 'generated_password' => isset($plainPassword) ? $plainPassword : null]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creant docent',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $db = Database::getInstance();
            $check = $db->prepare('SELECT id FROM teachers WHERE id = ?');
            $check->execute([$id]);
            if (!$check->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Docent no trobat']);
                return;
            }

            $sql = 'UPDATE teachers SET full_name = COALESCE(?, full_name), email = COALESCE(?, email), center_id = ? WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['full_name'] ?? null,
                $input['email'] ?? null,
                $input['center_id'] ?? null,
                $id
            ]);

            // Update is_active in users table
            if (isset($input['is_active'])) {
                // Get email from teacher to find user
                $getEmail = $db->prepare("SELECT email FROM teachers WHERE id = ?");
                $getEmail->execute([$id]);
                $emailRow = $getEmail->fetch();
                $teacherEmail = $emailRow ? $emailRow['email'] : null;
                
                if ($teacherEmail) {
                    $updUser = $db->prepare("UPDATE users SET is_active = ? WHERE email = ?");
                    $updUser->execute([$input['is_active'] ? 1 : 0, $teacherEmail]);
                }
            }

            $teacher = $this->fetchTeacherById($id);
            echo json_encode(['success' => true, 'data' => $teacher]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error actualitzant docent',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function delete($id) {
        try {
            $db = Database::getInstance();
            $row = $db->prepare('SELECT id, full_name, email, center_id FROM teachers WHERE id = ?');
            $row->execute([$id]);
            $teacher = $row->fetch();
            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Docent no trobat']);
                return;
            }

            $stmt = $db->prepare('DELETE FROM teachers WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $this->buildTeacherRow($teacher)]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error eliminant docent',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function resetPassword($id) {
        try {
            $db = Database::getInstance();
            $row = $db->prepare('SELECT email, full_name FROM teachers WHERE id = ?');
            $row->execute([$id]);
            $teacher = $row->fetch();
            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Docent no trobat']);
                return;
            }

            $email = $teacher['email'];
            $fullName = $teacher['full_name'];
            $plain = 'kairos-' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
            $hash = password_hash($plain, PASSWORD_BCRYPT);

            // Si l'usuari existeix, actualitzar; si no, crear
            $userCheck = $db->prepare('SELECT id FROM users WHERE email = ?');
            $userCheck->execute([$email]);
            if ($userCheck->fetch()) {
                $upd = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
                $upd->execute([$hash, $email]);
            } else {
                $userSql = 'INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?)';
                $db->prepare($userSql)->execute([$email, $hash, $fullName, 'teacher', 1]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Contrasenya restablerta',
                'password' => $plain
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error restablint contrasenya',
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
