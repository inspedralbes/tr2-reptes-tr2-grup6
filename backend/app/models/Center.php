<?php
/**
 * Model: Center
 * Gestiona dades dels centres educatius
 */

namespace Models;

use Services\Database;

class Center {
    private $db;
    
    public $id;
    public $name;
    public $code;
    public $city;
    public $address;
    public $postal_code;
    public $phone;
    public $contact_email;
    public $coordinator_id;
    public $is_active;
    public $created_at;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear un nou centre
     */
    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO centers (name, code, city, address, postal_code, phone, contact_email, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $params = [
            $data['name'],
            $data['code'],
            $data['city'],
            $data['address'],
            $data['postal_code'],
            $data['phone'],
            $data['contact_email']
        ];
        
        try {
            $id = $db->insert($sql, $params);
            
            if ($id) {
                // Return just the ID or basic object. For now just ID is enough for controller logic, 
                // but let's try to return full object if possible, or just ID.
                // Let's return the object for consistency.
                return self::findById($id);
            }
        } catch (\Exception $e) {
            // Log error
            return null;
        }
        return null;
    }
    
    /**
     * Trobar per ID
     */
    public static function findById($id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM centers WHERE id = ?";
        $result = $db->fetchOne($sql, [$id]);
        
        if ($result) {
            $center = new self();
            // Map properties
            foreach ($result as $key => $value) {
                if (property_exists($center, $key)) {
                    $center->$key = $value;
                }
            }
            return $center;
        }
        return null;
    }
    
    /**
     * Obté tots els centres
     */
    public static function all() {
        $db = Database::getInstance();
        $sql = "SELECT c.*, u.full_name as coordinator_name, u.email as coordinator_email 
                FROM centers c 
                LEFT JOIN users u ON c.coordinator_id = u.id 
                ORDER BY c.name";
        return $db->fetchAll($sql);
    }

    /**
     * Actualitzar centre
     */
    public function update($data) {
        $updates = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
            $this->name = $data['name'];
        }
        if (isset($data['code'])) {
            $updates[] = "code = ?";
            $params[] = $data['code'];
            $this->code = $data['code'];
        }
        if (isset($data['address'])) {
            $updates[] = "address = ?";
            $params[] = $data['address'];
            $this->address = $data['address'];
        }
        // Add other fields as needed
        
        if (empty($updates)) return true;
        
        $updates[] = "updated_at = NOW()";
        $params[] = $this->id;
        
        $sql = "UPDATE centers SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }

    /**
     * Eliminar centre
     */
    public function delete() {
        // Also delete coordinator? Maybe just nullify.
        // Schema says: users.center_id ON DELETE SET NULL
        // But coordinator_id in centers table is a bit circular.
        
        // Let's delete the center.
        $sql = "DELETE FROM centers WHERE id = ?";
        return $this->db->query($sql, [$this->id]);
    }

    /**
     * Actualitzar coordinador
     */
    public function setCoordinator($userId) {
        $sql = "UPDATE centers SET coordinator_id = ? WHERE id = ?";
        $this->db->query($sql, [$userId, $this->id]);
        $this->coordinator_id = $userId;
    }
}
