<?php
/**
 * Controlador d'Assignacions
 * Path: /backend/app/Controllers/AllocationController.php
 * 
 * Gestió d'assignacions finals de tallers als usuaris
 */

namespace Controllers;

class AllocationController {
    
    /**
     * Llista d'assignacions de l'usuari autenticat
     * GET /api/allocations
     */
    public function list() {
        try {
            // Validar token
            $token = \Utils\JwtHandler::getTokenFromHeader();
            $jwt = new \Utils\JwtHandler();
            $user_data = $jwt->decode($token);
            
            if (!$user_data) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuari no autenticat'
                ]);
                return;
            }
            
            // Si és admin, tornar TOTES les assignacions
            if ($user_data['role'] === 'admin') {
                $allocations = \Models\Allocation::all();
            } else {
                // Si és centre (coord), mostrar assignacions del centre
                if ($user_data['role'] === 'center_coord') {
                    $user = \Models\User::findById($user_data['id']);
                    if ($user && $user->center_id) {
                         $allocations = \Models\Allocation::findByCenter($user->center_id);
                    } else {
                        $allocations = [];
                    }
                } 
                // Si és docent (teacher), permetre veure assignacions del SEU centre (pool) o les seves
                else if ($user_data['role'] === 'teacher') {
                     $user = \Models\User::findById($user_data['id']);
                     $requestedCenterId = isset($_GET['assigned_center_id']) ? $_GET['assigned_center_id'] : null;
                     
                     // Fallback: Si el usuario no té center_id definit a la taula users, buscar a teachers
                     $userCenterId = $user ? $user->center_id : null;
                     if (!$userCenterId && $user) {
                         $db = \Services\Database::getInstance();
                         $teacherRow = $db->fetchOne("SELECT center_id FROM teachers WHERE email = ?", [$user->email]);
                         if ($teacherRow) $userCenterId = $teacherRow['center_id'];
                     }

                     if ($requestedCenterId && $userCenterId == $requestedCenterId) {
                         // Veure assignacions del centre (pool)
                         $allocations = \Models\Allocation::findByCenter($userCenterId);
                     } else {
                         // Per defecte: només les meves
                         $allocations = \Models\Allocation::findByTeacherId($user_data['id']);
                     }
                }
                else {
                    $allocations = [];
                }
            }
            
            // Convertir a array
            $data = [];
            foreach ($allocations as $allocation) {
                $data[] = $allocation->toArray();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            
        } catch (\Exception $e) {
            file_put_contents(__DIR__.'/../../error_500.log', date('Y-m-d H:i:s') . " List Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get single allocation by ID
     * GET /api/allocations/:id
     */
    public function getById($id) {
        try {
            $allocation = \Models\Allocation::findById($id);
            
            if (!$allocation) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Allocation not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $allocation->toArray()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Actualitzar assignació
     * PUT /api/allocations/:id
     */
    public function update($id) {
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            // Debug Log
            file_put_contents(__DIR__.'/../../debug_update.log', date('Y-m-d H:i:s') . " Update ID $id Input: " . $rawInput . "\n", FILE_APPEND);
            
            // Obtenir assignació
            $allocation = \Models\Allocation::findById((int)$id);
            
            if (!$allocation) {
                file_put_contents(__DIR__.'/../../debug_update.log', "Update ID $id: Allocation not found\n", FILE_APPEND);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Assignació no trobada']);
                return;
            }
            
            // Actualitzar camps
            $updated_fields = [];
            
            if (isset($input['assigned_teacher_id'])) {
                // Ensure teacher exists
                $newTeacherId = (int)$input['assigned_teacher_id'];
                file_put_contents(__DIR__.'/../../debug_update.log', "Update ID $id: Assigning teacher $newTeacherId\n", FILE_APPEND);
                // Optional: Validate if teacher belongs to center
                
                // Update implementation plan: Logic to update teacher ID is missing in Model
                // We need to extend Allocation model update method or run raw query
                // For now, raw query update:
                $db = \Services\Database::getInstance();
                $db->query("UPDATE allocations SET assigned_teacher_id = ? WHERE id = ?", [$newTeacherId, $id]);
                
                $updated_fields[] = 'assigned_teacher_id';
                $allocation->assigned_teacher_id = $newTeacherId; // Reflect in object
                file_put_contents(__DIR__.'/../../debug_update.log', "Update ID $id: DB Update Success\n", FILE_APPEND);
            }

            if (isset($input['status'])) {
                $allocation->setStatus($input['status']);
                $updated_fields[] = 'status';
                
                // 🎯 Emetre event realtime quan l'assignació canvia a 'assigned'
                if ($input['status'] === 'assigned') {
                    $realtime = new \Services\RealtimeService();
                    
                    try {
                        // Obtenir informació de la assignació
                        $workshop = \Models\Workshop::findById($allocation->workshop_id);
                        $slot = \Models\Slot::findById($allocation->slot_id);
                        
                        $workshopName = $workshop ? $workshop->name : 'Taller #' . $allocation->workshop_id;
                        $slotInfo = $slot ? [
                            'date' => $slot->date,
                            'time' => $slot->start_time . ' - ' . $slot->end_time,
                            'center' => $slot->center
                        ] : ['date' => '', 'time' => '', 'center' => ''];
                        
                        // Notificar
                        $realtime->notifyAssignmentExecuted(
                            $allocation->id,
                            $allocation->user_id,
                            $workshopName,
                            $slotInfo
                        );
                    } catch (\Exception $e) {
                        // Log error però continua (realtime és opcional)
                        error_log('[RealtimeService] Error notifyAssignmentExecuted: ' . $e->getMessage());
                    }
                }
            }
            
            if (isset($input['slot_id'])) {
                $allocation->update(null, $input['slot_id']);
                $updated_fields[] = 'slot_id';
            }
            
            if (isset($input['score'])) {
                $allocation->update(null, null, $input['score']);
                $updated_fields[] = 'score';
            }
            
            if (empty($updated_fields)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No hi ha camps per actualitzar'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Assignació actualitzada correctament',
                'updated_fields' => $updated_fields,
                'data' => $allocation->toArray()
            ]);
            
        } catch (\Throwable $e) {
             file_put_contents(__DIR__.'/../../error_500.log', date('Y-m-d H:i:s') . " Update CRITICAL Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
