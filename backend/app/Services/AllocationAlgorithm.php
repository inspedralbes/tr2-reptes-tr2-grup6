<?php
namespace Services;

use Models\Request;
use Models\Allocation;
use Models\Workshop;
use Models\User;
use Services\Database;

class AllocationAlgorithm {
    
    /**
     * Executes the smart allocation algorithm.
     * Assigns teachers to pending requests based on specialty match, score, and availability.
     * 
     * @param int $period_id
     * @return array Results
     */
    public static function executeAllocation($period_id) {
        $db = Database::getInstance();
        $pdo = $db->getPDO(); 
        
        try {
            // 1. Get Pending Requests
            $sql = "SELECT r.*, w.name as workshop_name, w.ambit, w.duration_hours, c.name as center_name 
                    FROM requests r 
                    JOIN workshops w ON r.workshop_id = w.id 
                    JOIN centers c ON r.center_id = c.id
                    WHERE r.status IN ('pending', 'approved') 
                    ORDER BY r.priority_level DESC, r.created_at ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([]);
            $requests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Results structure compatible with Frontend
            $results = [
                'processed' => 0,
                'assigned' => 0,
                'unassigned' => 0,
                'successRate' => 0,
                'strategy_used' => 'Standard Priority',
                'allocations' => [],
                'unassignedReasons' => []
            ];

            if (empty($requests)) {
                return $results;
            }

            // 2. Get All Active Teachers
            $teacherSql = "SELECT * FROM users WHERE role = 'teacher' AND is_active = 1";
            $teachers = $pdo->query($teacherSql)->fetchAll(\PDO::FETCH_ASSOC);

            // 3. Process Each Request
            foreach ($requests as $request) {
                $results['processed']++;
                
                // 3a. Find Candidates
                $candidates = [];
                foreach ($teachers as $teacher) {
                    $score = self::calculateTeacherScore($request, $teacher);
                    if ($score > 0) {
                        $candidates[] = ['teacher' => $teacher, 'score' => $score];
                    }
                }

                usort($candidates, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });

                // 3b. Try to find a slot
                $assigned = false;
                foreach ($candidates as $candidate) {
                    $teacher = $candidate['teacher'];
                    $slot = self::findAvailableSlot($pdo, $teacher['id'], $request['duration_hours']);
                    
                    if ($slot) {
                        // Success! Assign this teacher
                        self::createAllocationTransaction($pdo, $request, $teacher, $slot, $candidate['score']);
                        
                        $results['assigned']++;
                        $results['allocations'][] = [
                            'id' => uniqid(), // Temporary generated ID for frontend key
                            'request_id' => $request['id'],
                            'workshop_name' => $request['workshop_name'],
                            'instructor_name' => $teacher['full_name'],
                            'assigned_center_id' => $request['center_id'], // Center ID needed for getCenterName helper
                            'center_name' => $request['center_name'], // Helper if needed
                            'score' => $candidate['score'],
                            'slot' => $slot,
                            'strategy_used' => 'Score & Avaliability'
                        ];
                        $assigned = true;
                        break; 
                    }
                }

                if (!$assigned) {
                    $results['unassigned']++;
                    $results['unassignedReasons'][] = [
                        'request_id' => $request['id'],
                        'workshop_name' => $request['workshop_name'],
                        'user_name' => $request['center_name'], // In this context user is the center requesting
                        'reason' => empty($candidates) ? 'No teachers with matching specialty' : 'Teachers found but no slot available',
                        'severity' => 'warning'
                    ];
                }
            }
            
            // Calculate success rate
            if ($results['processed'] > 0) {
                $results['successRate'] = round(($results['assigned'] / $results['processed']) * 100, 1);
            }

            return $results;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Score a teacher suitability for a request
     */
    private static function calculateTeacherScore($request, $teacher) {
        $score = 10; // Base score
        
        // Match Ambit/Specialty
        $teacherSpecialty = strtolower($teacher['specialty'] ?? '');
        $requestAmbit = strtolower($request['ambit'] ?? '');
        
        // Exact or partial match
        if ($requestAmbit && $teacherSpecialty && strpos($teacherSpecialty, $requestAmbit) !== false) {
            $score += 50;
        }

        // TODO: Proximity or historical matching could improve this
        return $score;
    }

    /**
     * Find the first available time slot for a teacher
     */
    private static function findAvailableSlot($pdo, $teacherId, $durationHours) {
        // Simplified Logic: Look ahead 30 days, M-F, 9am or 3pm
        $startDate = new \DateTime('tomorrow');
        $endDate = new \DateTime('+30 days');
        
        // Get existing busy slots for teacher
        $stmt = $pdo->prepare("
            SELECT ws.start_time, ws.end_time 
            FROM workshop_slots ws
            JOIN allocations a ON ws.allocation_id = a.id
            WHERE a.assigned_teacher_id = ? 
            AND ws.start_time BETWEEN ? AND ?
        ");
        $stmt->execute([$teacherId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        $busySlots = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $current = clone $startDate;
        while ($current < $endDate) {
            // Skip weekends
            if ($current->format('N') > 5) {
                $current->modify('+1 day');
                continue;
            }

            // Potential slots: Morning (09:00) and Afternoon (15:00)
            $starts = [];
            
            // Morning
            $m = clone $current; 
            $m->setTime(9, 0, 0);
            $starts[] = $m;

            // Afternoon (if short enough)
            if ($durationHours <= 4) {
                $a = clone $current; 
                $a->setTime(15, 0, 0);
                $starts[] = $a;
            }

            foreach ($starts as $start) {
                $end = clone $start;
                $end->modify("+$durationHours hours");
                
                if (!self::isOverlapping($start, $end, $busySlots)) {
                    return [
                        'start' => $start->format('Y-m-d H:i:s'), 
                        'end' => $end->format('Y-m-d H:i:s')
                    ];
                }
            }
            
            $current->modify('+1 day');
        }

        return null;
    }

    private static function isOverlapping($start, $end, $busySlots) {
        $s = $start->getTimestamp();
        $e = $end->getTimestamp();
        
        foreach ($busySlots as $slot) {
            $bs = strtotime($slot['start_time']);
            $be = strtotime($slot['end_time']);
            
            // Check for overlap
            if ($s < $be && $e > $bs) {
                return true;
            }
        }
        return false;
    }

    private static function createAllocationTransaction($pdo, $request, $teacher, $slot, $score) {
        // 1. Allocation Record
        // Verify columns match DB schema
        $stmt = $pdo->prepare("INSERT INTO allocations (request_id, assigned_center_id, assigned_workshop_id, assigned_teacher_id, status, algorithm_priority_score, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
        $stmt->execute([
            $request['id'],
            $request['center_id'],
            $request['workshop_id'],
            $teacher['id'],
            $score
        ]);
        $allocId = $pdo->lastInsertId();

        // 2. Slot Record
        $stmtSlot = $pdo->prepare("INSERT INTO workshop_slots (workshop_id, allocation_id, start_time, end_time, is_booked) VALUES (?, ?, ?, ?, 1)");
        $stmtSlot->execute([
            $request['workshop_id'],
            $allocId,
            $slot['start'],
            $slot['end']
        ]);

        // 3. Update Request
        $stmtReq = $pdo->prepare("UPDATE requests SET status = 'assigned' WHERE id = ?");
        $stmtReq->execute([$request['id']]);
    }
    
    /**
     * Get Analytics for Dashboard
     */
    public static function getAnalytics($period_id) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN r.status = 'assigned' THEN 1 ELSE 0 END) as allocated_count,
                    SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                FROM requests r
                WHERE r.period_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$period_id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}