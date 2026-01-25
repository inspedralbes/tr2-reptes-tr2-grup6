<?php
namespace Utils;

class JwtHandler {
    private $secret;

    public function __construct() {
        // En producció, això hauria de venir de variables d'entorn (.env)
        $this->secret = 'kairos_secret_key_change_this_in_production_987654321';
    }

    /**
     * Get bearer token from header
     */
    public static function getTokenFromHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Generate a JWT token
     * @param array $payload Data to encode
     * @param int $expirationSeconds Duration in seconds
     * @return string
     */
    public function generate($payload, $expirationSeconds = 3600) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        // Add expiration
        $payload['exp'] = time() + $expirationSeconds;
        $payload['iat'] = time();
        
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Verify and decode a JWT token
     * @param string $token
     * @return array|false Payload if valid, false otherwise
     */
    public function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header64, $payload64, $signature64) = $parts;
        
        $header = json_decode($this->base64UrlDecode($header64), true);
        $payload = json_decode($this->base64UrlDecode($payload64), true);
        $signatureProvided = $this->base64UrlDecode($signature64);
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // Verify signature
        $signatureExpected = hash_hmac('sha256', $header64 . "." . $payload64, $this->secret, true);
        
        if (!hash_equals($signatureExpected, $signatureProvided)) {
            return false;
        }
        
        return $payload;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
