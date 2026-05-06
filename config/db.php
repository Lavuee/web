<?php
// Establishes the core database connection parameters.
$host = '127.0.0.1';
$db   = 'pines_ams';
$user = 'root'; 
$pass = '';   
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                  
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Suppresses raw database errors from outputting to the browser.
    error_log("DB connection failed: " . $e->getMessage());
    die("A database error occurred. Please contact the administrator.");
}
?>