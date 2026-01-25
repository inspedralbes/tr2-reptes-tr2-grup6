<?php
require_once __DIR__ . '/../config/Database.php';

class AllocationService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Esegueix l'algoritme d'assignació intel·ligent
     */
    public function executeAlgorithm($strategy = 'weighted', $periodId = null) {
        $this->db->beginTransaction();
        try {
            // 1. Obtenir sol·licituds pendents (prioritzades)
            $requests = $this->getPendingRequests($periodId);
            
            // 2. Obtenir docents disponibles
            $teachers = $this->getTeachers();

            $results = [
                'processed' => 0,
                'assigned' => 0,
                'unassigned' => 0,
                'details' => []
            ];

            foreach ($requests as $request) {
                $results['processed']++;
                
                // 3. Calcular puntuació per a cada docent
                $candidates = [];
                foreach ($teachers as $teacher) {
                    $score = $this->calculateScore($request, $teacher);
                    if ($score > 0) {
                        $candidates[] = [
                            'teacher' => $teacher,
                            'score' => $score
                        ];
                    }
                }

                // Ordenar candidats per puntuació descendents
                usort($candidates, function($a, $b) {
                    return $b['score'] - $a['score'];
                });

                $assigned = false;

                // 4. Intentar assignar al millor candidat
                foreach ($candidates as $candidate) {
                    $teacher = $candidate['teacher'];
                    
                    // Buscar slot disponible per a aquest docent
                    $slot = $this->findAvailableSlot($teacher['id'], $request['duration_hours']);
                    
                    if ($slot) {
                        $this->createAllocation($request, $teacher['id'], $slot, $candidate['score']);
                        $assigned = true;
                        $results['assigned']++;
                        $results['details'][] = "Assignat {$request['workshop_name']} a {$teacher['full_name']} (Score: {$candidate['score']})";
                        break; // Saltem al següent request
                    }
                }

                if (!$assigned) {
                    $results['unassigned']++;
                    $results['details'][] = "NO assignat {$request['workshop_name']} (Sense disponibilitat/candidats)";
                }
            }

            $this->db->commit();
            return $results;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getPendingRequests($periodId) {
        $sql = 'SELECT r.*, w.name as workshop_name, w.ambit, w.duration_hours 
                FROM requests r 
                JOIN workshops w ON r.workshop_id = w.id 
                WHERE r.status = "pending"';
        if ($periodId) {
            $sql .= ' AND r.period_id = ' . intval($periodId);
        }
        // Prioritzem per nivell de prioritat i antiguitat
        $sql .= ' ORDER BY r.priority_level DESC, r.created_at ASC';
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTeachers() {
        return $this->db->query("SELECT * FROM users WHERE role = 'teacher' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calculateScore($request, $teacher) {
        $score = 10; // Base score

        // Punts per especialitat (Ambit)
        $teacherSpecialty = strtolower($teacher['specialty'] ?? '');
        $requestAmbit = strtolower($request['ambit'] ?? '');
        
        if (!empty($teacherSpecialty) && strpos($teacherSpecialty, $requestAmbit) !== false) {
            $score += 50; // Gran match
        }

        // Punts per càrrega de treball (balanceig)
        // TODO: Restar punts si ja té moltes hores assignades

        return $score;
    }

    private function findAvailableSlot($teacherId, $durationHours) {
        // Simplificació: Busquem primer forat proper
        // En producció, això miraria workshop_slots
        $startDate = new DateTime('tomorrow');
        $endDate = new DateTime('+30 days');
        
        // Obtenir slots ocupats
        $busySlots = $this->getBusySlots($teacherId, $startDate, $endDate);

        $current = clone $startDate;
        while ($current < $endDate) {
            // Només laborables
            if ($current->format('N') <= 5) {
                // Provar slot matí (09:00)
                $slotStart = clone $current;
                $slotStart->setTime(9, 0, 0);
                $slotEnd = clone $slotStart;
                $slotEnd->modify("+$durationHours hours");

                if (!$this->isOverlapping($slotStart, $slotEnd, $busySlots)) {
                    return ['start' => $slotStart->format('Y-m-d H:i:s'), 'end' => $slotEnd->format('Y-m-d H:i:s')];
                }
            }
            $current->modify('+1 day');
        }
        return null;
    }

    private function getBusySlots($teacherId, $start, $end) {
        $stmt = $this->db->prepare('
            SELECT start_time, end_time FROM workshop_slots ws
            JOIN allocations a ON ws.allocation_id = a.id
            WHERE a.assigned_teacher_id = ? 
            AND ws.start_time BETWEEN ? AND ?
        ');
        $stmt->execute([$teacherId, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function isOverlapping($start, $end, $busySlots) {
        $s = $start->getTimestamp();
        $e = $end->getTimestamp();
        foreach ($busySlots as $busy) {
            $bs = strtotime($busy['start_time']);
            $be = strtotime($busy['end_time']);
            if ($s < $be && $e > $bs) return true;
        }
        return false;
    }

    private function createAllocation($request, $teacherId, $slot, $score) {
        // Crear Allocation
        $stmt = $this->db->prepare('INSERT INTO allocations (request_id, assigned_center_id, assigned_workshop_id, assigned_teacher_id, status, algorithm_priority_score, created_at) VALUES (?, ?, ?, ?, "confirmed", ?, NOW())');
        $stmt->execute([$request['id'], $request['center_id'], $request['workshop_id'], $teacherId, $score]);
        $allocId = $this->db->lastInsertId();

        // Crear Slot
        $stmt = $this->db->prepare('INSERT INTO workshop_slots (workshop_id, allocation_id, start_time, end_time, is_booked) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$request['workshop_id'], $allocId, $slot['start'], $slot['end']]);

        // Actualitzar Request
        $this->db->prepare('UPDATE requests SET status = "assigned" WHERE id = ?')->execute([$request['id']]);
    }
}
