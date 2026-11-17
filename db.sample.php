<?php
// Timezone configuration (Asia/Dhaka for Bangladesh standard time)
date_default_timezone_set('Asia/Dhaka');

// Database credentials
$host     = 'localhost';
$dbname   = 'fuel_update';
$username = 'root';
$password = '';

// Default Admin Panel Password
$admin_password = 'admin'; // Change this immediately after setup!

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed. Please run install.php or setup.php first. Error: " . $e->getMessage());
}
?>
