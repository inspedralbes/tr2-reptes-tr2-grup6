<?php
/**
 * Controlador d'Autenticació
 * Path: /backend/app/Controllers/AuthController.php
 * 
 * Gestió de login, registre i autenticació JWT
 */

namespace Controllers;

use Utils\JwtHandler;
use Services\PasswordHasher;
use Services\Database;

class AuthController {
    
    private $db;
    private $jwtHandler;
    
    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
        $this->jwtHandler = new JwtHandler();
    }
    
    /**
     * Login d'usuari
     * POST /api/auth/login
     */
    public function login() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar entrada
            if (!isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email i contrasenya són obligatoris',
                    'code' => 'MISSING_FIELDS'
                ]);
                return;
            }
            
            // Validar format email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Format d\'email no vàlid',
                    'code' => 'INVALID_EMAIL'
                ]);
                return;
            }
            
            // Buscar usuari a BD amb nom del centre
            $stmt = $this->db->prepare('
                SELECT u.id, u.email, u.password_hash, u.role, u.full_name, u.is_active, u.center_id, c.name as center_name 
                FROM users u 
                LEFT JOIN centers c ON u.center_id = c.id 
                WHERE u.email = ?
            ');
            $stmt->execute([$input['email']]);
            $user = $stmt->fetch();
            
            // Usuari no trobat
            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email o contrasenya incorrectes',
                    'code' => 'INVALID_CREDENTIALS'
                ]);
                return;
            }
            
            // Usuari inactiu
            if (!$user['is_active']) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Compte desactivat',
                    'code' => 'ACCOUNT_DISABLED'
                ]);
                return;
            }
            
            // Verificar contrasenya
            if (!PasswordHasher::verify($input['password'], $user['password_hash'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email o contrasenya incorrectes',
                    'code' => 'INVALID_CREDENTIALS'
                ]);
                return;
            }
            
            // Generar JWT token
            $payload = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'full_name' => $user['full_name'],
                'center_id' => $user['center_id'],
                'center_name' => $user['center_name'] ?? null
            ];
            
            $token = $this->jwtHandler->generate($payload);
            
            // Actualitzar last login
            $updateStmt = $this->db->prepare('UPDATE users SET updated_at = NOW() WHERE id = ?');
            $updateStmt->execute([$user['id']]);
            
            // Resposta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Login exitós',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name'],
                    'center_id' => $user['center_id'],
                    'center_name' => $user['center_name'] ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en el login: ' . $e->getMessage(),
                'code' => 'LOGIN_ERROR'
            ]);
        }
    }
    
    /**
     * Registre de nou usuari
     * POST /api/auth/register
     */
    public function register() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar entrada
            if (!isset($input['email']) || !isset($input['password']) || !isset($input['full_name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Camps obligatoris: email, password, full_name',
                    'code' => 'MISSING_FIELDS'
                ]);
                return;
            }
            
            // Validar format email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Format d\'email no vàlid',
                    'code' => 'INVALID_EMAIL'
                ]);
                return;
            }
            
            // Validar contrasenya (mínim 8 caràcters)
            if (strlen($input['password']) < 8) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Contrasenya ha de tenir almenys 8 caràcters',
                    'code' => 'WEAK_PASSWORD'
                ]);
                return;
            }
            
            // Comprovar si email ja existeix
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email ja registrat',
                    'code' => 'EMAIL_EXISTS'
                ]);
                return;
            }
            
            // Hash de contrasenya
            $passwordHash = PasswordHasher::hash($input['password']);
            
            // Crear usuari
            $center_id = $input['center_id'] ?? null;
            $role = $input['role'] ?? 'student';
            
            $sql = "INSERT INTO users (email, full_name, password_hash, role, center_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())";
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute([$input['email'], $input['full_name'], $passwordHash, $role, $center_id])) {
                $userId = $this->db->lastInsertId();
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuari registrat correctament',
                    'user' => [
                        'id' => $userId,
                        'email' => $input['email'],
                        'full_name' => $input['full_name'],
                        'role' => $role,
                        'center_id' => $center_id
                    ]
                ]);
                return;
            }
            
            // Si no es pot crear
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en crear l\'usuari',
                'code' => 'REGISTER_ERROR'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en el registre: ' . $e->getMessage(),
                'code' => 'REGISTER_ERROR'
            ]);
        }
    }
    
    /**
     * Verificar autenticació (útil per frontend)
     * GET /api/auth/verify
     */
    public function verify() {
        try {
            $jwtHandler = new JwtHandler();
            $token = JwtHandler::getTokenFromHeader();
            
            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'No hi ha token'
                ]);
                return;
            }
            
            $payload = $jwtHandler->decode($token);
            
            if (!$payload) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalid o expirat'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Token vàlid',
                'user' => $payload
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error verificant token: ' . $e->getMessage()
            ]);
        }
    }
}
