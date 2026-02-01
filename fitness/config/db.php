<?php
// Database configuration
define('DB_HOST', 'localhost'); // localhost
define('DB_NAME', 'np03cy4a240041'); // database name : fitness_club
define('DB_USER', 'np03cy4a240041'); // database user : root
define('DB_PASS', 'mp9smWnq3o'); // password : NULL

// Create PDO connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // In a real project, you'd log this error
    die('Database connection failed: ' . $e->getMessage());
}
?>