<?php
/**
 * Configuració de connexió a la BD
 */

class Database {
    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;
    
    private $connection;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'kairos_db';
        $this->db   = getenv('DB_NAME') ?: 'kairos_db';
        $this->user = getenv('DB_USER') ?: 'kairos_user';
        $this->pass = getenv('DB_PASSWORD') ?: 'kairos_secret_password_change_this';
        $this->port = getenv('DB_PORT') ?: 3306;
    }

    public function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            return $this->connection;
        } catch (PDOException $e) {
            // Rethrow per a que el Controller ho capturi en JSON
            throw $e;
        }
    }

    public static function getInstance() {
        static $instance = null;
        if (!$instance) {
            $instance = new self();
            $instance->connect();
        }
        return $instance->connection;
    }
}
?>
