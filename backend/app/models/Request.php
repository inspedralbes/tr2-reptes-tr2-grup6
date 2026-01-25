<?php
/**
 * Model: Request
 * Gestiona les sol·licituds de tallers dels usuaris
 */

namespace Models;

use Services\Database;

class Request
{
    private $db;
    
    public $id;
    public $center_id;
    public $workshop_id;
    public $period_id;
    public $num_students;
    public $priority_level;
    public $status;
    public $rejection_reason;
    public $created_at;
    public $updated_at;
    
    // Camps virtuals (joines)
    public $workshop_name;
    public $center_name;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir totes les sol·licituds d'un usuari
     */
    /**
     * Obtenir sol·licituds per centre ID
     */
    public static function findByCenter($center_id)
    {
        $db = Database::getInstance();
        $sql = "SELECT r.id, r.workshop_id, r.center_id, r.status, r.priority_level, r.period_id, r.created_at,
                       w.name as workshop_name, w.modality,
                       c.name as center_name
                FROM requests r
                LEFT JOIN workshops w ON r.workshop_id = w.id
                LEFT JOIN centers c ON r.center_id = c.id
                WHERE r.center_id = ? ORDER BY r.priority_level DESC, r.created_at DESC";
        
        $results = $db->query($sql, [$center_id]);
        
        $requests = [];
        foreach ($results as $row) {
            $request = new self();
            $request->mapFromDatabase($row);
            $requests[] = $request;
        }
        
        return $requests;
    }

    /**
     * Obtenir totes les sol·licituds (Admin)
     */
    public static function all()
    {
        $db = Database::getInstance();
        $sql = "SELECT r.id, r.workshop_id, r.center_id, r.status, r.priority_level, r.period_id, r.created_at,
                       w.name as workshop_name, w.modality,
                       c.name as center_name
                FROM requests r
                LEFT JOIN workshops w ON r.workshop_id = w.id
                LEFT JOIN centers c ON r.center_id = c.id
                ORDER BY r.created_at DESC";
        
        $results = $db->query($sql);
        
        $requests = [];
        foreach ($results as $row) {
            $request = new self();
            $request->mapFromDatabase($row);
            $requests[] = $request;
        }
        
        return $requests;
    }

    /**
     * Trobar sol·licitud per ID
     */
    public static function findById($id)
    {
        $db = Database::getInstance();
        $sql = "SELECT r.id, r.workshop_id, r.center_id, r.status, r.priority_level, r.period_id, r.created_at,
                       w.name as workshop_name,
                       c.name as center_name
                FROM requests r
                LEFT JOIN workshops w ON r.workshop_id = w.id
                LEFT JOIN centers c ON r.center_id = c.id
                WHERE r.id = ? LIMIT 1";
        
        $row = $db->fetchOne($sql, [$id]);
        
        if ($row) {
            $request = new self();
            $request->mapFromDatabase($row);
            return $request;
        }
        
        return null;
    }
    
    /**
     * Crear nova sol·licitud
     */
    public static function create($center_id, $workshop_id, $period_id, $num_students = 0, $priority_level = 0)
    {
        $db = Database::getInstance();
        
        // Comprovar que no existeix ja una sol·licitud igual
        $check_sql = "SELECT id FROM requests WHERE center_id = ? AND workshop_id = ? AND period_id = ? AND status != 'rejected' AND status != 'cancelled'";
        $stmt = $db->query($check_sql, [$center_id, $workshop_id, $period_id]);
        $check = $stmt->fetch();
        
        if ($check) {
            return null; // Sol·licitud ja existeix
        }
        
        $sql = "INSERT INTO requests (center_id, workshop_id, period_id, num_students, priority_level, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        $result = $db->query($sql, [$center_id, $workshop_id, $period_id, $num_students, $priority_level]);
        
        if ($result) {
            $request = new self();
            $request->center_id = $center_id;
            $request->workshop_id = $workshop_id;
            $request->period_id = $period_id;
            $request->num_students = $num_students;
            $request->priority_level = $priority_level;
            $request->status = 'pending';
            
            return $request;
        }
        
        return null;
    }
    
    /**
     * Actualitzar prioritat de sol·licitud
     */
    public function updatePriority($priority)
    {
        if ($this->status !== 'pending') {
            return false;
        }
        
        $sql = "UPDATE requests SET priority_level = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$priority, $this->id]);
        
        if ($result) {
            $this->priority_level = $priority;
        }
        
        return $result;
    }
    
    /**
     * Canviar l'estat de la sol·licitud (amb motiu opcional)
     */
    public function updateStatus($status, $reason = null)
    {
        $valid_statuses = ['pending', 'allocated', 'rejected', 'completed', 'cancelled', 'approved'];
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $sql = "UPDATE requests SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$status, $reason, $this->id]);
        
        if ($result) {
            $this->status = $status;
            $this->rejection_reason = $reason;
        }
        
        return $result;
    }

    // Deprecated alias for compatibility if needed
    public function setStatus($status) {
        return $this->updateStatus($status);
    }
    
    /**
     * Eliminar sol·licitud
     */
    public function delete()
    {
        $sql = "DELETE FROM requests WHERE id = ?";
        return $this->db->query($sql, [$this->id]);
    }
    
    /**
     * Obtenir sol·licituds pendents per centre
     */
    public static function getPendingByCenter($center_id, $period_id = null)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT r.id, r.workshop_id, r.center_id, r.status, r.priority_level, r.period_id, r.created_at,
                       w.name as workshop_name, w.modality, w.category, w.capacity,
                       c.name as center_name
                FROM requests r
                LEFT JOIN workshops w ON r.workshop_id = w.id
                LEFT JOIN centers c ON r.center_id = c.id
                WHERE r.center_id = ? AND r.status = 'pending'";
        
        $params = [$center_id];
        
        if ($period_id !== null) {
            $sql .= " AND r.period_id = ?";
            $params[] = $period_id;
        }
        
        $sql .= " ORDER BY r.priority_level DESC, r.created_at ASC";
        
        $results = $db->query($sql, $params);
        
        $requests = [];
        foreach ($results as $row) {
            $request = new self();
            $request->mapFromDatabase($row);
            $requests[] = $request;
        }
        
        return $requests;
    }
    
    /**
     * Convertir a array per JSON
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'workshop_id' => $this->workshop_id,
            'period_id' => $this->period_id,
            'num_students' => $this->num_students,
            'priority_level' => $this->priority_level,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'workshop_name' => $this->workshop_name,
            'center_name' => $this->center_name,
            'user_name' => $this->center_name, // Alias per compatibilitat amb frontend
        ];
    }
    
    /**
     * Helper per mappejar resultats de BD
     */
    private function mapFromDatabase($row)
    {
        $this->id = $row['id'];
        $this->center_id = $row['center_id'];
        $this->workshop_id = $row['workshop_id'];
        $this->period_id = $row['period_id'];
        $this->num_students = $row['num_students'] ?? 0;
        $this->priority_level = $row['priority_level'] ?? 0;
        $this->status = $row['status'];
        $this->rejection_reason = $row['rejection_reason'] ?? null;
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'] ?? null;
        $this->workshop_name = $row['workshop_name'] ?? null;
        $this->center_name = $row['center_name'] ?? null;
    }
}