<?php
namespace Controllers;

class LoggerController {
    
    public function log() {
        $input = file_get_contents('php://input');
        // Log to a file in backend/public if possible, or backend root
        $logFile = __DIR__ . '/../../public/debug_frontend.log';
        if (!is_dir(dirname($logFile))) {
             // fallback to sys temp if public doesn't exist relative to here
             $logFile = sys_get_temp_dir() . '/kairos_debug_frontend.log';
        }
        
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $input . "\n", FILE_APPEND);
        echo json_encode(['status' => 'logged', 'file' => $logFile]);
    }
}
