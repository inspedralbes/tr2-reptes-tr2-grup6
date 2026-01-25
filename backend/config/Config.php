<?php
/**
 * Configuració Principal de KAIROS
 * Path: /backend/config/Config.php
 */

class Config {
    
    // ===== ENTORN =====
    public const APP_ENV = 'production'; // development, staging, production
    public const APP_DEBUG = true;
    public const APP_NAME = 'KAIROS';
    public const APP_VERSION = '1.0.0';
    
    // ===== BASE DE DADES =====
    // ===== BASE DE DADES =====
    public const DB_HOST = 'db'; 
    public const DB_PORT = 3306;
    public const DB_NAME = 'kairos_db';
    public const DB_USER = 'kairos_user';
    public const DB_PASSWORD = 'kairos_secret_password_change_this';
    public const DB_CHARSET = 'utf8mb4';
    
    // ===== AUTENTICACIÓ =====
    public const JWT_SECRET = 'your_jwt_secret_key_change_this';
    public const JWT_ALGORITHM = 'HS256';
    public const JWT_EXPIRATION = 86400; // 24 hores en segons
    
    // ===== SERVIDOR =====
    public const APP_URL = 'https://kairos.daw.inspedralbes.cat';
    public const API_URL = 'https://kairos.daw.inspedralbes.cat/api';
    public const FRONTEND_URL = 'https://kairos.daw.inspedralbes.cat';
    public const REALTIME_URL = 'wss://kairos.daw.inspedralbes.cat';
    
    // ===== DIRECTÒRIES =====
    public const BASE_PATH = __DIR__ . '/..';
    public const LOG_PATH = __DIR__ . '/../logs';
    public const UPLOAD_PATH = __DIR__ . '/../uploads';
    public const CACHE_PATH = __DIR__ . '/../cache';
    
    // ===== CORS =====
    public const CORS_ORIGINS = [
        'http://localhost',
        'http://localhost:5173',
        'http://localhost:3000',
        'https://kairos.daw.inspedralbes.cat',
        'https://65.109.167.111'
    ];
    public const CORS_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
    public const CORS_HEADERS = ['Content-Type', 'Authorization', 'X-Requested-With'];
    
    // ===== EMAIL =====
    public const MAIL_HOST = 'smtp.example.com';
    public const MAIL_PORT = 587;
    public const MAIL_USERNAME = 'noreply@kairos.cat';
    public const MAIL_PASSWORD = 'email_password';
    public const MAIL_FROM_NAME = 'KAIROS - Programa ENGINY';
    public const MAIL_FROM_EMAIL = 'noreply@kairos.cat';
    
    // ===== ALGORITME =====
    public const ALGORITHM_MAX_RETRIES = 3;
    public const ALGORITHM_HISTORICAL_WEIGHT = 0.3; // 30% de pes al històric
    public const ALGORITHM_CAPACITY_WEIGHT = 0.4;   // 40% de pes a capacitat
    public const ALGORITHM_PRIORITY_WEIGHT = 0.3;   // 30% de pes a prioritat
    
    // ===== LLIM DE SLOTS PER TALLER =====
    public const MAX_SLOTS_PER_WORKSHOP = 50;
    public const SLOT_LOCK_TIMEOUT = 300; // 5 minuts en segons
    
    /**
     * Obtenir la cadena de connexió a BD
     */
    public static function getDbConnectionString(): string {
        return sprintf(
            'mysql:host=%s:%d;dbname=%s;charset=%s',
            self::DB_HOST,
            self::DB_PORT,
            self::DB_NAME,
            self::DB_CHARSET
        );
    }
    
    /**
     * Obtenir el path complet per a un fitxer de log
     */
    public static function getLogPath(string $filename = 'app.log'): string {
        return self::LOG_PATH . '/' . $filename;
    }
}
