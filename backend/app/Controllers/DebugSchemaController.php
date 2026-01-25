<?php
namespace Controllers;
use Services\Database;

class DebugSchemaController {
    public function check() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("DESCRIBE workshop_slots");
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo json_encode($columns);
        } catch (\Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    }
}
