<?php
/**
 * Controlador de Sol·licituds d'Alta de Centre (Públic)
 */

namespace Controllers;

use Models\CenterRequest;

class CenterRequestController {
    
    /**
     * Llistar sol·licituds (Admin)
     * GET /api/center-requests
     */
    public function list() {
        try {
            $requests = CenterRequest::all();
            
            $data = [];
            foreach ($requests as $request) {
                $data[] = $request->toArray();
                
                // Add more details for admin list
                $data[count($data)-1]['contact_name'] = $request->contact_name;
                $data[count($data)-1]['contact_email'] = $request->contact_email;
                $data[count($data)-1]['contact_phone'] = $request->contact_phone;
                $data[count($data)-1]['created_at'] = $request->created_at;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data
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
     * Crear nova sol·licitud d'alta de centre
     * POST /api/center-requests
     */
    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validació bàsica
            $required = ['center_name', 'center_code', 'address', 'city', 'postal_code', 'contact_name', 'contact_email', 'contact_phone'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "El camp $field és obligatori"
                    ]);
                    return;
                }
            }
            
            // Crear sol·licitud
            $request = CenterRequest::create($input);
            
            if ($request) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Sol·licitud enviada correctament',
                    'data' => $request->toArray()
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error en desar la sol·licitud'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Aprovar sol·licitud (Crea Centre + Usuari)
     * PUT /api/center-requests/:id/approve
     */
    public function approve($id) {
        // Here we should verify ADMIN role with Middleware, assuming route protection handles it
        try {
            $db = \Services\Database::getInstance();
            $request = CenterRequest::findById($id);
            
            if (!$request) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sol·licitud no trobada']);
                return;
            }
            
            if ($request->status !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La sol·licitud ja no està pendent']);
                return;
            }
            
            // 1. Create Center
            $centerData = [
                'name' => $request->center_name,
                'code' => $request->center_code,
                'city' => $request->city,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'phone' => $request->contact_phone,
                'contact_email' => $request->contact_email
            ];
            
            // Generate Complex Temp Password (kairos-X...)
            $randomPart = $this->generateComplexString(10);
            $tempPassword = 'kairos-' . $randomPart; 
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            // Begin Transaction ideally
            $db->getPDO()->beginTransaction();
            
            try {
                // Create Center
                $center = \Models\Center::create($centerData);
                if (!$center) throw new \Exception("Error creant el centre. Potser el codi ja existeix.");
                
                // Create User (Coordinator)
                $user = \Models\User::create(
                    $request->contact_email,
                    $request->contact_name,
                    $passwordHash,
                    'center_coord',
                    $center->id
                );
                
                if (!$user) throw new \Exception("Error creant l'usuari. L'email potser ja existeix.");
                
                // Link Coordinator to Center
                $center->setCoordinator($user->id);
                
                // Update Request Status
                $request->status = 'approved';
                $request->updateStatus('approved'); 
                
                $db->getPDO()->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sol·licitud aprovada correctament',
                    'password' => $tempPassword // Send back to admin to notify user
                ]);
                
            } catch (\Exception $ex) {
                $db->getPDO()->rollBack();
                throw $ex;
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Rebutjar sol·licitud
     * PUT /api/center-requests/:id/reject
     */
    public function reject($id) {
        try {
            $request = CenterRequest::findById($id);
            
            if (!$request) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sol·licitud no trobada']);
                return;
            }
            
            $request->updateStatus('rejected');
            
            echo json_encode([
                'success' => true,
                'message' => 'Sol·licitud rebutjada'
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
     * Generar string complexa per contrasenyes
     */
    private function generateComplexString($length = 10) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $all = $uppercase . $lowercase . $numbers . $symbols;
        
        $str = '';
        
        // Ensure at least one of each type
        $str .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $str .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $str .= $numbers[random_int(0, strlen($numbers) - 1)];
        $str .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest
        for ($i = 0; $i < $length - 4; $i++) {
            $str .= $all[random_int(0, strlen($all) - 1)];
        }
        
        return str_shuffle($str);
    }
}
