<?php
// backend/models/Taller.php
class Taller {
    private $conn;
    private $table = "tallers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        // Seleccionamos también el nombre de la categoría
        $query = "SELECT t.id, t.nom, t.descripcio, t.modalitat, t.durada_minuts, t.imatge_url, c.nom as categoria_nom, c.color as categoria_color 
                  FROM " . $this->table . " t
                  LEFT JOIN categories c ON t.categoria_id = c.id
                  WHERE t.estat = 'actiu'
                  ORDER BY t.nom ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
