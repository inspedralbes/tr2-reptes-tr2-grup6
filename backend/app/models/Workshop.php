<?php
/**
 * Model: Workshop
 * Gestiona dades de tallers i cursos
 */

namespace Models;

use Services\Database;

class Workshop
{
    private $db;
    
    public $id;
    public $name;
    public $description;
    public $modality;
    public $category;
    public $capacity;
    public $hours;
    public $tutor_id;
    public $academic_year_id;
    public $active;
    public $duration_days;
    public $created_at;
    public $updated_at;
    
    // Nous camps per a la gestió de tallers
    public $max_capacity;
    public $available_slots;
    public $instructor;
    public $location;
    public $theme;
    public $course;
    public $allowed_days;
    public $time_slots;
    public $images;
    public $start_date;
    public $end_date;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir tots els tallers actius
     */
    public static function getAll($include_inactive = false)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT w.id, w.name, w.description, w.modality, w.ambit, w.capacity, w.duration_hours,
                       w.is_active, w.created_at, w.updated_at,
                       w.instructor, w.location, w.theme, w.course,
                       w.allowed_days, w.time_slots, w.images, w.start_date, w.end_date,
                       COUNT(ws.id) as slots_available
                FROM workshops w
                LEFT JOIN workshop_slots ws ON w.id = ws.workshop_id AND ws.is_booked = 0";
        
        if (!$include_inactive) {
            $sql .= " WHERE w.is_active = 1";
        }
        
        $sql .= " GROUP BY w.id, w.name, w.description, w.modality, w.ambit, w.capacity, w.duration_hours, 
                       w.is_active, w.created_at, w.updated_at,
                       w.is_active, w.created_at, w.updated_at,
                       w.is_active, w.created_at, w.updated_at,
                       w.instructor, w.location, w.theme, w.course,
                       w.allowed_days, w.time_slots, w.images, w.start_date, w.end_date
                  ORDER BY w.name";
        
        $results = $db->query($sql, []);
        
        $workshops = [];
        foreach ($results as $row) {
            $workshop = new self();
            $workshop->mapFromDatabase($row);
            $workshops[] = $workshop;
        }
        
        return $workshops;
    }
    
    /**
     * Trobar taller per ID
     */
    public static function findById($id)
    {
        $db = Database::getInstance();
        $sql = "SELECT w.id, w.name, w.description, w.modality, w.ambit, w.capacity, w.duration_hours, 
                       w.is_active, w.created_at, w.updated_at,
                       w.instructor, w.location, w.theme, w.course,
                       w.allowed_days, w.time_slots, w.images, w.start_date, w.end_date,
                       COUNT(ws.id) as slots_available
                FROM workshops w
                LEFT JOIN workshop_slots ws ON w.id = ws.workshop_id
                WHERE w.id = ?
                GROUP BY w.id";
        
        $stmt = $db->query($sql, [$id]);
        $result = $stmt->fetch();
        
        if ($result) {
            $workshop = new self();
            $workshop->mapFromDatabase($result);
            return $workshop;
        }
        
        return null;
    }
    
    /**
     * Crear nou taller
     */
    public static function create($name, $description, $modality, $ambit, $capacity, $duration_hours)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO workshops (name, description, modality, ambit, capacity, duration_hours, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $result = $db->query($sql, [$name, $description, $modality, $ambit, $capacity, $duration_hours]);
        
        if ($result) {
            $workshop = new self();
            $workshop->name = $name;
            $workshop->description = $description;
            $workshop->modality = $modality;
            $workshop->category = $ambit;
            $workshop->capacity = $capacity;
            $workshop->hours = $duration_hours;
            $workshop->active = true;
            
            return $workshop;
        }
        
        return null;
    }
    
    /**
     * Obtenir tallers per modalitat
     */
    public static function findByModality($modality)
    {
        $db = Database::getInstance();
        $sql = "SELECT id, name, description, modality, ambit, capacity, duration_hours, is_active, created_at
                FROM workshops WHERE modality = ? AND is_active = 1 ORDER BY name";
        
        $results = $db->query($sql, [$modality]);
        
        $workshops = [];
        foreach ($results as $row) {
            $workshop = new self();
            $workshop->mapFromDatabase($row);
            $workshops[] = $workshop;
        }
        
        return $workshops;
    }
    
    /**
     * Obtenir tallers per categoria
     */
    public static function findByCategory($category)
    {
        $db = Database::getInstance();
        $sql = "SELECT id, name, description, modality, ambit, capacity, duration_hours, is_active, created_at
                FROM workshops WHERE ambit = ? AND is_active = 1 ORDER BY name";
        
        $results = $db->query($sql, [$category]);
        
        $workshops = [];
        foreach ($results as $row) {
            $workshop = new self();
            $workshop->mapFromDatabase($row);
            $workshops[] = $workshop;
        }
        
        return $workshops;
    }
    
    /**
     * Actualitzar taller
     */
    public function update($name = null, $description = null, $capacity = null, $hours = null)
    {
        $updates = [];
        $params = [];
        
        if ($name !== null) {
            $updates[] = "name = ?";
            $params[] = $name;
            $this->name = $name;
        }
        
        if ($description !== null) {
            $updates[] = "description = ?";
            $params[] = $description;
            $this->description = $description;
        }
        
        if ($capacity !== null) {
            $updates[] = "capacity = ?";
            $params[] = $capacity;
            $this->capacity = $capacity;
        }
        
        if ($hours !== null) {
            $updates[] = "duration_hours = ?";
            $params[] = $hours;
            $this->hours = $hours;
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $this->id;
        
        $sql = "UPDATE workshops SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Desactivar taller
     */
    public function deactivate()
    {
        $sql = "UPDATE workshops SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$this->id]);
        
        if ($result) {
            $this->active = false;
        }
        
        return $result;
    }
    
    /**
     * Obtenir places disponibles
     */
    public function getAvailableSlots()
    {
        $sql = "SELECT COUNT(*) as count FROM workshop_slots WHERE workshop_id = ? AND is_booked = 0";
        $result = $this->db->query($sql, [$this->id]);
        
        return !empty($result) ? $result[0]['count'] : 0;
    }
    
    /**
     * Convertir a array (per JSON)
     */
    public function toArray()
    {
        // Parsear JSON fields si son strings
        $allowedDays = $this->allowed_days;
        $timeSlots = $this->time_slots;
        $images = $this->images;
        
        if (is_string($allowedDays) && !empty($allowedDays)) {
            $allowedDays = json_decode($allowedDays, true);
        }
        if (is_string($timeSlots) && !empty($timeSlots)) {
            $timeSlots = json_decode($timeSlots, true);
        }
        if (is_string($images) && !empty($images)) {
            $images = json_decode($images, true);
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'modality' => $this->modality,
            'ambit' => $this->category,
            'capacity' => $this->capacity,
            'duration_hours' => $this->hours,
            'tutor_id' => $this->tutor_id,
            'academic_year_id' => $this->academic_year_id,
            'is_active' => (bool) $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'max_capacity' => $this->max_capacity,
            'available_slots' => $this->available_slots,
            'instructor' => $this->instructor,
            'location' => $this->location,
            'theme' => $this->theme,
            'course' => $this->course,
            'allowed_days' => $allowedDays ?? [],
            'time_slots' => $timeSlots ?? [],
            'images' => $images ?? [],
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration_days' => $this->duration_days,
        ];
    }
    
    /**
     * Helper per mappejar resultats de BD
     */
    private function mapFromDatabase($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->description = $row['description'] ?? null;
        $this->modality = $row['modality'] ?? null;
        $this->category = $row['ambit'] ?? null;
        $this->capacity = $row['capacity'] ?? null;
        $this->hours = $row['duration_hours'] ?? null;
        $this->tutor_id = $row['tutor_id'] ?? null;
        $this->academic_year_id = $row['academic_year_id'] ?? null;
        $this->active = (bool) ($row['is_active'] ?? true);
        $this->created_at = $row['created_at'] ?? null;
        $this->updated_at = $row['updated_at'] ?? null;
        
        // Nous camps
        $this->max_capacity = $row['capacity'] ?? null;
        $this->available_slots = $row['slots_available'] ?? null;
        $this->instructor = $row['instructor'] ?? null;
        $this->location = $row['location'] ?? null;
        $this->theme = $row['theme'] ?? null;
        $this->course = $row['course'] ?? null;
        $this->allowed_days = $row['allowed_days'] ?? null;
        $this->time_slots = $row['time_slots'] ?? null;
        $this->images = $row['images'] ?? null;
        $this->start_date = $row['start_date'] ?? null;
        $this->end_date = $row['end_date'] ?? null;
    }
}