<?php
// backend/db_migrations/migrate_tallers_date.php
include_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Comprobar si la columna ya existe
    $checkQuery = "SHOW COLUMNS FROM tallers LIKE 'data'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $query = "ALTER TABLE tallers ADD COLUMN data DATETIME NULL AFTER descripcio";
        $db->exec($query);
        echo "Columna 'data' añadida correctamente a la tabla 'tallers'.\n";
    } else {
        echo "La columna 'data' ya existe en la tabla 'tallers'.\n";
    }

} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage() . "\n";
}
?>
