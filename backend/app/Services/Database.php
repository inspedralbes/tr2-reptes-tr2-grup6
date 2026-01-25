<?php
/**
 * Database Connection Handler
 * Path: /backend/app/Services/Database.php
 * 
 * Gestió de connexió a BD
 */

namespace Services;

use PDO;
use PDOException;

class Database {
    
    private static $instance = null;
    private $pdo;
    
    /**
     * Constructor privat (Singleton)
     */
    private function __construct() {
        try {
            $dsn = \Config::getDbConnectionString();
            
            $this->pdo = new PDO(
                $dsn,
                \Config::DB_USER,
                \Config::DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
        } catch (PDOException $e) {
            die('Database connection error: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir instància de connexió (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtenir PDO connection
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Executar query amb paràmetres
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir una fila
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtenir totes les files
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Inserir i obtenir ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Preparar statement
     */
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    /**
     * Obtenir últim ID inserit
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Actualitzar
     */
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}
