<?php
// Debug SQL Restore
// Aquest script llegeix restore_db.sql i l'executa pas a pas per trobar l'error.

require_once __DIR__ . '/config/Config.php';

echo "Connectant a BD...\n";
try {
    $pdo = new PDO(
        Config::getDbConnectionString(),
        Config::DB_USER,
        Config::DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Error connexió: " . $e->getMessage() . "\n");
}

echo "Llegint restore_db.sql...\n";
$sqlFile = __DIR__ . '/database/restore_db.sql';
$content = file_get_contents($sqlFile);

// Simple parser for SOURCE commands
$lines = explode("\n", $content);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '--') === 0) continue;
    
    if (strpos($line, 'SOURCE') === 0) {
        // Extract filename
        $parts = explode(" ", $line);
        $file = trim($parts[1], ";");
        // Fix path for local execution (outside docker) -> actually we run this inside docker usually.
        // But if running from host, paths /docker-entrypoint... are wrong.
        // We need to map /docker-entrypoint-initdb.d/ to current dir.
        $file = str_replace('/docker-entrypoint-initdb.d/', __DIR__ . '/database/', $file);
        
        echo "Executant fitxer: $file ...\n";
        
        if (!file_exists($file)) {
            echo "ERROR: Fitxer no trobat: $file\n";
            continue;
        }
        
        // Read file content
        $fileContent = file_get_contents($file);
        
        // Split by semicolon (rough split)
        // Better: use the same logic as mysql CLI?
        // We will just execute the whole file content if possible, 
        // OR split by ; to find specific error.
        
        // Let's split by ; but respect delimiters/procedures.
        // It's hard to parse properly in simple PHP.
        // But let's try to just run specific chunks?
        
        // Actually, let's just run the whole file query if strictly SQL.
        // But some files use DELIMITER //
        
        // Strategy: Run mysql CLI for each file individually via exec.
        // That gives us granularity.
        
        $cmd = "mysql -u " . Config::DB_USER . " -p" . Config::DB_PASSWORD . " -h " . Config::DB_HOST . " " . Config::DB_NAME . " < " . escapeshellarg($file) . " 2>&1";
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            echo "ERROR al fitxer $file:\n";
            echo implode("\n", $output) . "\n";
            exit(1);
        } else {
            echo "OK.\n";
        }
    } else {
        // Regular SQL line in restore_db? (CREATE DATABASE usually)
        // We skip create database if inside PHP PDO usually connected to DB?
        // Or just exec it.
        if (stripos($line, 'CREATE DATABASE') !== false) continue;
        if (stripos($line, 'USE') !== false) continue;
        
        // Ignoring other lines for now as restore_db only has SOURCEs mostly.
    }
}

echo "Restauració completada.\n";
