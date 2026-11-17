<?php
session_start();

// যদি আগেই db.php তৈরি হয়ে থাকে, তবে হোমপেজে পাঠিয়ে দিবে
if (file_exists('db.php')) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');
    $admin_pass = trim($_POST['admin_pass'] ?? 'admin');

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ডেটাবেসের সকল টেবিল স্বয়ংক্রিয়ভাবে তৈরি করা
        $sql = "
        CREATE TABLE IF NOT EXISTS pumps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            address VARCHAR(255) NOT NULL,
            has_fuel TINYINT(1) DEFAULT 1,
            petrol TINYINT(1) DEFAULT 0,
            octane TINYINT(1) DEFAULT 0,
            diesel TINYINT(1) DEFAULT 0,
            fuel_limit VARCHAR(100) DEFAULT NULL,
            map_link TEXT DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            image_path VARCHAR(255) DEFAULT NULL,
            view_count INT DEFAULT 0,
            added_by VARCHAR(100) DEFAULT NULL,
            likes INT DEFAULT 0,
            dislikes INT DEFAULT 0,
            report_count INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pump_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            author VARCHAR(100) DEFAULT 'অজ্ঞাত',
            comment_text TEXT,
            image_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS pump_votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pump_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            vote_type ENUM('likes', 'dislikes') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_restriction TINYINT(1) DEFAULT 1,
            daily_vote_limit INT DEFAULT 5,
            daily_comment_limit INT DEFAULT 5,
            site_name VARCHAR(100) DEFAULT 'তেল দিচ্ছে?',
            site_desc VARCHAR(255) DEFAULT 'চুয়াডাঙ্গা জ্বালানি তথ্য সেবা',
            contact_email VARCHAR(100) DEFAULT '',
            copyright_year VARCHAR(20) DEFAULT '২০২৬',
            total_visits INT DEFAULT 0,
            creator_name VARCHAR(100) DEFAULT 'মুজাহিদুল ইসলাম',
            facebook_link VARCHAR(255) DEFAULT '',
            promo_text VARCHAR(255) DEFAULT '',
            promo_link VARCHAR(255) DEFAULT ''
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        INSERT IGNORE INTO settings (id, site_name) VALUES (1, 'তেল দিচ্ছে?');
        ";
        $pdo->exec($sql);

        // স্বয়ংক্রিয়ভাবে db.php তৈরি করা
        $db_content = "<?php\n\$host = '$db_host';\n\$dbname = '$db_name';\n\$username = '$db_user';\n\$password = '$db_pass';\n\$admin_password = '$admin_pass';\n\ntry {\n    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);\n    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n} catch (PDOException \$e) {\n    die(\"Database connection failed: \" . \$e->getMessage());\n}\n?>";
        
        if (file_put_contents('db.php', $db_content) !== false) {
            $success = true;
        } else {
            $error = "db.php ফাইল তৈরি করা সম্ভব হয়নি। অনুগ্রহ করে আপনার ফোল্ডারে ফাইল লেখার পারমিশন দিন।";
        }
        
    } catch (PDOException $e) {
        $error = "ডেটাবেস কানেকশনে সমস্যা: ডেটাবেস নেম, ইউজারনেম বা পাসওয়ার্ড ভুল। (" . $e->getMessage() . ")";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সিস্টেম ইন্সটলেশন - তেল দিচ্ছে?</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .install-container { max-width: 500px; margin: 60px auto; padding: 40px 30px; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-hover); border: 1px solid var(--border); }
        .install-header { text-align: center; margin-bottom: 30px; }
        .install-header h1 { font-size: 28px; color: var(--text-main); font-weight: 700; margin-bottom: 8px; letter-spacing: -0.5px; }
        .install-header p { color: var(--text-muted); font-size: 15px; }
        .install-success { text-align: center; padding: 20px 0; }
        .install-success-icon { font-size: 64px; display: block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="install-container">
        <?php if ($success): ?>
            <div class="install-success">
                <span class="install-success-icon">✅</span>
                <h2 style="color: var(--text-main); margin-bottom: 15px; font-size: 24px;">ইন্সটলেশন সফল হয়েছে!</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px; line-height: 1.6;">আপনার ওয়েবসাইট এবং ডেটাবেস এখন ব্যবহারের জন্য সম্পূর্ণ প্রস্তুত।</p>
                <a href="index.php" class="btn-submit" style="display: inline-block; text-decoration: none; padding: 14px 30px; border-radius: 100px;">হোমপেজে যান</a>
            </div>
        <?php else: ?>
            <div class="install-header">
                <h1>⚙️ সিস্টেম ইন্সটলেশন</h1>
                <p>অনুগ্রহ করে আপনার হোস্টিং এর ডেটাবেসের তথ্য দিন</p>
            </div>
            
            <?php if ($error): ?>
                <div style="background: var(--danger-bg); color: var(--danger); padding: 14px 16px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 600; border: 1px solid #fecdd3; font-size: 14px; line-height: 1.5;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>ডেটাবেস হোস্ট (সাধারণত localhost হয়)</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required placeholder="localhost">
                </div>
                <div class="form-group">
                    <label>ডেটাবেস নাম (Database Name)</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required placeholder="আপনার ডেটাবেসের নাম">
                </div>
                <div class="form-group">
                    <label>ডেটাবেস ইউজারনেম (Username)</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required placeholder="আপনার ডেটাবেসের ইউজারনেম">
                </div>
                <div class="form-group">
                    <label>ডেটাবেস পাসওয়ার্ড (Password)</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>" style="width: 100%; padding: 14px 18px; border: 2px solid var(--border); border-radius: var(--radius-md); font-size: 16px; font-family: inherit; background-color: #f8fafc; transition: 0.2s;" placeholder="ডেটাবেসের পাসওয়ার্ড দিন">
                </div>
                <div class="form-group">
                    <label>অ্যাডমিন প্যানেল পাসওয়ার্ড <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="admin_pass" value="<?= htmlspecialchars($_POST['admin_pass'] ?? '') ?>" required style="width: 100%; padding: 14px 18px; border: 2px solid var(--border); border-radius: var(--radius-md); font-size: 16px; font-family: inherit; background-color: #f8fafc; transition: 0.2s;" placeholder="অ্যাডমিন লগইনের জন্য পাসওয়ার্ড সেট করুন">
                </div>
                <button type="submit" class="btn-submit" style="width: 100%; padding: 16px; font-size: 18px; margin-top: 10px;">ইন্সটল শুরু করুন</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>