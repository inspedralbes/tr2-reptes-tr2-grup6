<?php
/**
 * Model: User
 * Gestiona dades i operacions de l'usuari
 */

namespace Models;

use Services\Database;

class User {
    private $db;
    
    public $id;
    public $email;
    public $full_name;
    public $role;
    public $center_id;
    public $is_active;
    public $password_hash;
    public $created_at;
    public $updated_at;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Trobar usuari per email
     */
    public static function findByEmail($email)
    {
        $db = Database::getInstance();
        $sql = "SELECT id, email, full_name, role, center_id, is_active, password_hash, created_at 
                FROM users WHERE email = ? LIMIT 1";
        
        $result = $db->fetchAll($sql, [$email]);
        
        if (!empty($result)) {
            $user = new self();
            $user->id = $result[0]['id'];
            $user->email = $result[0]['email'];
            $user->full_name = $result[0]['full_name'];
            $user->role = $result[0]['role'];
            $user->center_id = $result[0]['center_id'];
            $user->is_active = (bool) $result[0]['is_active'];
            $user->password_hash = $result[0]['password_hash'];
            $user->created_at = $result[0]['created_at'];
            
            return $user;
        }
        
        return null;
    }
    
    /**
     * Trobar usuari per ID
     */
    public static function findById($id)
    {
        $db = Database::getInstance();
        $sql = "SELECT id, email, full_name, role, center_id, is_active, created_at 
                FROM users WHERE id = ? LIMIT 1";
        
        $result = $db->fetchAll($sql, [$id]);
        
        if (!empty($result)) {
            $user = new self();
            $user->id = $result[0]['id'];
            $user->email = $result[0]['email'];
            $user->full_name = $result[0]['full_name'];
            $user->role = $result[0]['role'];
            $user->center_id = $result[0]['center_id'];
            $user->is_active = (bool) $result[0]['is_active'];
            $user->created_at = $result[0]['created_at'];
            
            return $user;
        }
        
        return null;
    }
    
    /**
     * Crear nou usuari
     */
    public static function create($email, $full_name, $password_hash, $role = 'student', $center_id = null)
    {
        $db = Database::getInstance();
        $sql = "INSERT INTO users (email, full_name, password_hash, role, center_id, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW())";
        
        $id = $db->insert($sql, [$email, $full_name, $password_hash, $role, $center_id]);
        
        if ($id) {
            $user = new self();
            $user->id = $id;
            $user->email = $email;
            $user->full_name = $full_name;
            $user->role = $role;
            $user->center_id = $center_id;
            $user->is_active = true;
            
            return $user;
        }
        
        return null;
    }
    
    /**
     * Verificar que l'email ja existeix
     */
    public static function emailExists($email)
    {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        
        $result = $db->fetchOne($sql, [$email]);
        
        return !empty($result) && $result['count'] > 0;
    }
    
    /**
     * Retornar dades públiques de l'usuari (per JWT)
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'role' => $this->role,
            'center_id' => $this->center_id,
        ];
    }
    
    /**
     * Actualitzar perfil d'usuari
     */
    public function update($full_name = null, $center_id = null)
    {
        $updates = [];
        $params = [];
        
        if ($full_name !== null) {
            $updates[] = "full_name = ?";
            $params[] = $full_name;
            $this->full_name = $full_name;
        }
        
        if ($center_id !== null) {
            $updates[] = "center_id = ?";
            $params[] = $center_id;
            $this->center_id = $center_id;
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $this->id;
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Canviar contrasenya
     */
    public function changePassword($new_password_hash)
    {
        $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        
        $result = $this->db->query($sql, [$new_password_hash, $this->id]);
        
        if ($result) {
            $this->password_hash = $new_password_hash;
        }
        
        return $result;
    }
    
    /**
     * Desactivar compte
     */
    public function deactivate()
    {
        $sql = "UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$this->id]);
        
        if ($result) {
            $this->is_active = false;
        }
        
        return $result;
    }
    
    /**
     * Obté usuaris per centre
     */
    public static function findByCenter($center_id)
    {
        $db = Database::getInstance();
        $sql = "SELECT id, email, full_name, role, is_active, created_at 
                FROM users WHERE center_id = ? ORDER BY full_name";
        
        $results = $db->fetchAll($sql, [$center_id]);
        
        $users = [];
        foreach ($results as $row) {
            $user = new self();
            $user->id = $row['id'];
            $user->email = $row['email'];
            $user->full_name = $row['full_name'];
            $user->role = $row['role'];
            $user->center_id = $center_id;
            $user->is_active = (bool) $row['is_active'];
            $user->created_at = $row['created_at'];
            
            $users[] = $user;
        }
        
        return $users;
    }
}