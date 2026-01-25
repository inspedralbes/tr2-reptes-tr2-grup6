<?php
/**
 * Controlador de Sol·licituds
 * Path: /backend/app/Controllers/RequestController.php
 * 
 * Gestió de sol·licituds de tallers (CRUD)
 */

namespace Controllers;

class RequestController {
    
    /**
     * Llista de sol·licituds de l'usuari autenticat
     * GET /api/requests
     */
    /**
     * Llista de sol·licituds
     * GET /api/requests
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
            
            // Si és admin, tornar TOTES les sol·licituds
            if ($user_data['role'] === 'admin') {
                $requests = \Models\Request::all(); 
            } else {
                // Si és centre/docent, hem de buscar el centre associat a l'usuari
                $user = \Models\User::findById($user_data['id']);
                if ($user && $user->center_id) {
                     $requests = \Models\Request::findByCenter($user->center_id);
                } else {
                    $requests = []; // Si no té centre, no veu res o veu buit
                }
            }
            
            // Convertir a array
            $data = [];
            foreach ($requests as $request) {
                $data[] = $request->toArray();
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
     * Crear nova sol·licitud
     * POST /api/requests
     */
    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar entrada
            if (!isset($input['workshop_id']) || !isset($input['center_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Camps obligatoris: workshop_id, center_id'
                ]);
                return;
            }
            
            // Obté l'usuari del JWT
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
            
            // Crear sol·licitud
            $priority = $input['priority'] ?? 1;
            $period_id = $input['period_id'] ?? null;
            
            $request = \Models\Request::create(
                $input['center_id'],
                $input['workshop_id'],
                $period_id,
                $input['num_students'] ?? 0,
                $priority
            );
            
            if ($request) {
                // 🎯 Emetre event realtime quan es crea sol·licitud
                $realtime = new \Services\RealtimeService();
                
                // Obtenir informació del taller i centre
                try {
                    $workshop = \Models\Workshop::findById($input['workshop_id']);
                    $center = \Models\Center::findById($input['center_id']);
                    
                    $workshopName = $workshop ? $workshop->name : 'Taller #' . $input['workshop_id'];
                    $centerName = $center ? $center->name : 'Centre #' . $input['center_id'];
                    
                    // Notificar
                    $realtime->notifyRequestCreated(
                        $request->id,
                        $user_data['id'],
                        $workshopName,
                        $centerName
                    );
                } catch (\Exception $e) {
                    error_log('[RealtimeService] Error notifyRequestCreated: ' . $e->getMessage());
                }
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Sol·licitud creada correctament',
                    'data' => $request->toArray()
                ]);
                return;
            }
            
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No es pot crear la sol·licitud (potser ja existeix)'
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
     * Enviament de múltiples sol·licituds (Cistella)
     * POST /api/cart/submit
     */
    public function submitBulk() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['requests']) || !is_array($input['requests'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Format de cistella invàlid']);
                return;
            }

            // Validar token
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $user_data = null;
            if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
                $jwtHandler = new \Utils\JwtHandler();
                $user_data = $jwtHandler->decode($matches[1]);
            }

            if (!$user_data) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Sessió no vàlida']);
                return;
            }

            // Obtenir centre de l'usuari
            $user = \Models\User::findById($user_data['id']);
            if (!$user || !$user->center_id) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'L\'usuari no està vinculat a cap centre']);
                return;
            }

            // Obtenir període actiu
            $db = \Services\Database::getInstance();
            $period = $db->fetchOne("SELECT id FROM periods WHERE start_date <= NOW() AND end_date >= NOW() LIMIT 1");
            if (!$period) {
                // Fallback al període més proper si no n'hi ha cap de vigent avui
                $period = $db->fetchOne("SELECT id FROM periods ORDER BY ABS(DATEDIFF(start_date, NOW())) LIMIT 1");
            }
            
            if (!$period) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No hi ha cap període lectiu definit']);
                return;
            }

            $success_count = 0;
            $errors = [];
            $results = [];

            foreach ($input['requests'] as $req) {
                $workshop_id = $req['workshop_id'];
                $priority = $req['priority'] ?? 1;
                $num_students = $req['num_students'] ?? 0;

                $newRequest = \Models\Request::create(
                    $user->center_id,
                    $workshop_id,
                    $period['id'],
                    $num_students,
                    $priority
                );

                if ($newRequest) {
                    $success_count++;
                    $results[] = $newRequest->toArray();
                } else {
                    $errors[] = "El taller #$workshop_id ja ha estat sol·licitat o és invàlid";
                }
            }

            if ($success_count > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => "S'han creat $success_count sol·licituds",
                    'errors' => $errors,
                    'data' => $results
                ]);
            } else {
                // Si no s'ha creat cap, tornem errors
                $errorMsg = count($errors) > 0 ? implode(". ", $errors) : "No s'han pogut crear les sol·licituds";
                echo json_encode([
                    'success' => false,
                    'message' => $errorMsg, 
                    'errors' => $errors
                ]);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Eliminar sol·licitud
     * DELETE /api/requests/:id
     */
    public function delete($id) {
        try {
            $request = \Models\Request::findById((int)$id);
            
            if (!$request) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Sol·licitud no trobada'
                ]);
                return;
            }
            
            // Verificar que l'usuari autenticat és el propietari
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $user_data = null;
            
            if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
                $token = $matches[1];
                $jwtHandler = new \Services\JWTHandler();
                $user_data = $jwtHandler->validate($token);
            }
            
            if (!$user_data || $user_data['id'] != $request->user_id) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'No tens permís per eliminar aquesta sol·licitud'
                ]);
                return;
            }
            
            // Eliminar
            if ($request->delete()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sol·licitud eliminada correctament'
                ]);
                return;
            }
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error en eliminar la sol·licitud'
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
     * Actualitzar sol·licitud (Admin o User)
     * PUT /api/requests/:id
     */
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $request = \Models\Request::findById((int)$id);
            
            if (!$request) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sol·licitud no trobada']);
                return;
            }

            // Actualització simple de camps permesos
            if (isset($input['status'])) {
                $request->updateStatus($input['status'], $input['rejection_reason'] ?? null);
            }
            
            // Si cal actualitzar altres camps (priority, num_students)
            // $request->update($input); // Si existís un mètode update genèric al model
            
            // Retornar la sol·licitud actualitzada
            $updated = \Models\Request::findById((int)$id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sol·licitud actualitzada',
                'data' => $updated->toArray()
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
