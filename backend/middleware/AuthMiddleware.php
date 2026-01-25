<?php
namespace Middleware;

require_once __DIR__ . '/../../utils/JwtHandler.php';

class AuthMiddleware {
    /**
     * Verifica el token JWT en les capçaleres
     * @return array Dades de l'usuari si és vàlid
     * Termina l'execució amb 401 si no és vàlid
     */
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token no proporcionat']);
            exit;
        }
        
        $token = $matches[1];
        $jwt = new JwtHandler();
        $payload = $jwt->decode($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token invàlid o expirat']);
            exit;
        }
        
        return $payload;
    }
}
