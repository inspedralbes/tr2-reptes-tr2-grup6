<?php
/**
 * Model: WorkshopSlot
 * Gestiona les places o sessions de tallers
 */

namespace Models;

use Services\Database;

class WorkshopSlot
{
    private $db;
    
    public $id;
    public $workshop_id;
    public $date_time;
    public $location;
    public $capacity;
    public $tutor_id;
    public $status;
    public $created_at;
    public $updated_at;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir totes les places d'un taller
     */
    public static function findByWorkshop($workshop_id, $include_booked = true)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT id, workshop_id, date_time, location, capacity, tutor_id, status, created_at
                FROM workshop_slots 
                WHERE workshop_id = ?";
        
        if (!$include_booked) {
            $sql .= " AND status IN ('available', 'locked')";
        }
        
        $sql .= " ORDER BY date_time ASC";
        
        $results = $db->query($sql, [$workshop_id]);
        
        $slots = [];
        foreach ($results as $row) {
            $slot = new self();
            $slot->mapFromDatabase($row);
            $slots[] = $slot;
        }
        
        return $slots;
    }
    
    /**
     * Trobar place per ID
     */
    public static function findById($id)
    {
        $db = Database::getInstance();
        $sql = "SELECT id, workshop_id, date_time, location, capacity, tutor_id, status, created_at
                FROM workshop_slots WHERE id = ? LIMIT 1";
        
        $result = $db->query($sql, [$id]);
        
        if (!empty($result)) {
            $slot = new self();
            $slot->mapFromDatabase($result[0]);
            return $slot;
        }
        
        return null;
    }
    
    /**
     * Crear nova place
     */
    public static function create($workshop_id, $date_time, $location, $capacity, $tutor_id = null)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO workshop_slots (workshop_id, date_time, location, capacity, tutor_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'available', NOW())";
        
        $result = $db->query($sql, [$workshop_id, $date_time, $location, $capacity, $tutor_id]);
        
        if ($result) {
            $slot = new self();
            $slot->workshop_id = $workshop_id;
            $slot->date_time = $date_time;
            $slot->location = $location;
            $slot->capacity = $capacity;
            $slot->tutor_id = $tutor_id;
            $slot->status = 'available';
            
            return $slot;
        }
        
        return null;
    }
    
    /**
     * Bloquejar una place (per evitar double booking)
     */
    public function lock($lock_duration_minutes = 5)
    {
        $sql = "UPDATE workshop_slots SET status = 'locked', updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$this->id]);
        
        if ($result) {
            $this->status = 'locked';
        }
        
        return $result;
    }
    
    /**
     * Desbloqueja una place
     */
    public function unlock()
    {
        if ($this->status === 'locked') {
            $sql = "UPDATE workshop_slots SET status = 'available', updated_at = NOW() WHERE id = ?";
            $result = $this->db->query($sql, [$this->id]);
            
            if ($result) {
                $this->status = 'available';
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Marcar place com booked (reservada)
     */
    public function book()
    {
        $sql = "UPDATE workshop_slots SET status = 'booked', updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$this->id]);
        
        if ($result) {
            $this->status = 'booked';
        }
        
        return $result;
    }
    
    /**
     * Obtenir nombre d'usuaris assignats a aquesta place
     */
    public function getAssignedCount()
    {
        $sql = "SELECT COUNT(*) as count FROM allocations WHERE slot_id = ? AND status = 'active'";
        $result = $this->db->query($sql, [$this->id]);
        
        return !empty($result) ? intval($result[0]['count']) : 0;
    }
    
    /**
     * Comprovar si la place està plena
     */
    public function isFull()
    {
        return $this->getAssignedCount() >= $this->capacity;
    }
    
    /**
     * Obtenir places disponibles
     */
    public function getAvailableSpots()
    {
        return max(0, $this->capacity - $this->getAssignedCount());
    }
    
    /**
     * Canviar l'estat de la place
     */
    public function setStatus($status)
    {
        $valid_statuses = ['available', 'locked', 'booked', 'completed', 'cancelled'];
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $sql = "UPDATE workshop_slots SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$status, $this->id]);
        
        if ($result) {
            $this->status = $status;
        }
        
        return $result;
    }
    
    /**
     * Obtenir detalls de la place amb usuaris assignats
     */
    public function getDetails()
    {
        $assigned = $this->getAssignedCount();
        
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'date_time' => $this->date_time,
            'location' => $this->location,
            'capacity' => $this->capacity,
            'tutor_id' => $this->tutor_id,
            'status' => $this->status,
            'assigned_count' => $assigned,
            'available_spots' => $this->getAvailableSpots(),
            'is_full' => $this->isFull()
        ];
    }
    
    /**
     * Convertir a array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'workshop_id' => $this->workshop_id,
            'date_time' => $this->date_time,
            'location' => $this->location,
            'capacity' => $this->capacity,
            'tutor_id' => $this->tutor_id,
            'status' => $this->status,
            'created_at' => $this->created_at
        ];
    }
    
    /**
     * Helper per mappejar resultats de BD
     */
    private function mapFromDatabase($row)
    {
        $this->id = $row['id'];
        $this->workshop_id = $row['workshop_id'];
        $this->date_time = $row['date_time'];
        $this->location = $row['location'];
        $this->capacity = $row['capacity'];
        $this->tutor_id = $row['tutor_id'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
    }
}