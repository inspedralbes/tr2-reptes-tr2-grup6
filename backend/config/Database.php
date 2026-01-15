<?php
// backend/config/Database.php

class Database {
    // Configuración para servidor remoto de producción
    private $host_remote = "daw.inspedralbes.cat";
    private $db_name_remote = "a24bryruzgon_tr2";
    private $username_remote = "a24bryruzgon_tr2";
    private $password_remote = "V28UIDV0n&[zdyda";
    
    // Configuración para Docker local (desarrollo)
    private $host = "db";
    private $db_name = "kairos_db";
    private $username = "root";
    private $password = "rootpass";
    
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            // Modo de errores estricto para desarrollo
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de connexió: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
