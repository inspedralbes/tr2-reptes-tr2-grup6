<?php
// router.php
// Aquest fitxer s'utilitza per al servidor intern de PHP (php -S)
// Redirigeix totes les peticions API a index.php i serveix fitxers estàtics si existeixen

if (php_sapi_name() == 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    
    // Si el fitxer existeix (imatges, css, etc), servir-lo directament
    if (is_file($file)) {
        return false;
    }
}

// En cas contrari, carregar index.php "hardcoded" per evitar problemes de rutes
require __DIR__ . '/index.php';
