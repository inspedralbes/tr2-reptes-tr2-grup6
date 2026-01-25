<?php
/**
 * Controlador de Slots de Calendarització
 * Path: /backend/app/Controllers/SlotController.php
 * 
 * Gestió de places de tallers amb blocking i booking
 */

namespace Controllers;

class SlotController {
    
    /**
     * Obtenir slots d'un taller
     * GET /api/slots/:workshop_id
     */
    public function show($workshop_id) {
        try {
            // Obtenir slots del taller
            $slots = \Models\WorkshopSlot::findByWorkshop((int)$workshop_id);
            
            if (empty($slots)) {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'message' => 'No hi ha slots per aquest taller'
                ]);
                return;
            }
            
            // Convertir a array amb detalls
            $data = [];
            foreach ($slots as $slot) {
                $data[] = $slot->getDetails();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Blocar slot per evitar selecció simultània
     * POST /api/slots/lock
     * 
     * Body: { "slot_id": 1 }
     * Retorn: { "lock_token": "abc123", "expires_in": 300 }
     */
    public function lock() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar entrada
            if (!isset($input['slot_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Camp obligatori: slot_id'
                ]);
                return;
            }
            
            // Obtenir usuari autenticat
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $user_data = null;
            
            if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
                $token = $matches[1];
                $jwtHandler = new \Services\JWTHandler();
                $user_data = $jwtHandler->validate($token);
            }
            
            if (!$user_data) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuari no autenticat'
                ]);
                return;
            }
            
            // Obtenir slot
            $slot = \Models\WorkshopSlot::findById((int)$input['slot_id']);
            
            if (!$slot) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Slot no trobat'
                ]);
                return;
            }
            
            // Comprovar si està disponible
            if ($slot->status !== 'available') {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Slot no està disponible',
                    'status' => $slot->status
                ]);
                return;
            }
            
            // Comprovar si és plena
            if ($slot->isFull()) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Slot està ple',
                    'assigned' => $slot->getAssignedCount(),
                    'capacity' => $slot->capacity
                ]);
                return;
            }
            
            // Blocar slot
            $slot->lock();
            
            // Generar token de lock (per validar més tard)
            $lock_token = bin2hex(random_bytes(32));
            $lock_duration = 300; // 5 minuts
            
            // Guardar lock a Redis/Memcached (aquí seria implementació real)
            // Per simplificar, guardem a sessió
            session_start();
            $_SESSION['slot_locks'][$slot->id] = [
                'user_id' => $user_data['id'],
                'token' => $lock_token,
                'expires_at' => time() + $lock_duration,
                'created_at' => time()
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Slot blocat correctament',
                'lock_token' => $lock_token,
                'slot_id' => $slot->id,
                'expires_in' => $lock_duration
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Confirmar booking d'un slot
     * POST /api/slots/book
     * 
     * Body: { "slot_id": 1, "lock_token": "abc123" }
     */
    public function book() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar entrada
            if (!isset($input['slot_id']) || !isset($input['lock_token'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Camps obligatoris: slot_id, lock_token'
                ]);
                return;
            }
            
            // Verificar lock és valid
            $slot_id = (int)$input['slot_id'];
            $lock_token = $input['lock_token'];
            
            session_start();
            $lock_info = $_SESSION['slot_locks'][$slot_id] ?? null;
            
            if (!$lock_info || $lock_info['token'] !== $lock_token) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Lock token no vàlid o expirat'
                ]);
                return;
            }
            
            // Comprovar que no ha expirat
            if (time() > $lock_info['expires_at']) {
                unset($_SESSION['slot_locks'][$slot_id]);
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Lock ha expirat'
                ]);
                return;
            }
            
            // Obtenir slot
            $slot = \Models\WorkshopSlot::findById($slot_id);
            
            if (!$slot) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Slot no trobat'
                ]);
                return;
            }
            
            // Marcar com booked
            if ($slot->book()) {
                // Eliminar lock
                unset($_SESSION['slot_locks'][$slot_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Slot reservat correctament',
                    'slot' => $slot->getDetails()
                ]);
                return;
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en reservar el slot'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Desbloqueja un slot (quan l'usuari cancel·la la selecció)
     * POST /api/slots/unlock
     */
    public function unlock() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['slot_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Camp obligatori: slot_id'
                ]);
                return;
            }
            
            $slot = \Models\WorkshopSlot::findById((int)$input['slot_id']);
            
            if ($slot && $slot->unlock()) {
                session_start();
                unset($_SESSION['slot_locks'][(int)$input['slot_id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Slot desbloquejat'
                ]);
                return;
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en desbloquejat el slot'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}