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
// seamlessly initialize SQLite so the site is 100% interactive and loaded with all real data.
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
                ip_restriction INTEGER DEFAULT 1,
                daily_vote_limit INTEGER DEFAULT 2,
                daily_comment_limit INTEGER DEFAULT 5,
                site_name TEXT DEFAULT 'তেল দিচ্ছে?',
                site_desc TEXT DEFAULT 'চুয়াডাঙ্গা জ্বালানি তথ্য সেবা',
                contact_email TEXT DEFAULT '',
                copyright_year TEXT DEFAULT '২০২৬',
                creator_name TEXT DEFAULT 'মুজাহিদুল ইসলাম',
                facebook_link TEXT DEFAULT '',
                promo_text TEXT DEFAULT 'Powered By CHUADANGA-বাংলাদেশের প্রথম রাজধানী',
                promo_link TEXT DEFAULT 'https://www.facebook.com/groups/cdbpr',
                total_visits INTEGER DEFAULT 142
            );

            CREATE TABLE IF NOT EXISTS pumps (
                id INTEGER PRIMARY KEY,
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
                id INTEGER PRIMARY KEY,
                pump_id INTEGER NOT NULL,
                ip_address TEXT,
                author TEXT DEFAULT 'অজ্ঞাত',
                comment_text TEXT DEFAULT NULL,
                image_path TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pump_votes (
                id INTEGER PRIMARY KEY,
                pump_id INTEGER NOT NULL,
                ip_address TEXT NOT NULL,
                vote_type TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed settings
        $stmtSetCheck = $pdo->query("SELECT COUNT(*) FROM settings");
        if ($stmtSetCheck->fetchColumn() == 0) {
            $pdo->exec("
                INSERT INTO settings (id, ip_restriction, daily_vote_limit, daily_comment_limit, site_name, site_desc, contact_email, copyright_year, creator_name, facebook_link, promo_text, promo_link, total_visits)
                VALUES (1, 1, 2, 5, 'তেল দিচ্ছে?', 'চুয়াডাঙ্গা জ্বালানি তথ্য সেবা', '', '২০২৬', 'মুজাহিদুল ইসলাম', '', 'Powered By CHUADANGA-বাংলাদেশের প্রথম রাজধানী', 'https://www.facebook.com/groups/cdbpr', 142);
            ");
        }

        // Seed complete real pumps data
        $pumpCount = $pdo->query("SELECT COUNT(*) FROM pumps")->fetchColumn();
        if ($pumpCount == 0) {
            $pdo->exec("
                INSERT INTO pumps (id, name, address, has_fuel, petrol, octane, diesel, fuel_limit, map_link, phone, image_path, added_by, likes, dislikes, report_count, last_updated) VALUES
                (1, 'Highway Filling Station', 'Near Dingedah bazar, Jhenaidah - Chuadanga Road, Shankarchandra', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/MP2W5Ktm5QRrfgam8', '01711-398668', 'uploads/1774806457_69c965b9a1be9.webp', NULL, 7, 2, 0, '2026-04-02 15:43:01'),
                (3, 'মোজাম্মেল হক পেট্রল পাম্প', 'Chuadanga - Jhenaidah Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/Yff5SnSerk27JLXA7', NULL, 'uploads/1774886642_69ca9ef2481c0.webp', NULL, 8, 2, 0, '2026-04-04 00:31:35'),
                (4, 'সীমান্ত পেট্রোল পাম্প', 'Doulatdiar, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/RvzqHyjgAaNENJrM8', NULL, 'uploads/1774886913_69caa001c4f1b.webp', NULL, 7, 1, 0, '2026-04-02 01:29:46'),
                (5, 'চুয়াডাঙ্গা ফিলিং স্টেশন', 'Chuadanga - Alamdanga Rd, Chuadanga 7200', 1, 1, 1, 1, 'Full', 'https://maps.app.goo.gl/cXS4YfpPKRzUYerUA', NULL, 'uploads/1774887041_69caa081742d3.webp', NULL, 4, 1, 0, '2026-10-24 15:44:38'),
                (6, 'মনিরুল পেট্রল পাম্প & এলপিজি স্টেশন', 'Chuadanga - Jhenaidah Hwy, Sarajgonj', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/bRCm4QPQC1vpzL1H9', '01884456127', 'uploads/1774887362_69caa1c25287b.webp', NULL, 1, 0, 0, '2026-04-08 11:35:39'),
                (7, 'মামুন ফিলিং স্টেশন', 'Chuadanga - Jhenaidah Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/QttYMyxR1h4hAPvH8', NULL, 'uploads/1774887459_69caa2238ddbd.webp', NULL, 4, 0, 0, '2026-04-05 09:24:46'),
                (8, 'মেসার্স জোয়ার্দার পেট্রোলিয়াম', 'Chuadanga - Jhenaidah Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/vC3pLKDGVNdkTqW56', NULL, 'uploads/1774887557_69caa28591b2c.webp', NULL, 8, 6, 0, '2026-04-11 06:13:40'),
                (9, 'লালন শাহ ফিলিং স্টেশনের', 'Chuadanga - Jhenaidah Hwy, Sarajgonj', 0, 1, 0, 0, NULL, 'https://maps.app.goo.gl/UXLM9NCfdxbpi5zP8', NULL, 'uploads/1774887629_69caa2cd73c83.webp', NULL, 0, 0, 0, '2026-10-25 17:14:43'),
                (10, 'ইমরান ফিলিং স্টেশন', 'Jessore-Chuadanga Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/mLTE57dYxcpCBzY5A', '01760963535', 'uploads/1774887956_69caa414c77fc.webp', NULL, 1, 0, 0, '2026-03-31 02:18:51'),
                (11, 'Shugondha Filling Station', 'Chuadanga - Meherpur Hwy, Alukdia', 1, 1, 0, 1, NULL, 'https://maps.app.goo.gl/ZW9j1YaWarNRJ6KW8', '01912314443', 'uploads/1774978997_69cc07b567db9.webp', NULL, 3, 0, 0, '2026-04-10 04:29:42'),
                (12, 'বিশ্বাস ফি‌লিং স্টীশন', 'আ‌লুক‌দিয়া বাজার, চুয়াডাঙ্গা টু মে‌হেরপুর রোড', 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'ismail', 2, 1, 1, '2026-04-04 05:33:22'),
                (13, 'হক ফিলিং স্টেশন', 'আলমডাঙ্গা', 1, 1, 1, 1, 'Unlimited', 'https://maps.app.goo.gl/2LGnhumXjT2kDg548', NULL, 'uploads/1774983370_69cc18cadf128.webp', 'Sheikh Al Mamun', 4, 4, 0, '2026-05-15 02:38:55'),
                (14, 'উত্তরা ফিলিং স্টেশন', 'আলমডাঙ্গা', 1, 1, 1, 0, '500', 'https://maps.app.goo.gl/Ei7LKSeMyoVsBUTu5', NULL, 'uploads/1774983721_69cc1a290e99e.webp', 'Sheikh Al Mamun', 5, 0, 0, '2026-04-12 02:38:15'),
                (15, 'জীবননগর ফিলিং স্টেশন', 'জীবননগর', 0, 0, 0, 0, '500', NULL, NULL, NULL, 'মোঃ শামীম হোছাইন', 1, 1, 0, '2026-04-01 07:24:03'),
                (16, 'দর্শনা ফিলিং স্টেশন', 'দর্শনা', 0, 0, 0, 0, '৩০০', NULL, NULL, NULL, NULL, 1, 0, 0, '2026-04-01 07:24:53'),
                (18, 'এনামুল হক ফিলিং স্টেশন', 'মুন্সিগঞ্জ পশু হাট, আলমডাঙ্গা, চুয়াডাঙ্গা', 0, 0, 0, 0, '500', 'https://maps.app.goo.gl/EU6n6Yb5bJHxbVJo6', NULL, NULL, NULL, 0, 0, 0, '2026-04-02 01:49:19');
            ");
        }

        // Seed real comments
        $commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
        if ($commentCount == 0) {
            $pdo->exec("
                INSERT INTO comments (id, pump_id, ip_address, author, comment_text, image_path, created_at) VALUES
                (1, 8, '162.12.213.2', 'অজ্ঞাত', 'বুধবার তেল দেবে', NULL, '2026-03-30 17:37:40'),
                (2, 13, '37.111.243.196', 'অজ্ঞাত', 'সকাল ৮ টা ফুয়েল দিবে।', NULL, '2026-03-31 18:57:19'),
                (3, 5, '37.111.244.117', 'অজ্ঞাত', 'কম আজকে একটু', 'uploads/1775090819_upd_69cdbc83a4f3a.webp', '2026-04-02 00:46:59'),
                (4, 7, '37.111.242.223', 'অজ্ঞাত', 'আগের থেকে কম', 'uploads/1775111171_upd_69ce0c0320dc7.webp', '2026-04-02 06:26:11'),
                (5, 11, '162.12.213.5', 'অজ্ঞাত', 'সকাল ৭টা থেকে এখন পর্যন্ত দিচ্ছে', NULL, '2026-04-02 06:49:37'),
                (6, 3, '103.89.240.45', 'অজ্ঞাত', '‌তেল আ‌ছে', NULL, '2026-04-03 00:29:05'),
                (7, 8, '2400:c600:5264:dd7c::1', 'অজ্ঞাত', 'বাস টার্মিনাল পার হয়ে মামুন ফিলিং স্টেশন পর্যন্ত চলে গেছে। পাম্পে লাইন মেইনটেন্যান্স করছে না বিধায় তেল দিতে দেরি করছে।', 'uploads/1775178244_upd_69cf1204ecf4e.webp', '2026-04-03 01:04:05'),
                (8, 3, '2400:c600:5264:dd7c::1', 'অজ্ঞাত', 'লাইন কালুর হোটেল পর্যন্ত চলে গেছে।', 'uploads/1775178392_upd_69cf1298170e2.webp', '2026-04-03 01:06:33'),
                (9, 14, '103.31.179.67', 'অজ্ঞাত', '৫০০+ গাড়ির সিরিয়াল', NULL, '2026-04-03 04:34:20'),
                (10, 11, '118.179.32.129', 'অজ্ঞাত', 'সুগন্ধা পাম্প থেকে সীমান্ত পাম্প পযর্ন্ত লাইন।', NULL, '2026-04-04 00:31:20'),
                (11, 13, '202.52.40.239', 'অজ্ঞাত', 'Kono vir nai', NULL, '2026-05-15 02:38:55');
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
