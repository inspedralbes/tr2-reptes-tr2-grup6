<?php
namespace Controllers;

class AllocationControllerNew {
    
    public function list() {
        try {
            // Validar token
            $token = \Utils\JwtHandler::getTokenFromHeader();
            $jwt = new \Utils\JwtHandler();
            $user_data = $jwt->decode($token);
            
            if (!$user_data) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Usuari no autenticat']);
                return;
            }
            
            $allocations = [];

            if ($user_data['role'] === 'admin') {
                $allocations = \Models\Allocation::all();
            } 
            else if ($user_data['role'] === 'center_coord') {
                $user = \Models\User::findById($user_data['id']);
                if ($user && $user->center_id) {
                     $allocations = \Models\Allocation::findByCenter($user->center_id);
                }
            } 
            else if ($user_data['role'] === 'teacher') {
                 $user = \Models\User::findById($user_data['id']);
                 $requestedCenterId = isset($_GET['assigned_center_id']) ? $_GET['assigned_center_id'] : null;
                 
                 // Fallback Logic
                 $userCenterId = $user ? $user->center_id : null;
                 
                 if (!$userCenterId && $user) {
                     $db = \Services\Database::getInstance();
                     $teacherRow = $db->fetchOne("SELECT center_id FROM teachers WHERE email = ?", [$user->email]);
                     if ($teacherRow) {
                         $userCenterId = $teacherRow['center_id'];
                     }
                 }

                 if ($requestedCenterId && $userCenterId == $requestedCenterId) {
                     $allocations = \Models\Allocation::findByCenter($userCenterId);
                 } else {
                     $allocations = \Models\Allocation::findByTeacherId($user_data['id']);
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
                'total' => count($data),
                'debug_source' => 'AllocationControllerNew'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    // Copy update method as well (simplified for file size)
    public function update($id) {
         try {
            $input = json_decode(file_get_contents('php://input'), true);
            $allocation = \Models\Allocation::findById((int)$id);
            if (!$allocation) { http_response_code(404); echo json_encode(['success'=>false]); return; }
            
            if (isset($input['status'])) $allocation->setStatus($input['status']);
            if (isset($input['slot_id'])) $allocation->update(null, $input['slot_id']);
            if (isset($input['score'])) $allocation->update(null, null, $input['score']);
            
            echo json_encode(['success'=>true, 'data'=>$allocation->toArray()]);
         } catch (\Exception $e) {
             http_response_code(500); echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
         }
    }
}
