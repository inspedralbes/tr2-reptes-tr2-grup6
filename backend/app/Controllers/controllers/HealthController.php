<?php
/**
 * HealthController - Comprovació de salut del sistema
 */

class HealthController {
    public function check() {
        try {
            $db = Database::getInstance();
            $db->query('SELECT 1');
            
            http_response_code(200);
            echo json_encode([
                'status' => 'ok',
                'message' => 'Sistema en funcionament',
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => 'Conectat'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error de connexió a BD',
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>
