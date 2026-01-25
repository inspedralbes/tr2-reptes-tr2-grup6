<?php
/**
 * Script per arreglar l'ENUM de status a la taula requests
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../app/Services/Database.php';

use Services\Database;

try {
    echo "Connectant a la base de dades...\n";
    $db = Database::getInstance();
    $pdo = $db->getPDO();
    
    echo "Modificant columna status de la taula requests...\n";
    
    // Afegim 'approved' a la llista d'ENUM
    $sql = "ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'allocated', 'rejected', 'completed', 'cancelled', 'approved') DEFAULT 'pending'";
    
    $pdo->exec($sql);
    
    echo "✅ Taula requests actualitzada correctament.\n";
    echo "Ara suporta els estats: pending, allocated, rejected, completed, cancelled, approved.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
