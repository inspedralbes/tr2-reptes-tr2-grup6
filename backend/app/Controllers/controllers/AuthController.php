<?php
/**
 * AuthController - Autenticació bàsica
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private function jsonInput() {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public function login() {
        $payload = array_merge($_POST ?? [], $this->jsonInput());
        $email = $payload['email'] ?? '';
        $password = $payload['password'] ?? '';

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cal proporcionar correu electrònic i contrasenya']);
            return;
        }

        try {
            // Updated to use User Model
            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user || !$userModel->verifyPassword($password, $user['password_hash'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Credencials incorrectes. Torna-ho a provar.']);
                return;
            }

            // Generar JWT amb JwtHandler
            $jwt = new \Utils\JwtHandler();
            $token = $jwt->encode([
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Sessió iniciada correctament',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name'],
                    'center_id' => $user['center_id'],
                    'center_name' => $user['center_name']
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error intern en el servidor',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function logout() {
        echo json_encode([
            'success' => true,
            'message' => 'Sessió tancada'
        ]);
    }
}
