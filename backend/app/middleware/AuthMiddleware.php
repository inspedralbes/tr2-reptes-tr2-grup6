<?php
/**
 * Middleware d'Autenticació
 * Path: /backend/app/Middleware/AuthMiddleware.php
 * 
 * Verificar JWT token en peticions protegides
 */

namespace Middleware;

use Utils\JwtHandler;

class AuthMiddleware {
    
    /**
     * Verificar autenticació
     * 
     * @return array|false Array amb dades d'usuari si autenticat
     */
    public static function authenticate() {
        
        // Obtenir token del header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        $token = null;
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token d\'autenticació requerit',
                'code' => 'NO_TOKEN'
            ]);
            exit;
        }
        
        // Validar token
        $jwtHandler = new \Utils\JwtHandler();
        $payload = $jwtHandler->decode($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token invalid o expirat',
                'code' => 'INVALID_TOKEN'
            ]);
            exit;
        }
        
        // Retornar dades del usuari
        return $payload;
    }
    
    /**
     * Verificar que l'usuari té un rol específic
     * 
     * @param array $user Dades del usuari (del JWT)
     * @param string|array $requiredRoles Rol(s) requerits
     * @return bool True si té el rol
     */
    public static function checkRole($user, $requiredRoles) {
        
        if (is_string($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }
        
        return isset($user['role']) && in_array($user['role'], $requiredRoles);
    }
    
    /**
     * Verificar permisos i denegar accés si necessari
     * 
     * @param array $user Dades del usuari
     * @param string|array $requiredRoles Rol(s) requerits
     */
    public static function requireRole($user, $requiredRoles) {
        
        if (!self::checkRole($user, $requiredRoles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Permissos insuficients per accedir aquest recurs',
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'user_role' => $user['role'] ?? 'unknown'
            ]);
            exit;
        }
    }
    
    /**
     * CORS Middleware
     * Establir headers CORS
     */
    public static function setupCORS() {
        
        // Allow any origin for development
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        
        header('Access-Control-Allow-Methods: ' . implode(', ', \Config::CORS_METHODS));
        header('Access-Control-Allow-Headers: ' . implode(', ', \Config::CORS_HEADERS));
        header('Access-Control-Max-Age: 3600');
        
        // Manejar preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
