<?php
/**
 * JWT Handler - Gestió de JSON Web Tokens
 * Path: /backend/app/Services/JWTHandler.php
 * 
 * Generar, validar i decodificar JWT tokens
 */

namespace Services;

class JWTHandler {
    
    private $secret;
    private $algorithm = 'HS256';
    private $expiration;
    
    /**
     * Constructor
     */
    public function __construct($secret = null, $expiration = null) {
        $this->secret = $secret ?? \Config::JWT_SECRET;
        $this->expiration = $expiration ?? \Config::JWT_EXPIRATION;
    }
    
    /**
     * Generar JWT token
     * 
     * @param array $payload Dades del token
     * @return string Token JWT signat
     */
    public function generate($payload) {
        
        // Header
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ];
        
        // Payload amb timestamps
        $payload['iat'] = time();  // Issued at
        $payload['exp'] = time() + $this->expiration;  // Expiration
        
        // Codificar header i payload
        $headerEncoded = $this->base64Encode(json_encode($header));
        $payloadEncoded = $this->base64Encode(json_encode($payload));
        
        // Crear signature
        $signInput = $headerEncoded . '.' . $payloadEncoded;
        $signature = $this->base64Encode(
            hash_hmac('sha256', $signInput, $this->secret, true)
        );
        
        // Token complet
        $token = $signInput . '.' . $signature;
        
        return $token;
    }
    
    /**
     * Validar i decodificar JWT token
     * 
     * @param string $token Token JWT
     * @return array|false Array amb payload si és vàlid, false si no
     */
    public function validate($token) {
        
        if (!$token) {
            return false;
        }
        
        // Dividir token en parts
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        try {
            // Decodificar
            $header = json_decode($this->base64Decode($headerEncoded), true);
            $payload = json_decode($this->base64Decode($payloadEncoded), true);
            $signature = $this->base64Decode($signatureEncoded);
            
            // Verificar header
            if ($header['alg'] !== $this->algorithm) {
                return false;
            }
            
            // Verificar signature
            $signInput = $headerEncoded . '.' . $payloadEncoded;
            $expectedSignature = hash_hmac('sha256', $signInput, $this->secret, true);
            
            if (!hash_equals($signature, $expectedSignature)) {
                return false;
            }
            
            // Verificar expiració
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            // Tot bé, retornar payload
            return $payload;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Extraure token del header Authorization
     * 
     * @return string|null Token si existeix
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }
        
        $authHeader = $headers['Authorization'];
        
        // Format esperado: "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }
        
        return $matches[1];
    }
    
    /**
     * Base64 URL encode
     */
    private function base64Encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64Decode($data) {
        $b64 = strtr($data, '-_', '+/');
        return base64_decode($b64 . str_repeat('=', 4 - strlen($b64) % 4));
    }
}
