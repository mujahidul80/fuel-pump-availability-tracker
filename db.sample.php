<?php
// Timezone configuration (Asia/Dhaka for Bangladesh standard time)
date_default_timezone_set('Asia/Dhaka');

// Database credentials (supports environment variables for Vercel/Railway or falls back to local)
$host     = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_NAME') ?: 'fuel_update';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port     = getenv('DB_PORT') ?: '3306';

// Admin Panel Password
$admin_password = getenv('ADMIN_PASS') ?: 'admin';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed. Please run install.php or setup.php first. Error: " . $e->getMessage());
}
?>
