<?php

$host = 'localhost';
$username = 'root';
$password = '';

try {
    // ডেটাবেস সার্ভারে কানেক্ট করা হচ্ছে (কোনো নির্দিষ্ট ডেটাবেস ছাড়া)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ১. ডেটাবেস তৈরি করা হচ্ছে (যদি আগে থেকে না থাকে)
    $sqlDB = "CREATE DATABASE IF NOT EXISTS fuel_update CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sqlDB);
    echo "✅ ডেটাবেস 'fuel_update' সফলভাবে তৈরি হয়েছে বা আগে থেকেই আছে।<br>";

    // ২. ডেটাবেস সিলেক্ট করা হচ্ছে
    $pdo->exec("USE fuel_update");

    // ৩. pumps টেবিল তৈরি করা হচ্ছে
    $sqlTable = "CREATE TABLE IF NOT EXISTS pumps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address VARCHAR(255) NOT NULL,
        has_fuel TINYINT(1) DEFAULT 0,
        petrol TINYINT(1) DEFAULT 0,
        octane TINYINT(1) DEFAULT 0,
        diesel TINYINT(1) DEFAULT 0,
        fuel_limit VARCHAR(100) NULL,
        likes INT DEFAULT 0,
        dislikes INT DEFAULT 0,
        map_link VARCHAR(255) NULL,
        phone VARCHAR(20) NULL,
        image_path VARCHAR(255) NULL,
        added_by VARCHAR(255) NULL,
        report_count INT DEFAULT 0,
        view_count INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlTable);
    echo "✅ টেবিল 'pumps' সফলভাবে তৈরি হয়েছে।<br>";

    // ৪. settings টেবিল তৈরি করা হচ্ছে
    $sqlSettings = "CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY DEFAULT 1,
        site_name VARCHAR(255) DEFAULT 'তেল দিচ্ছে?',
        site_desc VARCHAR(255) DEFAULT 'ঈশ্বরদী জ্বালানি তথ্য সেবা',
        contact_email VARCHAR(255) DEFAULT 'hello@rakibs.com.bd',
        copyright_year VARCHAR(10) DEFAULT '২০২৬',
        creator_name VARCHAR(255) DEFAULT 'রাকিবুল ইসলাম',
        facebook_link VARCHAR(255) DEFAULT '',
        promo_text TEXT NULL,
        promo_link VARCHAR(255) NULL,
        ip_restriction TINYINT(1) DEFAULT 1,
        daily_vote_limit INT DEFAULT 5,
        daily_comment_limit INT DEFAULT 5,
        total_visits INT DEFAULT 0
    )";
    $pdo->exec($sqlSettings);
    $pdo->exec("INSERT IGNORE INTO settings (id) VALUES (1)");
    echo "✅ টেবিল 'settings' সফলভাবে তৈরি হয়েছে।<br>";

    // ৫. comments টেবিল তৈরি করা হচ্ছে
    $sqlComments = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pump_id INT NOT NULL,
        author VARCHAR(255) DEFAULT 'অজ্ঞাত',
        comment_text TEXT NOT NULL,
        image_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pump_id) REFERENCES pumps(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlComments);
    echo "✅ টেবিল 'comments' সফলভাবে তৈরি হয়েছে।<br>";

    // ৬. votes ট্র্যাকিং টেবিল তৈরি করা হচ্ছে
    $sqlVotes = "CREATE TABLE IF NOT EXISTS pump_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pump_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        vote_type VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pump_id) REFERENCES pumps(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlVotes);
    echo "✅ টেবিল 'pump_votes' সফলভাবে তৈরি হয়েছে।<br>";

    // পুরানো ডাটাবেস থাকলে নতুন কলামগুলো আপডেট করার জন্য
    $new_columns = [
        "map_link VARCHAR(255) NULL AFTER diesel",
        "phone VARCHAR(20) NULL AFTER map_link",
        "image_path VARCHAR(255) NULL AFTER phone",
        "added_by VARCHAR(255) NULL AFTER image_path",
        "report_count INT DEFAULT 0 AFTER added_by",
        "fuel_limit VARCHAR(100) NULL AFTER diesel",
        "likes INT DEFAULT 0 AFTER fuel_limit",
        "dislikes INT DEFAULT 0 AFTER likes"
    ];
    foreach($new_columns as $col) {
        try { $pdo->exec("ALTER TABLE pumps ADD COLUMN $col"); } catch (PDOException $e) {}
    }
    try { $pdo->exec("ALTER TABLE comments ADD COLUMN ip_address VARCHAR(45) NULL AFTER pump_id"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE settings ADD COLUMN ip_restriction TINYINT(1) DEFAULT 1 AFTER id"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE settings ADD COLUMN daily_vote_limit INT DEFAULT 5 AFTER ip_restriction"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE settings ADD COLUMN daily_comment_limit INT DEFAULT 5 AFTER daily_vote_limit"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE comments ADD COLUMN image_path VARCHAR(255) NULL AFTER comment_text"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE pumps ADD COLUMN view_count INT DEFAULT 0 AFTER report_count"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE settings ADD COLUMN total_visits INT DEFAULT 0 AFTER copyright_year"); } catch (PDOException $e) {}
    echo "✅ ডাটাবেসে নতুন কলামগুলো চেক/যুক্ত করা হয়েছে।<br>";

    echo "<h3>🎉 সেটআপ সম্পন্ন হয়েছে! এখন আপনি <a href='./'>মূল সাইটে</a> যেতে পারেন।</h3>";

} catch (PDOException $e) {
    die("❌ সেটআপ ব্যর্থ হয়েছে: " . $e->getMessage());
}
?>