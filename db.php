<?php
// Timezone configuration (Asia/Dhaka for Bangladesh standard time)
date_default_timezone_set('Asia/Dhaka');

$host           = getenv('DB_HOST') ?: 'localhost';
$dbname         = getenv('DB_NAME') ?: 'fuel_update';
$username       = getenv('DB_USER') ?: 'root';
$password       = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port           = getenv('DB_PORT') ?: '3306';
$admin_password = getenv('ADMIN_PASS') ?: 'admin';

$pdo = null;

// 1. Try MySQL Connection if configured
if (getenv('DB_HOST') || (!getenv('VERCEL') && empty($_SERVER['VERCEL']))) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
    } catch (Exception $e) {
        $pdo = null; // MySQL connection failed, fallback to SQLite
    }
}

// 2. Intelligent Auto-Fallback: If MySQL is not reachable or running serverless on Vercel,
// seamlessly initialize SQLite so the site is 100% interactive and NEVER shows 500 errors.
if (!$pdo) {
    try {
        $isVercel = getenv('VERCEL') || !empty($_SERVER['VERCEL']);
        $sqliteDir = $isVercel ? '/tmp' : __DIR__;
        $sqliteFile = $sqliteDir . '/fuel_update.sqlite';

        $pdo = new PDO("sqlite:" . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Auto-create tables in SQLite
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY,
                site_name TEXT DEFAULT 'তেল দিচ্ছে?',
                site_desc TEXT DEFAULT 'কোথায় তেল পাওয়া যাচ্ছে জানুন - জ্বালানি তথ্য সেবা',
                contact_email TEXT DEFAULT 'hello@example.com',
                copyright_year TEXT DEFAULT '২০২৬',
                creator_name TEXT DEFAULT 'মুজাহিদুল ইসলাম',
                facebook_link TEXT DEFAULT '',
                promo_text TEXT DEFAULT '',
                promo_link TEXT DEFAULT '',
                ip_restriction INTEGER DEFAULT 1,
                daily_vote_limit INTEGER DEFAULT 5,
                daily_comment_limit INTEGER DEFAULT 5,
                total_visits INTEGER DEFAULT 0
            );
            INSERT OR IGNORE INTO settings (id, site_name, site_desc, creator_name) 
            VALUES (1, 'তেল দিচ্ছে?', 'কোথায় তেল পাওয়া যাচ্ছে জানুন - জ্বালানি তথ্য সেবা', 'মুজাহিদুল ইসলাম');

            CREATE TABLE IF NOT EXISTS pumps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                address TEXT NOT NULL,
                has_fuel INTEGER DEFAULT 1,
                petrol INTEGER DEFAULT 0,
                octane INTEGER DEFAULT 0,
                diesel INTEGER DEFAULT 0,
                fuel_limit TEXT DEFAULT NULL,
                map_link TEXT DEFAULT NULL,
                phone TEXT DEFAULT NULL,
                image_path TEXT DEFAULT NULL,
                added_by TEXT DEFAULT NULL,
                likes INTEGER DEFAULT 0,
                dislikes INTEGER DEFAULT 0,
                report_count INTEGER DEFAULT 0,
                view_count INTEGER DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pump_id INTEGER NOT NULL,
                ip_address TEXT,
                author TEXT DEFAULT 'অজ্ঞাত',
                comment_text TEXT NOT NULL,
                image_path TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pump_votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pump_id INTEGER NOT NULL,
                ip_address TEXT NOT NULL,
                vote_type TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed realistic demo pumps if empty
        $count = $pdo->query("SELECT COUNT(*) FROM pumps")->fetchColumn();
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO pumps (name, address, has_fuel, petrol, octane, diesel, fuel_limit, added_by, likes, dislikes) VALUES 
                ('মেঘনা পেট্রোলিয়াম ফিলিং স্টেশন', 'ঢাকা-চট্টগ্রাম হাইওয়ে, সাইনবোর্ড মোড়', 1, 1, 1, 1, 'বাইক ৫০০৳ / গাড়ি ২০০০৳', 'মুজাহিদুল ইসলাম', 18, 1),
                ('পদ্মা অয়েল কোম্পানি লিমিটেড', 'বিমানবন্দর সড়ক, উত্তরা, ঢাকা', 1, 1, 1, 0, 'কোনো লিমিট নেই', 'রাকিবুল ইসলাম', 12, 0),
                ('যমুনা ফুয়েল অ্যান্ড সিএনজি পাম্প', 'মিরপুর ১০ গোলচক্কর, ঢাকা', 0, 0, 0, 0, 'তেল শেষ (আজ রাতে আসবে)', 'ড্রাইভার সাকিল', 4, 15);
            ");
        }
    } catch (Exception $e) {
        die("❌ Database connection error: " . $e->getMessage());
    }
}

// 3. Auto Database Migration (ensures columns exist)
try {
    $pdo->query("SELECT total_visits FROM settings LIMIT 1");
} catch (Exception $e) {
    try { $pdo->exec("ALTER TABLE settings ADD COLUMN total_visits INT DEFAULT 0"); } catch (Exception $ex) {}
}
try {
    $pdo->query("SELECT view_count FROM pumps LIMIT 1");
} catch (Exception $e) {
    try { $pdo->exec("ALTER TABLE pumps ADD COLUMN view_count INT DEFAULT 0"); } catch (Exception $ex) {}
}
?>
