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
        $sqliteFile = $sqliteDir . '/fuel_chuadanga_v3.sqlite';

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

        // Verify if full Chuadanga dataset exists; if not, populate immediately
        $checkRealPumps = $pdo->query("SELECT COUNT(*) FROM pumps WHERE name = 'Highway Filling Station'")->fetchColumn();
        if ($checkRealPumps == 0) {
            $pdo->exec("DELETE FROM pumps; DELETE FROM settings; DELETE FROM comments; DELETE FROM pump_votes;");

            // Seed settings
            $pdo->exec("
                INSERT INTO settings (id, ip_restriction, daily_vote_limit, daily_comment_limit, site_name, site_desc, contact_email, copyright_year, creator_name, facebook_link, promo_text, promo_link, total_visits)
                VALUES (1, 1, 2, 5, 'তেল দিচ্ছে?', 'চুয়াডাঙ্গা জ্বালানি তথ্য সেবা', '', '২০২৬', 'মুজাহিদুল ইসলাম', '', 'Powered By CHUADANGA-বাংলাদেশের প্রথম রাজধানী', 'https://www.facebook.com/groups/cdbpr', 142);
            ");

            // Seed all 16 pumps
            $pdo->exec("
                INSERT INTO pumps (id, name, address, has_fuel, petrol, octane, diesel, fuel_limit, map_link, phone, image_path, added_by, likes, dislikes, report_count, last_updated) VALUES
                (1, 'Highway Filling Station', 'Near Dingedah bazar, Jhenaidah - Chuadanga Road, Shankarchandra', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/MP2W5Ktm5QRrfgam8', '01711-398668', 'uploads/pump_1.webp', NULL, 7, 2, 0, '2026-04-02 15:43:01'),
                (3, 'মোজাম্মেল হক পেট্রল পাম্প', 'Chuadanga - Jhenaidah Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/Yff5SnSerk27JLXA7', NULL, 'uploads/pump_3.webp', NULL, 8, 2, 0, '2026-04-04 00:31:35'),
                (4, 'সীমান্ত পেট্রোল পাম্প', 'Doulatdiar, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/RvzqHyjgAaNENJrM8', NULL, 'uploads/pump_4.webp', NULL, 7, 1, 0, '2026-04-02 01:29:46'),
                (5, 'চুয়াডাঙ্গা ফিলিং স্টেশন', 'Chuadanga - Alamdanga Rd, Chuadanga 7200', 1, 1, 1, 1, 'Full', 'https://maps.app.goo.gl/cXS4YfpPKRzUYerUA', NULL, 'uploads/pump_5.webp', NULL, 4, 1, 0, '2026-10-24 15:44:38'),
                (6, 'মনিরুল পেট্রল পাম্প & এলপিজি স্টেশন', 'Chuadanga - Jhenaidah Hwy, Sarajgonj', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/bRCm4QPQC1vpzL1H9', '01884456127', 'uploads/pump_6.webp', NULL, 1, 0, 0, '2026-04-08 11:35:39'),
                (7, 'মামুন ফিলিং স্টেশন', 'Chuadanga - Jhenaidah Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/QttYMyxR1h4hAPvH8', NULL, 'uploads/pump_7.webp', NULL, 4, 0, 0, '2026-04-05 09:24:46'),
                (8, 'মেসার্স জোয়ার্দার পেট্রোলিয়াম', 'Chuadanga - Jhenaidah Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/vC3pLKDGVNdkTqW56', NULL, 'uploads/pump_8.webp', NULL, 8, 6, 0, '2026-04-11 06:13:40'),
                (9, 'লালন শাহ ফিলিং স্টেশনের', 'Chuadanga - Jhenaidah Hwy, Sarajgonj', 0, 1, 0, 0, NULL, 'https://maps.app.goo.gl/UXLM9NCfdxbpi5zP8', NULL, 'uploads/pump_9.webp', NULL, 0, 0, 0, '2026-10-25 17:14:43'),
                (10, 'ইমরান ফিলিং স্টেশন', 'Jessore-Chuadanga Hwy, Chuadanga', 0, 0, 0, 0, NULL, 'https://maps.app.goo.gl/mLTE57dYxcpCBzY5A', '01760963535', 'uploads/pump_10.webp', NULL, 1, 0, 0, '2026-03-31 02:18:51'),
                (11, 'Shugondha Filling Station', 'Chuadanga - Meherpur Hwy, Alukdia', 1, 1, 0, 1, NULL, 'https://maps.app.goo.gl/ZW9j1YaWarNRJ6KW8', '01912314443', 'uploads/pump_11.webp', NULL, 3, 0, 0, '2026-04-10 04:29:42'),
                (12, 'বিশ্বাস ফি‌লিং স্টীশন', 'আ‌লুক‌দিয়া বাজার, চুয়াডাঙ্গা টু মে‌হেরপুর রোড', 0, 0, 0, 0, NULL, NULL, NULL, 'uploads/pump_12.webp', 'ismail', 2, 1, 1, '2026-04-04 05:33:22'),
                (13, 'হক ফিলিং স্টেশন', 'আলমডাঙ্গা', 1, 1, 1, 1, 'Unlimited', 'https://maps.app.goo.gl/2LGnhumXjT2kDg548', NULL, 'uploads/pump_13.webp', 'Sheikh Al Mamun', 4, 4, 0, '2026-05-15 02:38:55'),
                (14, 'উত্তরা ফিলিং স্টেশন', 'আলমডাঙ্গা', 1, 1, 1, 0, '500', 'https://maps.app.goo.gl/Ei7LKSeMyoVsBUTu5', NULL, 'uploads/pump_14.webp', 'Sheikh Al Mamun', 5, 0, 0, '2026-04-12 02:38:15'),
                (15, 'জীবননগর ফিলিং স্টেশন', 'জীবননগর', 0, 0, 0, 0, '500', NULL, NULL, 'uploads/pump_15.webp', 'মোঃ শামীম হোছাইন', 1, 1, 0, '2026-04-01 07:24:03'),
                (16, 'দর্শনা ফিলিং স্টেশন', 'দর্শনা', 0, 0, 0, 0, '৩০০', NULL, NULL, 'uploads/pump_16.webp', NULL, 1, 0, 0, '2026-04-01 07:24:53'),
                (18, 'এনামুল হক ফিলিং স্টেশন', 'মুন্সিগঞ্জ পশু হাট, আলমডাঙ্গা, চুয়াডাঙ্গা', 0, 0, 0, 0, '500', 'https://maps.app.goo.gl/EU6n6Yb5bJHxbVJo6', NULL, 'uploads/pump_18.webp', NULL, 0, 0, 0, '2026-04-02 01:49:19');
            ");

            // Seed real comments
            $pdo->exec("
                INSERT INTO comments (id, pump_id, ip_address, author, comment_text, image_path, created_at) VALUES
                (1, 8, '162.12.213.2', 'অজ্ঞাত', 'বুধবার তেল দেবে', NULL, '2026-03-30 17:37:40'),
                (2, 13, '37.111.243.196', 'অজ্ঞাত', 'সকাল ৮ টা ফুয়েল দিবে।', NULL, '2026-03-31 18:57:19'),
                (3, 5, '37.111.244.117', 'অজ্ঞাত', 'কম আজকে একটু', 'uploads/comment_3.webp', '2026-04-02 00:46:59'),
                (4, 7, '37.111.242.223', 'অজ্ঞাত', 'আগের থেকে কম', 'uploads/comment_4.webp', '2026-04-02 06:26:11'),
                (5, 11, '162.12.213.5', 'অজ্ঞাত', 'সকাল ৭টা থেকে এখন পর্যন্ত দিচ্ছে', NULL, '2026-04-02 06:49:37'),
                (6, 3, '103.89.240.45', 'অজ্ঞাত', '‌তেল আ‌ছে', NULL, '2026-04-03 00:29:05'),
                (7, 8, '2400:c600:5264:dd7c::1', 'অজ্ঞাত', 'বাস টার্মিনাল পার হয়ে মামুন ফিলিং স্টেশন পর্যন্ত চলে গেছে। পাম্পে লাইন মেইনটেন্যান্স করছে না বিধায় তেল দিতে দেরি করছে।', 'uploads/comment_7.webp', '2026-04-03 01:04:05'),
                (8, 3, '2400:c600:5264:dd7c::1', 'অজ্ঞাত', 'লাইন কালুর হোটেল পর্যন্ত চলে গেছে।', 'uploads/comment_8.webp', '2026-04-03 01:06:33'),
                (9, 14, '103.31.179.67', 'অজ্ঞাত', '৫০০+ গাড়ির সিরিয়াল', NULL, '2026-04-03 04:34:20'),
                (10, 11, '118.179.32.129', 'অজ্ঞাত', 'সুগন্ধা পাম্প থেকে সীমান্ত পাম্প পযর্ন্ত লাইন।', NULL, '2026-04-04 00:31:20'),
                (11, 13, '202.52.40.239', 'অজ্ঞাত', 'Kono vir nai', NULL, '2026-05-15 02:38:55');
            ");

            // Seed real votes
            $pdo->exec("
                INSERT INTO pump_votes (id, pump_id, ip_address, vote_type, created_at) VALUES
                (1, 2, '162.12.213.0', 'likes', '2026-03-29 17:53:33'),
                (2, 1, '103.149.57.212', 'likes', '2026-03-30 14:16:18'),
                (4, 1, '103.149.57.213', 'likes', '2026-03-30 17:46:57'),
                (5, 3, '103.149.57.213', 'likes', '2026-03-30 17:47:06'),
                (6, 3, '103.149.57.208', 'likes', '2026-03-31 02:18:49'),
                (7, 10, '103.149.57.208', 'likes', '2026-03-31 02:18:51'),
                (8, 8, '103.149.57.212', 'likes', '2026-03-31 14:38:18'),
                (9, 3, '2401:1900:1095:df0e::1', 'likes', '2026-03-31 16:08:30'),
                (10, 8, '163.227.144.17', 'dislikes', '2026-03-31 16:12:45'),
                (11, 8, '162.12.213.7', 'dislikes', '2026-03-31 16:18:02'),
                (12, 8, '210.87.69.92', 'dislikes', '2026-03-31 16:22:52'),
                (13, 1, '162.12.213.0', 'dislikes', '2026-03-31 16:29:12'),
                (14, 4, '103.149.57.210', 'likes', '2026-03-31 16:35:48'),
                (15, 1, '2401:f40:133d:30:3eb7:eca6:b2db:3288', 'likes', '2026-03-31 16:54:11'),
                (16, 4, '2401:f40:133d:30:3eb7:eca6:b2db:3288', 'dislikes', '2026-03-31 16:54:21'),
                (17, 4, '2400:c600:521d:8d4:144e:b6f4:6f01:5d1a', 'likes', '2026-03-31 17:00:18'),
                (18, 7, '180.149.232.189', 'likes', '2026-03-31 17:04:06'),
                (19, 1, '103.218.26.161', 'likes', '2026-03-31 17:21:50'),
                (20, 4, '37.111.201.29', 'likes', '2026-03-31 17:33:43'),
                (21, 7, '37.111.201.29', 'likes', '2026-03-31 17:34:19'),
                (22, 4, '103.218.26.161', 'likes', '2026-03-31 17:41:37'),
                (23, 8, '163.227.144.18', 'dislikes', '2026-03-31 18:08:31'),
                (24, 4, '118.179.32.129', 'likes', '2026-03-31 18:14:56'),
                (25, 12, '103.139.9.178', 'likes', '2026-03-31 18:18:34'),
                (26, 12, '37.111.243.196', 'likes', '2026-03-31 18:19:25'),
                (27, 1, '114.130.176.62', 'dislikes', '2026-03-31 18:41:34'),
                (28, 8, '37.111.243.196', 'dislikes', '2026-03-31 19:02:41'),
                (29, 8, '103.167.15.220', 'dislikes', '2026-03-31 19:19:53'),
                (30, 4, '103.172.139.198', 'likes', '2026-03-31 21:10:33'),
                (31, 4, '103.236.107.148', 'likes', '2026-03-31 23:03:47'),
                (32, 13, '118.179.156.177', 'dislikes', '2026-04-01 03:05:41'),
                (34, 3, '37.111.242.108', 'likes', '2026-04-01 04:07:55'),
                (35, 8, '114.130.188.148', 'likes', '2026-04-01 05:01:40'),
                (36, 3, '36.255.82.240', 'likes', '2026-04-01 06:21:41'),
                (37, 8, '36.255.82.240', 'likes', '2026-04-01 06:21:57'),
                (38, 15, '157.20.148.7', 'likes', '2026-04-01 06:29:25'),
                (39, 3, '118.179.123.33', 'likes', '2026-04-01 06:30:03'),
                (40, 3, '103.155.118.58', 'likes', '2026-04-01 06:37:46'),
                (41, 15, '210.87.69.96', 'dislikes', '2026-04-01 07:20:53'),
                (42, 16, '210.87.69.96', 'likes', '2026-04-01 07:24:36'),
                (43, 13, '37.111.245.79', 'likes', '2026-04-01 07:27:46'),
                (45, 13, '103.126.23.254', 'dislikes', '2026-04-01 09:19:20'),
                (46, 13, '103.149.57.210', 'likes', '2026-04-01 13:58:10'),
                (47, 3, '103.149.57.210', 'likes', '2026-04-01 13:58:12'),
                (48, 3, '103.218.26.161', 'dislikes', '2026-04-01 15:01:16'),
                (49, 3, '202.86.219.140', 'dislikes', '2026-04-01 15:05:53'),
                (50, 13, '103.101.197.42', 'dislikes', '2026-04-01 16:02:42'),
                (51, 13, '103.218.26.161', 'dislikes', '2026-04-01 18:30:47'),
                (52, 17, '37.111.244.117', 'likes', '2026-04-02 00:49:28'),
                (53, 5, '37.111.244.117', 'likes', '2026-04-02 00:50:03'),
                (54, 14, '103.234.203.137', 'likes', '2026-04-02 01:45:48'),
                (55, 12, '103.234.203.136', 'dislikes', '2026-04-02 05:42:32'),
                (56, 7, '118.179.123.33', 'likes', '2026-04-02 08:12:12'),
                (57, 1, '2404:1c40:db:76fb:88f9:c5ff:fe22:2fc9', 'likes', '2026-04-02 11:06:28'),
                (58, 17, '202.86.220.202', 'likes', '2026-04-02 12:56:29'),
                (59, 5, '202.86.220.202', 'likes', '2026-04-02 12:56:30'),
                (60, 17, '37.111.239.184', 'dislikes', '2026-04-02 14:13:35'),
                (79, 11, '103.149.57.209', 'likes', '2026-04-02 14:48:43'),
                (80, 1, '103.149.57.209', 'likes', '2026-04-02 14:48:44'),
                (81, 11, '103.149.57.208', 'likes', '2026-04-02 15:42:36'),
                (82, 1, '103.149.57.208', 'likes', '2026-04-02 15:43:01'),
                (83, 13, '103.31.179.64', 'likes', '2026-04-02 17:44:59'),
                (84, 14, '103.31.179.64', 'likes', '2026-04-02 17:45:04'),
                (86, 8, '2404:1c40:f8:6296:1:0:dafd:ad6', 'likes', '2026-04-03 01:41:23'),
                (90, 11, '103.149.57.214', 'likes', '2026-04-03 04:27:49'),
                (91, 8, '103.149.57.214', 'likes', '2026-04-03 04:27:52'),
                (92, 8, '118.179.32.129', 'likes', '2026-04-03 04:36:58'),
                (94, 8, '103.149.57.213', 'likes', '2026-04-03 04:54:34'),
                (95, 5, '103.149.57.213', 'likes', '2026-04-03 04:54:44'),
                (96, 8, '103.31.179.65', 'likes', '2026-04-03 05:47:08'),
                (97, 5, '202.86.216.33', 'dislikes', '2026-04-03 14:47:57'),
                (98, 7, '103.245.204.177', 'likes', '2026-04-05 09:24:46'),
                (101, 6, '103.245.204.177', 'likes', '2026-04-05 09:26:20'),
                (104, 14, '103.234.202.75', 'likes', '2026-04-06 05:10:42'),
                (105, 14, '114.130.176.62', 'likes', '2026-04-07 16:39:03'),
                (107, 14, '37.111.242.14', 'likes', '2026-04-12 02:38:15'),
                (108, 13, '37.111.242.14', 'likes', '2026-04-12 02:38:16'),
                (109, 5, '103.11.197.14', 'likes', '2026-10-24 15:44:38');
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
