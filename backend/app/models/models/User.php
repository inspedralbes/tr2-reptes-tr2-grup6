<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find user by email including center info
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.email, u.password_hash, u.role, u.full_name, 
                   c.id as center_id, c.name as center_name 
            FROM users u 
            LEFT JOIN centers c ON c.coordinator_id = u.id 
            WHERE u.email = ? 
            LIMIT 1
        ');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Create a new user
     * @param array $data
     * @return int|false Last inserted ID
     */
    public function create($data) {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (email, password_hash, role, full_name, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ');
        
        $success = $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'center_coord',
            $data['full_name']
        ]);

        return $success ? $this->pdo->lastInsertId() : false;
    }

    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
