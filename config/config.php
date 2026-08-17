<?php
session_start();

// Email configuration (for verification)
define('MAIL_FROM', 'noreply@yourdomain.com');
define('BASE_URL', 'http://localhost/material-php-pdo');

define('DB_HOST', 'localhost');
define('DB_NAME', 'material_pdo');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>