<?php
/**
 * Controlador de Tallers
 * Path: /backend/app/Controllers/WorkshopController.php
 * 
 * Gestió de tallers: llistar, detalls, filtres per modalitat/categoria
 */

namespace Controllers;

class WorkshopController {
    
    /**
     * Obtenir llista de tallers
     * GET /api/workshops
     * Query params: modality, category
     */
    public function list() {
        try {
            // Obté paràmetres de query
            $modality = $_GET['modality'] ?? null;
            $category = $_GET['category'] ?? null;
            
            // Obté tallers segons filtres
            if ($modality) {
                $workshops = \Models\Workshop::findByModality($modality);
            } elseif ($category) {
                $workshops = \Models\Workshop::findByCategory($category);
            } else {
                $workshops = \Models\Workshop::getAll();
            }
            
            // Convertir a array
            $data = [];
            foreach ($workshops as $workshop) {
                $data[] = $workshop->toArray();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($data),
                'filters' => [
                    'modality' => $modality,
                    'category' => $category
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error obtenint tallers: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtenir detalls d'un taller
     * GET /api/workshops/:id
     */
    public function show($id) {
        try {
            $workshop = \Models\Workshop::findById((int)$id);
            
            if (!$workshop) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Taller no trobat'
                ]);
                return;
            }
            
            // Obtenir places disponibles
            $available_slots = $workshop->getAvailableSlots();
            
            $data = $workshop->toArray();
            $data['available_slots'] = $available_slots;
            
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
     * Crear nou taller
     * POST /api/workshops
     */
    public function create() {
        try {
            // Llegir JSON del body
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar camps requerits
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'El nom del taller és obligatori'
                ]);
                return;
            }

            // Crear taller
            $db = \Services\Database::getInstance();
            
            $sql = "INSERT INTO workshops (
                name, description, modality, capacity, ambit, 
                duration_hours, duration_days, hours_per_day,
                provider_name, provider_contact, modality_color, is_active,
                provider_name, provider_contact, modality_color, is_active,
                provider_name, provider_contact, modality_color, is_active,
                instructor, location, 
                theme, course, allowed_days, time_slots, images,
                start_date, end_date, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, NOW()
            )";

            $lastId = $db->insert($sql, [
                $input['name'] ?? null,
                $input['description'] ?? null,
                $input['modality'] ?? 'A',
                $input['capacity'] ?? 16,
                $input['ambit'] ?? null,
                $input['duration_hours'] ?? 20,
                $input['duration_days'] ?? 10,
                $input['hours_per_day'] ?? 2,
                $input['provider_name'] ?? null,
                $input['provider_contact'] ?? null,
                $input['modality_color'] ?? null,
                1,
                $input['instructor'] ?? null,
                $input['location'] ?? null,
                $input['theme'] ?? null,
                $input['course'] ?? null,
                isset($input['allowed_days']) ? json_encode($input['allowed_days']) : null,
                isset($input['time_slots']) ? json_encode($input['time_slots']) : null,
                isset($input['images']) ? json_encode($input['images']) : null,
                $input['start_date'] ?? null,
                $input['end_date'] ?? null
            ]);

            if ($lastId) {
                $workshop = \Models\Workshop::findById($lastId);

                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Taller creat correctament',
                    'data' => $workshop ? $workshop->toArray() : ['id' => $lastId, 'name' => $input['name']]
                ]);
            } else {
                throw new \Exception('Error inserint a la base de dades');
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creant taller: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualitzar taller
     * PUT /api/workshops/:id
     */
    public function update($id) {
        try {
            $id = (int)$id;
            
            // Verificar que el taller existeix
            $workshop = \Models\Workshop::findById($id);
            if (!$workshop) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Taller no trobat'
                ]);
                return;
            }

            // Llegir JSON del body
            $input = json_decode(file_get_contents('php://input'), true);

            // Actualitzar a la base de dades
            $db = \Services\Database::getInstance();
            
            $updates = [];
            $params = [];

            // Camps que es poden actualitzar
            $fields = ['name', 'description', 'modality', 'capacity', 'ambit', 
                      'duration_hours', 'duration_days', 'hours_per_day',
                      'provider_name', 'provider_contact', 'modality_color',
                      'provider_name', 'provider_contact', 'modality_color',
                      'instructor', 'location',
                      'theme', 'course', 'start_date', 'end_date'];

            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }

            // Camps especials que son JSON
            if (isset($input['allowed_days'])) {
                $updates[] = "allowed_days = ?";
                $params[] = json_encode($input['allowed_days']);
            }
            if (isset($input['time_slots'])) {
                $updates[] = "time_slots = ?";
                $params[] = json_encode($input['time_slots']);
            }
            if (isset($input['images'])) {
                $updates[] = "images = ?";
                $params[] = json_encode($input['images']);
            }

            // Sempre actualitzar updated_at
            $updates[] = "updated_at = NOW()";

            if (empty($updates)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cap canvi registrat',
                    'data' => $workshop->toArray()
                ]);
                return;
            }

            $params[] = $id;
            $sql = "UPDATE workshops SET " . implode(", ", $updates) . " WHERE id = ?";

            $result = $db->query($sql, $params);

            if ($result) {
                $updatedWorkshop = \Models\Workshop::findById($id);
                echo json_encode([
                    'success' => true,
                    'message' => 'Taller actualitzat correctament',
                    'data' => $updatedWorkshop ? $updatedWorkshop->toArray() : ['id' => $id]
                ]);
            } else {
                throw new \Exception('Error actualitzant la base de dades');
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error actualitzant taller: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar taller
     * DELETE /api/workshops/:id
     */
    public function delete($id) {
        try {
            $id = (int)$id;
            
            // Verificar que el taller existeix
            $workshop = \Models\Workshop::findById($id);
            if (!$workshop) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Taller no trobat'
                ]);
                return;
            }

            // Eliminar de la base de dades
            $db = \Services\Database::getInstance();
            $sql = "DELETE FROM workshops WHERE id = ?";
            
            $result = $db->query($sql, [$id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Taller eliminat correctament'
                ]);
            } else {
                throw new \Exception('Error eliminant de la base de dades');
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error eliminant taller: ' . $e->getMessage()
            ]);
        }
    }
}
