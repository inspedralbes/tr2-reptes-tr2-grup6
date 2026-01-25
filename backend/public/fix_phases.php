<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "Connected to database.\n";
    
    // Check if phases exist
    $stmt = $conn->query("SELECT COUNT(*) FROM phases");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "Phases table already has $count rows. Skipping seed.\n";
    } else {
        echo "Phases table is empty. Seeding data...\n";
        
        $sql = "INSERT INTO phases (id, name, description, start_date, end_date, features) VALUES
        (1, 'Fase 1: Exploració', 
         'Els centres poden explorar el catàleg de tallers disponibles',
         '2025-09-01', '2025-10-15',
         '{\"marketplace_view\": true, \"add_to_cart\": false, \"submit_requests\": false, \"view_allocations\": false, \"schedule_teachers\": false, \"feedback\": false}'),

        (2, 'Fase 2: Llista de Desitjos',
         'Els centres poden crear el seu carret i enviar sol·licituds de tallers',
         '2025-10-16', '2025-11-30',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": false, \"schedule_teachers\": false, \"feedback\": false}'),

        (3, 'Fase 3: La Concordança',
         'Els coordinadors d\'entre tallers i professors assignats',
         '2025-12-01', '2026-01-15',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": false, \"feedback\": false}'),

        (4, 'Fase 4: Calendari',
         'Docents poden agafar els tallers assignats',
         '2026-01-16', '2026-03-31',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": true, \"feedback\": false}'),

        (5, 'Fase 5: Execució',
         'Execució dels tallers programats',
         '2026-04-01', '2026-05-31',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": true, \"feedback\": false}'),

        (6, 'Fase 6: Avaluació',
         'Recollida de feedback i avaluació dels tallers',
         '2026-06-01', '2026-06-30',
         '{\"marketplace_view\": true, \"add_to_cart\": true, \"submit_requests\": true, \"view_allocations\": true, \"schedule_teachers\": true, \"feedback\": true}')";
         
        $conn->exec($sql);
        echo "Seeded 6 phases successfully.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
