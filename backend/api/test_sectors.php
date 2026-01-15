<?php
// Test para verificar sectores
include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Verificar si la tabla existe
    $query = "SHOW TABLES LIKE 'sectors'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Tabla 'sectors' existe: " . ($tableExists ? "SÍ" : "NO") . "\n\n";
    
    if ($tableExists) {
        // Contar sectores
        $query = "SELECT COUNT(*) as total FROM sectors";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total sectores: " . $result['total'] . "\n\n";
        
        // Listar sectores
        $query = "SELECT id, nom, actiu FROM sectors ORDER BY nom";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sectores encontrados:\n";
        foreach ($sectors as $sector) {
            echo "  - ID: {$sector['id']}, Nom: {$sector['nom']}, Actiu: {$sector['actiu']}\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
