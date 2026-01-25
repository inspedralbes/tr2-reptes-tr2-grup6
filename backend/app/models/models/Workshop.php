<?php
require_once __DIR__ . '/../config/Database.php';

class Workshop {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Get all workshops with JSON fields decoded
     * @return array
     */
    public function getAll() {
        $sql = 'SELECT * FROM workshops ORDER BY id ASC';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process JSON fields automatically
        foreach ($rows as &$row) {
            if (!empty($row['allowed_days'])) $row['allowed_days'] = json_decode($row['allowed_days']);
            if (!empty($row['time_slots'])) $row['time_slots'] = json_decode($row['time_slots']);
            if (!empty($row['images'])) $row['images'] = json_decode($row['images']);
        }

        return $rows;
    }
}
