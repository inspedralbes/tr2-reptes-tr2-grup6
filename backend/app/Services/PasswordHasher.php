<?php
/**
 * Password Hasher - Gestió de contrasenyes
 * Path: /backend/app/Services/PasswordHasher.php
 * 
 * Hash segur de contrasenyes amb Argon2/Bcrypt
 */

namespace Services;

class PasswordHasher {
    
    /**
     * Generar hash d'una contrasenya
     * 
     * @param string $password Contrasenya en text plà
     * @return string Hash de la contrasenya
     */
    public static function hash($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  // 64MB
            'time_cost' => 4,        // 4 iteracions
            'threads' => 2           // 2 threads
        ]);
    }
    
    /**
     * Verificar contrasenya contra hash
     * 
     * @param string $password Contrasenya en text plà
     * @param string $hash Hash emmagatzemat
     * @return bool True si coincideix
     */
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Comprovar si un hash necessita ser rehasheado
     * 
     * @param string $hash Hash actual
     * @return bool True si necessita actualització
     */
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ]);
    }
}
