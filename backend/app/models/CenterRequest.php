<?php
/**
 * Model: CenterRequest
 * Gestiona les sol·licituds d'alta de nous centres
 */

namespace Models;

use Services\Database;

class CenterRequest
{
    private $db;
    
    public $id;
    public $center_name;
    public $center_code;
    public $address;
    public $city;
    public $postal_code;
    public $contact_name;
    public $contact_email;
    public $contact_phone;
    public $contact_position;
    public $student_count;
    public $notes;
    public $status; // pending, approved, rejected
    public $created_at;
    public $updated_at;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir totes les sol·licituds
     */
    public static function all()
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM center_requests ORDER BY created_at DESC";
        
        $results = $db->query($sql);
        
        $requests = [];
        foreach ($results as $row) {
            $request = new self();
            foreach ($row as $key => $value) {
                if (property_exists($request, $key)) {
                    $request->$key = $value;
                }
            }
            $requests[] = $request;
        }
        
        return $requests;
    }

    /**
     * Crear nova sol·licitud de centre
     */
    public static function create($data)
    {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO center_requests (
            center_name, center_code, address, city, postal_code,
            contact_name, contact_email, contact_phone, contact_position,
            student_count, notes, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $params = [
            $data['center_name'],
            $data['center_code'],
            $data['address'],
            $data['city'],
            $data['postal_code'],
            $data['contact_name'],
            $data['contact_email'],
            $data['contact_phone'],
            $data['contact_position'] ?? null,
            $data['student_count'] ?? 0,
            $data['notes'] ?? null
        ];
        
        // Use insert method to get ID directly
        try {
            $id = $db->insert($sql, $params);
            
            if ($id) {
                $request = new self();
                foreach ($data as $key => $value) {
                    if (property_exists($request, $key)) {
                        $request->$key = $value;
                    }
                }
                $request->id = $id;
                $request->status = 'pending';
                
                return $request;
            }
        } catch (\Exception $e) {
            // Log error if needed
            return null;
        }
        
        return null;
    }

    /**
     * Convertir a array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'center_name' => $this->center_name,
            'center_code' => $this->center_code,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'contact_position' => $this->contact_position,
            'student_count' => $this->student_count,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at
        ];
    }
    
    public static function findById($id) {
        $db = Database::getInstance();
        $result = $db->fetchOne("SELECT * FROM center_requests WHERE id = ?", [$id]);
        if ($result) {
            $req = new self();
            foreach ($result as $k => $v) if(property_exists($req, $k)) $req->$k = $v;
            return $req;
        }
        return null;
    }
    
    public function updateStatus($status) {
        $db = Database::getInstance();
        $this->status = $status;
        $db->query("UPDATE center_requests SET status = ? WHERE id = ?", [$status, $this->id]);
    }
}
