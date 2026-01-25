<?php
// Standalone hash generation matches PasswordHasher.php settings
$password = 'Admin1234';
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 2
]);

echo $hash;
?>
