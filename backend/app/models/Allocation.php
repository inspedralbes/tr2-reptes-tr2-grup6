<?php
/**
 * Model: Allocation
 * Gestiona les assignacions finals dels tallers als usuaris
 */

namespace Models;

use Services\Database;

class Allocation
{
    private $db;
    
    public $id;
    public $request_id;
    public $assigned_workshop_id;
    public $assigned_teacher_id;
    public $assigned_center_id;
    // public $slot_id; // Removed: Relationship is inverse (Slot -> Allocation)
    public $status;
    public $score;
    public $created_at;
    public $updated_at;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir assignacions d'un docent
     */
    public static function findByTeacherId($teacher_id)
    {
        $db = Database::getInstance();
        $sql = "SELECT a.id, a.request_id, a.assigned_workshop_id, a.assigned_teacher_id, a.assigned_center_id, a.status, a.algorithm_priority_score as score, a.created_at,
                       w.name as workshop_name, w.modality,
                       ws.id as slot_id, DATE(ws.start_time) as slot_date, TIME(ws.start_time) as slot_start, TIME(ws.end_time) as slot_end,
                       c.name as center_name
                FROM allocations a
                LEFT JOIN workshops w ON a.assigned_workshop_id = w.id
                LEFT JOIN workshop_slots ws ON ws.allocation_id = a.id
                LEFT JOIN centers c ON a.assigned_center_id = c.id
                WHERE a.assigned_teacher_id = ? ORDER BY a.created_at DESC";
        
        $results = $db->query($sql, [$teacher_id]);
        
        $allocations = [];
        foreach ($results as $row) {
            $allocation = new self();
            $allocation->mapFromDatabase($row);
            $allocations[] = $allocation;
        }
        
        return $allocations;
    }

    /**
     * Obtenir totes les assignacions (Admin)
     */
    public static function all()
    {
        $db = Database::getInstance();
        $sql = "SELECT a.id, a.request_id, a.assigned_workshop_id, a.assigned_teacher_id, a.assigned_center_id, a.status, a.algorithm_priority_score as score, a.created_at,
                       w.name as workshop_name,
                       u.full_name as user_name,
                       c.name as center_name,
                       ws.id as slot_id, DATE(ws.start_time) as slot_date, TIME(ws.start_time) as slot_start
                FROM allocations a
                LEFT JOIN workshops w ON a.assigned_workshop_id = w.id
                LEFT JOIN users u ON a.assigned_teacher_id = u.id
                LEFT JOIN centers c ON a.assigned_center_id = c.id
                LEFT JOIN workshop_slots ws ON ws.allocation_id = a.id
                ORDER BY a.created_at DESC";
        
        $results = $db->query($sql);
        
        $allocations = [];
        foreach ($results as $row) {
            $allocation = new self();
            $allocation->mapFromDatabase($row);
            $allocations[] = $allocation;
        }
        
        return $allocations;
    }
    
    /**
     * Obtenir assignacions per centre
     */
    public static function findByCenter($center_id)
    {
        $db = Database::getInstance();
        $sql = "SELECT a.id, a.request_id, a.assigned_workshop_id, a.assigned_teacher_id, a.assigned_center_id, a.status, a.algorithm_priority_score as score, a.created_at,
                       w.name as workshop_name, w.modality,
                       u.full_name as user_name,
                       ws.id as slot_id, DATE(ws.start_time) as slot_date, TIME(ws.start_time) as slot_start
                FROM allocations a
                LEFT JOIN workshops w ON a.assigned_workshop_id = w.id
                LEFT JOIN users u ON a.assigned_teacher_id = u.id
                LEFT JOIN workshop_slots ws ON ws.allocation_id = a.id
                WHERE a.assigned_center_id = ? ORDER BY a.created_at DESC";
        
        $results = $db->query($sql, [$center_id]);
        
        $allocations = [];
        foreach ($results as $row) {
            $allocation = new self();
            $allocation->mapFromDatabase($row);
            $allocations[] = $allocation;
        }
        
        return $allocations;
    }
    
    /**
     * Trobar assignació per ID
     */
    public static function findById($id)
    {
        $db = Database::getInstance();
        $sql = "SELECT a.id, a.request_id, a.assigned_workshop_id, a.assigned_teacher_id, a.assigned_center_id, a.status, a.algorithm_priority_score as score, a.created_at
                FROM allocations a
                WHERE a.id = ? LIMIT 1";
        
        $stmt = $db->query($sql, [$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $allocation = new self();
            $allocation->mapFromDatabase($row);
            return $allocation;
        }
        
        return null;
    }
    
    /**
     * Crear nova assignació
     */
    public static function create($request_id, $workshop_id, $user_id, $center_id, $score = 0)
    {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO allocations (request_id, assigned_workshop_id, assigned_teacher_id, assigned_center_id, status, algorithm_priority_score, created_at) 
                VALUES (?, ?, ?, ?, 'active', ?, NOW())";
        
        $result = $db->query($sql, [$request_id, $workshop_id, $user_id, $center_id, $score]);
        
        if ($result) {
            $allocation = new self();
            $allocation->request_id = $request_id;
            $allocation->assigned_workshop_id = $workshop_id;
            $allocation->assigned_teacher_id = $user_id;
            $allocation->assigned_center_id = $center_id;
            // $allocation->slot_id = $slot_id; // managed elsewhere or via update
            $allocation->status = 'active';
            $allocation->score = $score;
            
            return $allocation;
        }
        
        return null;
    }
    
    /**
     * Actualitzar assignació
     */
    public function update($status = null, $slot_id = null, $score = null)
    {
        $updates = [];
        $params = [];
        
        if ($status !== null) {
            $updates[] = "status = ?";
            $params[] = $status;
            $this->status = $status;
        }
        
        // Slot ID not directly updatable here if it's external, or use slot mgmt
        // But if slot is inverse, we can't update allocation table to change slot.
        // We must update workshop_slots table to point to this allocation.
        // Skipping slot_id update here for now as column is gone.
        
        if ($score !== null) {
            $updates[] = "algorithm_priority_score = ?";
            $params[] = $score;
            $this->score = $score;
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $this->id;
        
        $sql = "UPDATE allocations SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Canviar estat de l'assignació
     */
    public function setStatus($status)
    {
        $valid_statuses = ['active', 'completed', 'cancelled', 'failed'];
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        return $this->update($status);
    }
    
    /**
     * Obtenir assignacions actives per taller
     */
    public static function findByWorkshop($workshop_id, $status = 'active')
    {
        $db = Database::getInstance();
        
        $sql = "SELECT a.id, a.request_id, a.assigned_workshop_id, a.assigned_teacher_id, a.assigned_center_id, a.status, a.algorithm_priority_score as score, a.created_at,
                       u.full_name as user_name, u.email,
                       c.name as center_name,
                       ws.id as slot_id, DATE(ws.start_time) as slot_date, TIME(ws.start_time) as slot_start
                FROM allocations a
                LEFT JOIN users u ON a.assigned_teacher_id = u.id
                LEFT JOIN centers c ON a.assigned_center_id = c.id
                LEFT JOIN workshop_slots ws ON ws.allocation_id = a.id
                WHERE a.assigned_workshop_id = ?";
        
        $params = [$workshop_id];
        
        if ($status !== null) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY a.created_at";
        
        $results = $db->query($sql, $params);
        
        $allocations = [];
        foreach ($results as $row) {
            $allocation = new self();
            $allocation->mapFromDatabase($row);
            $allocations[] = $allocation;
        }
        
        return $allocations;
    }
    
    /**
     * Eliminar assignació
     */
    public function delete()
    {
        $sql = "DELETE FROM allocations WHERE id = ?";
        return $this->db->query($sql, [$this->id]);
    }
    
    /**
     * Convertir a array per JSON
     */
    public $user_name;
    public $workshop_name;
    public $center_name;
    public $slot_time; // mapped from slot_start
    public $slot_date;

    // ... (constructor)

    public function toArray()
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'assigned_workshop_id' => $this->assigned_workshop_id,
            'assigned_teacher_id' => $this->assigned_teacher_id,
            'assigned_center_id' => $this->assigned_center_id,
            'status' => $this->status,
            'score' => $this->score,
            'created_at' => $this->created_at,
            // Extra fields for frontend
            'user_name' => $this->user_name,
            'workshop_name' => $this->workshop_name,
            'center_name' => $this->center_name,
            'slot_time' => $this->slot_time,
            'slot_date' => $this->slot_date ?? null,
            'allocation_date' => $this->created_at // alias for frontend
        ];
    }
    
    private function mapFromDatabase($row)
    {
        $this->id = $row['id'];
        $this->request_id = $row['request_id'];
        $this->assigned_workshop_id = $row['assigned_workshop_id'];
        $this->assigned_teacher_id = $row['assigned_teacher_id'];
        $this->assigned_center_id = $row['assigned_center_id'];
        $this->status = $row['status'];
        $this->score = $row['score'];
        $this->created_at = $row['created_at'];
        
        // Map extra fields if they exist in query
        $this->user_name = $row['user_name'] ?? null;
        $this->workshop_name = $row['workshop_name'] ?? null;
        $this->center_name = $row['center_name'] ?? null;
        $this->slot_time = $row['slot_start'] ?? null;
        $this->slot_date = $row['slot_date'] ?? null;
    }
}