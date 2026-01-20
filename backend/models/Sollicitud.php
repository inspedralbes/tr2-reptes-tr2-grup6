<?php
// backend/models/Sollicitud.php
class Sollicitud {
    private $conn;
    private $table = "sollicituds";

    public $centre_id;
    public $taller_id;
    public $data_preferent;
    public $nombre_alumnes;
    public $comentaris;
    public $curs_grup;
    public $necessitats;
    public $preferencia_dates;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (centre_id, taller_id, data_preferent, nombre_alumnes, comentaris, curs_grup, necessitats_especifiques, preferencia_dates, estat) 
                  VALUES (:centre_id, :taller_id, :data_preferent, :nombre_alumnes, :comentaris, :curs, :nec, :pref, 'pendent')";

        $stmt = $this->conn->prepare($query);

        // Limpieza básica
        $this->comentaris = htmlspecialchars(strip_tags($this->comentaris));
        $this->curs_grup = htmlspecialchars(strip_tags($this->curs_grup));
        $this->necessitats = htmlspecialchars(strip_tags($this->necessitats));
        $this->preferencia_dates = htmlspecialchars(strip_tags($this->preferencia_dates));

        // Bind parameters
        $stmt->bindParam(':centre_id', $this->centre_id);
        $stmt->bindParam(':taller_id', $this->taller_id);
        $stmt->bindParam(':data_preferent', $this->data_preferent);
        $stmt->bindParam(':nombre_alumnes', $this->nombre_alumnes);
        $stmt->bindParam(':comentaris', $this->comentaris);
        $stmt->bindParam(':curs', $this->curs_grup);
        $stmt->bindParam(':nec', $this->necessitats);
        $stmt->bindParam(':pref', $this->preferencia_dates);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getByCentre($centre_id) {
        $query = "SELECT s.id, s.estat, s.data_creacio, s.nombre_alumnes, s.comentaris, 
                         s.data_preferent, s.curs_grup, s.necessitats_especifiques, s.preferencia_dates,
                         t.nom as taller_nom, t.descripcio as taller_descripcio, t.imatge_url,
                         c.nom as categoria_nom
                  FROM " . $this->table . " s
                  JOIN tallers t ON s.taller_id = t.id
                  LEFT JOIN categories c ON t.categoria_id = c.id
                  WHERE s.centre_id = :centre_id
                  ORDER BY s.data_creacio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centre_id', $centre_id);
        $stmt->execute();
        return $stmt;
    }
}
?>
