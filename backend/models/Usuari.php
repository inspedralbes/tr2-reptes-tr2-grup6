<?php
// backend/models/Usuari.php
require_once '../config/Database.php';

class Usuari {
    private $conn;
    private $table_name = "usuaris";

    // Propiedades del usuario
    public $id;
    public $nom_complet;
    public $email;
    public $password; // Password sin encriptar (entrada)
    public $rol_id;
    public $codi_centre;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Función para comprobar Login
    public function login() {
        // Query para buscar por email
        $query = "SELECT id, nom_complet, password_hash, rol_id, codi_centre 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Vincular parámetros
        $stmt->bindParam(':email', $this->email);

        $stmt->execute();

        // Si encontramos el usuario
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar la contraseña (hash)
            if (password_verify($this->password, $row['password_hash'])) {
                // Asignar valores al objeto
                $this->id = $row['id'];
                $this->nom_complet = $row['nom_complet'];
                $this->rol_id = $row['rol_id'];
                $this->codi_centre = $row['codi_centre'];
                return true;
            }
        }
        return false;
    }
}
?>
