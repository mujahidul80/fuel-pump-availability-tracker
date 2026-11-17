<?php
date_default_timezone_set('Asia/Dhaka'); // বাংলাদেশের রিয়েল টাইম সেট করা হলো

$host = 'localhost';
$dbname = 'fuel_update';
$username = 'root';
$password = '';
$admin_password = 'admin'; // অ্যাডমিন প্যানেলের পাসওয়ার্ড

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ ডেটাবেস কানেকশন ব্যর্থ হয়েছে। দয়া করে setup.php ফাইলটি আগে রান করুন। Error: " . $e->getMessage());
}

// স্বয়ংক্রিয় ডাটাবেস মাইগ্রেশন (নতুন কলাম যোগ করার জন্য)
try {
    // 'settings' টেবিলে 'total_visits' কলাম আছে কি না চেক করা
    $pdo->query("SELECT total_visits FROM settings LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE settings ADD COLUMN total_visits INT DEFAULT 0");
    } catch (Exception $ex) {}
}
try {
    // 'pumps' টেবিলে 'view_count' কলাম আছে কি না চেক করা
    $pdo->query("SELECT view_count FROM pumps LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE pumps ADD COLUMN view_count INT DEFAULT 0");
    } catch (Exception $ex) {}
}
?>