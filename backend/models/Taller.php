<?php
// backend/models/Taller.php
class Taller {
    private $conn;
    private $table = "tallers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        // Seleccionamos también el nombre y detalles del sector
        $query = "SELECT t.id, t.nom, t.descripcio, t.modalitat, t.durada_minuts, t.capacitat_max, t.imatge_url, t.data, t.sector_id, 
                         s.nom as sector_nom, s.color as sector_color, s.icona as sector_icona
                  FROM " . $this->table . " t
                  LEFT JOIN sectors s ON t.sector_id = s.id
                  WHERE t.estat = 'actiu'
                  ORDER BY t.data ASC, t.nom ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
