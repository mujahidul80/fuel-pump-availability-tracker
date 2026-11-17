<?php
session_start();

// ইনস্টল করা না থাকলে ইনস্টল পেজে নিয়ে যাবে
$db_path = file_exists(__DIR__ . '/../db.php') ? __DIR__ . '/../db.php' : (file_exists('db.php') ? 'db.php' : '../db.php');
if (!file_exists($db_path)) {
    header("Location: ../install.php");
    exit;
}

require_once $db_path;

// সময়ের হিসাব (Time Ago) বের করার ফাংশন
if (!function_exists('time_elapsed_string')) {
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'বছর', 'm' => 'মাস', 'w' => 'সপ্তাহ', 'd' => 'দিন', 'h' => 'ঘণ্টা', 'i' => 'মিনিট', 's' => 'সেকেন্ড');
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v; } else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' আগে' : 'এইমাত্র';
}
}

// লগইন পাসওয়ার্ড এখন db.php থেকে স্বয়ংক্রিয়ভাবে আসবে
// $admin_password = '...'; 

// লগইন লজিক
if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "পাসওয়ার্ড ভুল হয়েছে!";
    }
}

// লগআউট লজিক
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ./");
    exit;
}

$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// অ্যাডমিন অ্যাকশন (Toggle On/Off এবং Delete)
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete') {
            $id = (int)$_POST['pump_id'];
            
            // পাম্প ডিলিট করার সময় তার ছবিটিও সার্ভার থেকে মুছে ফেলা
            $stmt = $pdo->prepare("SELECT image_path FROM pumps WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetchColumn();
            if ($img && file_exists("../" . $img)) {
                unlink("../" . $img);
            }
            
            // পাম্প ডিলিট করার সময় মতামতের ছবিগুলোও মুছে ফেলা
            $cStmt = $pdo->prepare("SELECT image_path FROM comments WHERE pump_id = ?");
            $cStmt->execute([$id]);
            while ($cImg = $cStmt->fetchColumn()) {
                if ($cImg && file_exists("../" . $cImg)) {
                    unlink("../" . $cImg);
                }
            }
            $pdo->prepare("DELETE FROM comments WHERE pump_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM pumps WHERE id = ?")->execute([$id]);
        } elseif ($_POST['action'] === 'delete_image') {
            $id = (int)$_POST['pump_id'];
            $stmt = $pdo->prepare("SELECT image_path FROM pumps WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetchColumn();
            if ($img && file_exists("../" . $img)) {
                unlink("../" . $img);
            }
            $pdo->prepare("UPDATE pumps SET image_path = NULL WHERE id = ?")->execute([$id]);
        } elseif ($_POST['action'] === 'toggle') {
            $id = (int)$_POST['pump_id'];
            $field = $_POST['field']; // has_fuel, petrol, octane, diesel
            
            // সিকিউরিটি চেক: শুধুমাত্র নির্দিষ্ট ফিল্ডগুলোই আপডেট করা যাবে
            $allowed_fields = ['has_fuel', 'petrol', 'octane', 'diesel'];
            if (in_array($field, $allowed_fields)) {
                // বর্তমান মান চেক করে উল্টো মান সেট করা (0 থাকলে 1, 1 থাকলে 0)
                $stmt = $pdo->prepare("SELECT $field FROM pumps WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetchColumn();
                $new_val = $current ? 0 : 1;
                
                $pdo->prepare("UPDATE pumps SET $field = ? WHERE id = ?")->execute([$new_val, $id]);
            }
        } elseif ($_POST['action'] === 'update_settings') {
            $ip_restriction = isset($_POST['ip_restriction']) ? 1 : 0;
            $daily_vote_limit = max(1, (int)$_POST['daily_vote_limit']);
            $daily_comment_limit = max(1, (int)$_POST['daily_comment_limit']);
            $stmt = $pdo->prepare("UPDATE settings SET ip_restriction = ?, daily_vote_limit = ?, daily_comment_limit = ?, site_name = ?, site_desc = ?, contact_email = ?, copyright_year = ?, creator_name = ?, facebook_link = ?, promo_text = ?, promo_link = ? WHERE id = 1");
            $stmt->execute([
                $ip_restriction, $daily_vote_limit, $daily_comment_limit, $_POST['site_name'], $_POST['site_desc'], $_POST['contact_email'], $_POST['copyright_year'], $_POST['creator_name'], $_POST['facebook_link'], $_POST['promo_text'], $_POST['promo_link']
            ]);
        } elseif ($_POST['action'] === 'reset_report') {
            $id = (int)$_POST['pump_id'];
            $pdo->prepare("UPDATE pumps SET report_count = 0 WHERE id = ?")->execute([$id]);
        } elseif ($_POST['action'] === 'update_votes') {
            $id = (int)$_POST['pump_id'];
            $likes = max(0, (int)$_POST['likes']);
            $dislikes = max(0, (int)$_POST['dislikes']);
            $pdo->prepare("UPDATE pumps SET likes = ?, dislikes = ? WHERE id = ?")->execute([$likes, $dislikes, $id]);
        } elseif ($_POST['action'] === 'delete_comment') {
            $comment_id = (int)$_POST['comment_id'];
            $stmt = $pdo->prepare("SELECT image_path FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $img = $stmt->fetchColumn();
            if ($img && file_exists("../" . $img)) {
                unlink("../" . $img);
            }
            $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$comment_id]);
        } elseif ($_POST['action'] === 'edit_comment') {
            $comment_id = (int)$_POST['comment_id'];
            $comment_text = trim($_POST['comment_text']);
            if (!empty($comment_text)) {
                $pdo->prepare("UPDATE comments SET comment_text = ? WHERE id = ?")->execute([$comment_text, $comment_id]);
            }
        } elseif ($_POST['action'] === 'edit_pump_info') {
            $id = (int)$_POST['pump_id'];
            $name = trim($_POST['name']);
            $address = trim($_POST['address']);
            $phone = trim($_POST['phone'] ?? '');
            $map_link = trim($_POST['map_link'] ?? '');
            
            if (!empty($name) && !empty($address)) {
                $pdo->prepare("UPDATE pumps SET name = ?, address = ?, phone = ?, map_link = ? WHERE id = ?")->execute([$name, $address, $phone, $map_link, $id]);
            }
        } elseif ($_POST['action'] === 'change_pump_image') {
            $id = (int)$_POST['pump_id'];
            if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['new_image']['tmp_name'];
                $mime = mime_content_type($tmpName);
                if (in_array($mime, ['image/jpeg', 'image/png'])) {
                    $uploadDir = '../uploads/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                    $converted = false;
                    $image_path = null;
                    if (function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
                        $targetPath = $uploadDir . time() . '_admin_' . uniqid() . '.webp';
                        $img = ($mime === 'image/jpeg') ? @imagecreatefromjpeg($tmpName) : @imagecreatefrompng($tmpName);
                        if ($img) {
                            if ($mime === 'image/png') { imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true); }
                            if (imagewebp($img, $targetPath, 80)) { $image_path = 'uploads/' . basename($targetPath); $converted = true; }
                            imagedestroy($img);
                        }
                    }
                    if (!$converted) {
                        $ext = ($mime === 'image/jpeg') ? '.jpg' : '.png';
                        $targetPath = $uploadDir . time() . '_admin_' . uniqid() . $ext;
                        if (move_uploaded_file($tmpName, $targetPath)) { $image_path = 'uploads/' . basename($targetPath); }
                    }
                    if ($image_path) {
                        // পুরনো ছবি ডিলিট করা
                        $stmt = $pdo->prepare("SELECT image_path FROM pumps WHERE id = ?");
                        $stmt->execute([$id]);
                        $old_img = $stmt->fetchColumn();
                        if ($old_img && file_exists("../" . $old_img)) { unlink("../" . $old_img); }
                        // নতুন ছবি সেভ করা
                        $pdo->prepare("UPDATE pumps SET image_path = ? WHERE id = ?")->execute([$image_path, $id]);
                    }
                }
            }
        } elseif ($_POST['action'] === 'system_update') {
            if (isset($_FILES['update_zip']) && $_FILES['update_zip']['error'] === UPLOAD_ERR_OK) {
                $zip = new ZipArchive;
                if ($zip->open($_FILES['update_zip']['tmp_name']) === TRUE) {
                    $extract_path = '../';
                    for($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        // db.php এবং uploads ফোল্ডার ওভাররাইট করা বন্ধ রাখা হয়েছে (নিরাপত্তার জন্য)
                        if($filename === 'db.php' || strpos($filename, 'uploads/') === 0) continue;
                        
                        if(substr($filename, -1) === '/') {
                            if(!is_dir($extract_path . $filename)) mkdir($extract_path . $filename, 0777, true);
                        } else {
                            $dir = dirname($extract_path . $filename);
                            if(!is_dir($dir)) mkdir($dir, 0777, true);
                            copy("zip://".$_FILES['update_zip']['tmp_name']."#".$filename, $extract_path . $filename);
                        }
                    }
                    $zip->close();
                    header("Location: ./?msg=updated");
                    exit;
                } else {
                    header("Location: ./?msg=error");
                    exit;
                }
            }
        }
        // রিফ্রেশ বাটন প্রেসের ডাবল সাবমিট এড়াতে রিডাইরেক্ট
        header("Location: ./");
        exit;
    }
}

// সেটিংস ফেচ করা হচ্ছে
$stmtSet = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmtSet->fetch(PDO::FETCH_ASSOC) ?: [];

// লগইন করা থাকলে পাম্পের লিস্ট আনবে
if ($is_logged_in) {
    $stmt = $pdo->query("SELECT * FROM pumps ORDER BY last_updated DESC");
    $pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtComments = $pdo->query("SELECT * FROM comments ORDER BY created_at DESC");
    $all_comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
    $commentsByPump = [];
    foreach($all_comments as $c) {
        $commentsByPump[$c['pump_id']][] = $c;
    }
}

// ডাইনামিক বেস URL ও প্রটোকল ডিটেকশন (HTTPS / Reverse Proxy / Vercel সাপোর্টেড)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $is_https ? "https" : "http";

$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_path = dirname($script_dir);
if ($base_path === '\\' || $base_path === '/' || $base_path === '.') { $base_path = ''; }
$site_root = $base_path;
$site_url = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $base_path;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন প্যানেল - তেল দিচ্ছে?</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $site_root ?>/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style.css?v=<?= time() ?>">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #0ea5e9;
            --success: #10b981;
            --success-bg: #dcfce7;
            --success-text: #15803d;
            --danger: #f43f5e;
            --danger-bg: #ffe4e6;
            --danger-text: #e11d48;
            --warning: #f59e0b;
            --warning-bg: #fef3c7;
            --warning-text: #b45309;
            --bg-color: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 24px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.04);
            --shadow-hover: 0 15px 35px rgba(0,0,0,0.08);
            --font-family: 'Hind Siliguri', system-ui, -apple-system, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-icon {
            font-size: 24px;
            color: var(--primary);
        }
        .navbar h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: var(--text-main);
        }
        .navbar p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-main);
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 15px;
            outline: none;
            box-sizing: border-box;
            background: #fff;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary);
        }
        .admin-container { max-width: 1000px; margin: 30px auto; padding: 0 16px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0; background: var(--surface); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--border); }
        .admin-table th { background-color: #f8fafc; font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; padding: 16px 20px; border-bottom: 1px solid var(--border); text-align: left; }
        .admin-table td { padding: 24px 20px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: top; }
        .admin-table tr:hover { background-color: #f8fafc; }
        .admin-table tr:last-child td { border-bottom: none; }
        
        .toggle-btn { background: var(--surface); border: 1px solid var(--border); cursor: pointer; font-size: 13px; padding: 6px 12px; border-radius: 100px; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 700; font-family: inherit; box-shadow: 0 1px 2px rgba(0,0,0,0.05); white-space: nowrap; }
        .toggle-btn.on { background-color: var(--success-bg); color: var(--success-text); border-color: #86efac; box-shadow: none; }
        .toggle-btn.off { background-color: var(--danger-bg); color: var(--danger-text); border-color: #fecdd3; }
        .toggle-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .delete-btn { color: var(--danger); background-color: var(--danger-bg); border: 1px solid #fecdd3; padding: 8px 16px; border-radius: 100px; cursor: pointer; font-size: 13px; font-weight: 700; font-family: inherit; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .delete-btn:hover { background-color: var(--danger); color: white; transform: translateY(-1px); box-shadow: 0 4px 6px rgba(225, 29, 72, 0.2); }
        
        .admin-img-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 12px; border: 2px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: block; margin-bottom: 8px; transition: 0.2s; }
        .admin-img-thumb:hover { transform: scale(1.05); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .img-delete-btn { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecdd3; padding: 4px 8px; border-radius: 6px; font-size: 12px; cursor: pointer; display: block; width: 100%; text-align: center; font-weight: 700; transition: 0.2s; }
        .img-delete-btn:hover { background: var(--danger); color: white; }
        
        .report-badge { color: var(--danger); font-weight: 700; background: var(--danger-bg); padding: 6px 12px; border-radius: 100px; font-size: 12px; display: inline-block; margin-bottom: 8px; border: 1px solid #fecdd3; white-space: nowrap; }
        
        .admin-tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 30px; }
        .tab-link { background: none; border: none; padding: 15px 25px; font-size: 16px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.2s; border-bottom: 3px solid transparent; }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .settings-card { background: var(--surface); padding: 30px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 30px; }
        .settings-card h3 { font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .admin-section-title { font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; padding-bottom: 8px; }

        .login-box { max-width: 400px; margin: 100px auto; background: var(--surface); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); }
    </style>
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <span class="nav-icon">⚙️</span>
        <div>
            <h1>অ্যাডমিন প্যানেল</h1>
            <p>সকল পাম্পের নিয়ন্ত্রণ</p>
        </div>
    </div>
    <?php if ($is_logged_in): ?>
        <a href="?logout=1" style="color: var(--danger); font-weight: 600; text-decoration: none; padding: 8px 16px; border: 2px solid var(--danger); border-radius: 100px; transition: 0.2s;">লগআউট</a>
    <?php endif; ?>
</div>

<div class="admin-container">
    <?php if (!$is_logged_in): ?>
        <div class="login-box">
            <h2 style="margin-top:0; margin-bottom: 20px;">অ্যাডমিন লগইন</h2>
            <?php if (isset($error)) echo "<p style='color: var(--danger); background: var(--danger-bg); padding: 12px; border-radius: var(--radius-sm); font-weight: 600;'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>পাসওয়ার্ড</label>
                    <input type="password" name="password" required style="width: 100%; padding: 14px; border: 2px solid var(--border); border-radius: var(--radius-md); font-size: 16px; outline: none; transition: 0.2s;">
                </div>
                <button type="submit" name="login" class="btn-submit" style="width: 100%; padding: 14px; font-size: 16px; margin-top: 10px;">লগইন করুন</button>
            </form>
        </div>
    <?php else: ?>
        <div class="admin-tabs">
            <button class="tab-link active" data-tab="tab-pumps">সকল পাম্প</button>
            <button class="tab-link" data-tab="tab-settings">সাইট সেটিংস</button>
            <button class="tab-link" data-tab="tab-system">সিস্টেম</button>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <p style="background: var(--success-bg); color: var(--success); padding: 16px; border-radius: var(--radius-md); font-weight: 600; border: 1px solid #bbf7d0;">✅ ওয়েবসাইট সফলভাবে আপডেট হয়েছে!</p>
        <?php elseif(isset($_GET['msg']) && $_GET['msg'] === 'error'): ?>
            <p style="background: var(--danger-bg); color: var(--danger); padding: 16px; border-radius: var(--radius-md); font-weight: 600; border: 1px solid #fecdd3;">❌ আপডেট ব্যর্থ হয়েছে! সঠিক জিপ (.zip) ফাইল নির্বাচন করুন।</p>
        <?php endif; ?>

        <div id="tab-pumps" class="tab-content active">
            <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="min-width: 300px;">পাম্পের বিবরণ</th>
                        <th style="min-width: 200px;">বর্তমান অবস্থা</th>
                        <th style="min-width: 280px;">এনগেজমেন্ট ও অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pumps as $pump): ?>
                    <?php $pump_comments = $commentsByPump[$pump['id']] ?? []; ?>
                    <tr>
                        <td>
                            <div style="display: flex; gap: 16px; align-items: flex-start;">
                                <?php if (!empty($pump['image_path'])): ?>
                                    <div style="display: flex; flex-direction: column; gap: 6px; align-items: center;">
                                    <a href="<?= $site_url ?>/<?= htmlspecialchars($pump['image_path']) ?>" target="_blank" style="margin-bottom: 0;">
                                        <img src="<?= $site_url ?>/<?= htmlspecialchars($pump['image_path']) ?>" class="admin-img-thumb" style="margin-bottom: 0;">
                                        </a>
                                        <form method="POST" onsubmit="return confirm('ছবিটি ডিলিট করতে চান?');" style="width: 100%;">
                                            <input type="hidden" name="action" value="delete_image">
                                            <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                            <button type="submit" class="img-delete-btn">ডিলিট</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong style="color: var(--text-main); font-size: 16px; display: block; margin-bottom: 4px; line-height: 1.3;"><?= htmlspecialchars($pump['name']) ?></strong>
                                    <span style="font-size: 13px; color: var(--text-muted); display: block; margin-bottom: 8px;"><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?= htmlspecialchars($pump['address']) ?></span>
                                    <div style="font-size: 12px; color: #94a3b8; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border);">
                                        <i class="fa-regular fa-clock"></i> শেষ আপডেট: <?= time_elapsed_string($pump['last_updated']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <?php foreach (['has_fuel' => 'তেল', 'petrol' => 'পেট্রোল', 'octane' => 'অকটেন', 'diesel' => 'ডিজেল'] as $field => $label): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                        <input type="hidden" name="field" value="<?= $field ?>">
                                        <button type="submit" class="toggle-btn <?= $pump[$field] ? 'on' : 'off' ?>" title="<?= $label ?> আপডেট করুন">
                                            <?= $pump[$field] ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>' ?> <?= $label ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); background: #f8fafc; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-thumbs-up" style="color: var(--success);"></i> <?= $pump['likes'] ?? 0 ?>
                                        <i class="fa-solid fa-thumbs-down" style="color: var(--danger); margin-left: 8px;"></i> <?= $pump['dislikes'] ?? 0 ?>
                                    </div>
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); background: #f8fafc; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-eye" style="color: var(--primary);"></i> <?= $pump['view_count'] ?? 0 ?>
                                    </div>
                                    <?php if (($pump['report_count'] ?? 0) > 0): ?>
                                    <div style="font-size: 13px; font-weight: 600; color: var(--danger); background: var(--danger-bg); padding: 6px 10px; border-radius: 8px; border: 1px solid #fecdd3; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-flag"></i> <?= $pump['report_count'] ?>
                                        <form method="POST" style="display:inline; margin: 0; padding: 0;">
                                            <input type="hidden" name="action" value="reset_report">
                                            <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                            <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 12px; margin-left: 4px;" title="রিপোর্ট মুছুন">✖</button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                    <button type="button" class="btn-cancel" style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border); box-shadow: 0 1px 2px rgba(0,0,0,0.05);" onclick="document.getElementById('comments-<?= $pump['id'] ?>').classList.toggle('active')">
                                        <i class="fa-regular fa-comment-dots"></i> মতামত (<?= count($pump_comments) ?>)
                                    </button>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" class="btn-cancel" style="padding: 8px 14px; font-size: 13px; border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 6px;" onclick="document.getElementById('edit-info-<?= $pump['id'] ?>').style.display = 'table-row';">
                                        <i class="fa-solid fa-pen-to-square"></i> এডিট
                                    </button>
                                    <form method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই পাম্পটি ডিলিট করতে চান?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                        <button type="submit" class="delete-btn" style="padding: 8px 14px; font-size: 13px;">
                                            <i class="fa-solid fa-trash-can"></i> ডিলিট
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr id="edit-info-<?= $pump['id'] ?>" style="display: none;">
                        <td colspan="3" style="background-color: #f8fafc; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
                            <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; display: flex; justify-content: space-between; align-items: center;">
                                <span>📝 '<?= htmlspecialchars($pump['name']) ?>' এর তথ্য এডিট করুন</span>
                                <button type="button" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--text-muted);" onclick="document.getElementById('edit-info-<?= $pump['id'] ?>').style.display = 'none';">✖</button>
                            </h4>
                            
                            <!-- Basic Info Update -->
                            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                                <input type="hidden" name="action" value="edit_pump_info">
                                <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                <div><label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">পাম্পের নাম:</label><input type="text" name="name" value="<?= htmlspecialchars($pump['name']) ?>" required style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px;"></div>
                                <div><label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">ঠিকানা:</label><input type="text" name="address" value="<?= htmlspecialchars($pump['address']) ?>" required style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px;"></div>
                                <div><label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">ফোন নম্বর:</label><input type="tel" name="phone" value="<?= htmlspecialchars($pump['phone'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px;"></div>
                                <div><label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">ম্যাপ লিংক:</label><input type="url" name="map_link" value="<?= htmlspecialchars($pump['map_link'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px;"></div>
                                <div style="grid-column: 1 / -1; display: flex; gap: 10px; margin-top: 5px;">
                                    <button type="submit" style="background: var(--text-main); color: white; border: none; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer;">তথ্য সেভ করুন</button>
                                </div>
                            </form>

                            <!-- Votes Update -->
                            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; padding-top: 15px; border-top: 1px dashed var(--border);">
                                <input type="hidden" name="action" value="update_votes">
                                <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                <div><label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">লাইক সংখ্যা:</label><input type="number" name="likes" value="<?= $pump['likes'] ?? 0 ?>" min="0" required style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px;"></div>
                                <div><label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">ডিসলাইক সংখ্যা:</label><input type="number" name="dislikes" value="<?= $pump['dislikes'] ?? 0 ?>" min="0" required style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px;"></div>
                                <div style="grid-column: 1 / -1; display: flex; gap: 10px; margin-top: 5px;">
                                    <button type="submit" style="background: var(--success); color: white; border: none; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer;">ভোট আপডেট করুন</button>
                                </div>
                            </form>

                            <!-- Image Update -->
                            <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; padding-top: 15px; border-top: 1px dashed var(--border);">
                                <input type="hidden" name="action" value="change_pump_image">
                                <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                <div style="grid-column: 1 / -1;">
                                    <label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 4px;">নতুন ছবি আপলোড করুন:</label>
                                    <input type="file" name="new_image" accept=".jpg, .jpeg, .png" required style="width: 100%; padding: 8px; border: 1px dashed var(--primary); border-radius: 4px; font-size: 14px; background: white; cursor: pointer;">
                                </div>
                                <div style="grid-column: 1 / -1; display: flex; gap: 10px; margin-top: 5px;">
                                    <button type="submit" style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer;">ছবি পরিবর্তন করুন</button>
                                </div>
                            </form>
                        </td>
                    </tr>

                    <tr id="comments-<?= $pump['id'] ?>" class="comments-row">
                            <td colspan="3" style="padding: 0; background: #f8fafc; box-shadow: inset 0 4px 6px rgba(0,0,0,0.05);">
                                <div style="padding: 20px;">
                                    <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--text-main);">💬 এই পাম্পের মতামত সমূহ</h4>
                                    <?php if (count($pump_comments) > 0): ?>
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <?php foreach($pump_comments as $c): ?>
                                                <div class="comment-item" style="background: white; padding: 15px; border-radius: 8px; border: 1px solid var(--border); display: flex; gap: 15px; align-items: flex-start; transition: opacity 0.3s ease;">
                                                    <div style="flex: 1;">
                                                        <strong style="font-size: 14px;"><?= htmlspecialchars($c['author'] ?: 'অজ্ঞাত') ?></strong> <span style="font-size: 12px; color: var(--text-muted);">(<?= date('d M, h:i A', strtotime($c['created_at'])) ?>)</span>
                                                        <form method="POST" style="margin-top: 8px; display: flex; gap: 10px;">
                                                            <input type="hidden" name="action" value="edit_comment">
                                                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                                            <textarea name="comment_text" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 13px; font-family: inherit; resize: vertical; min-height: 40px;"><?= htmlspecialchars($c['comment_text']) ?></textarea>
                                                            <button type="submit" class="btn-submit" style="padding: 8px 16px; font-size: 13px; white-space: nowrap; height: max-content;">সেভ করুন</button>
                                                        </form>
                                                    </div>
                                                    <?php if(!empty($c['image_path'])): ?>
                                                        <div style="flex-shrink: 0; margin-top: 25px;">
                                                        <a href="<?= $site_url ?>/<?= htmlspecialchars($c['image_path']) ?>" target="_blank"><img src="<?= $site_url ?>/<?= htmlspecialchars($c['image_path']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);"></a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <form class="delete-comment-form" method="POST" style="margin-top: 25px;">
                                                        <input type="hidden" name="action" value="delete_comment">
                                                        <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="delete-btn" style="padding: 8px 12px; background: var(--danger-bg); color: var(--danger);">🗑️ ডিলিট</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">কোনো মতামত পাওয়া যায়নি।</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div id="tab-settings" class="tab-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                <div class="settings-card">
                    <div style="background: var(--bg-color); padding: 20px; border-radius: var(--radius-md); text-align: center; margin-bottom: 30px; border: 1px solid var(--border);">
                        <h4 style="margin: 0 0 8px 0; font-size: 16px; color: var(--text-muted); font-weight: 600;">সর্বমোট ওয়েবসাইট ভিজিট</h4>
                        <p style="margin: 0; font-size: 36px; font-weight: 700; color: var(--primary); line-height: 1;"><?= number_format($settings['total_visits'] ?? 0) ?></p>
                    </div>

                    <h3>সাধারণ সেটিংস</h3>
                    <div class="settings-grid">
                        <div class="form-group" style="grid-column: 1 / -1; background: #eff6ff; padding: 16px; border-radius: var(--radius-sm); border: 1px solid #bfdbfe;">
                            <label style="color: var(--primary-dark);">সিকিউরিটি (IP রেস্ট্রিকশন)</label>
                            <label class="toggle-container" style="margin: 0;">
                                <div class="toggle-switch">
                                    <input type="checkbox" name="ip_restriction" <?= ($settings['ip_restriction'] ?? 1) ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span class="toggle-text" style="font-size: 14px; font-weight: normal; color: var(--text-main);">চালু থাকলে একজন ইউজার একটি পাম্পে মাত্র ১ বার লাইক ও কমেন্ট করতে পারবে।</span>
                            </label>
                            <div style="margin-top: 15px; display: flex; gap: 15px;">
                                <div style="flex:1;"><label style="font-size:13px; color:var(--text-muted);">দিনে সর্বোচ্চ ভোট (প্রতি ইউজার)</label><input type="number" name="daily_vote_limit" value="<?= $settings['daily_vote_limit'] ?? 5 ?>" min="1" required></div>
                                <div style="flex:1;"><label style="font-size:13px; color:var(--text-muted);">দিনে সর্বোচ্চ মতামত (প্রতি ইউজার)</label><input type="number" name="daily_comment_limit" value="<?= $settings['daily_comment_limit'] ?? 5 ?>" min="1" required></div>
                            </div>
                        </div>
                        <div class="form-group"><label>সাইটের নাম</label><input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required></div>
                        <div class="form-group"><label>সাইটের বিবরণ</label><input type="text" name="site_desc" value="<?= htmlspecialchars($settings['site_desc'] ?? '') ?>"></div>
                        <div class="form-group"><label>যোগাযোগের ইমেইল</label><input type="text" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>"></div>
                        <div class="form-group"><label>কপিরাইট সাল</label><input type="text" name="copyright_year" value="<?= htmlspecialchars($settings['copyright_year'] ?? '') ?>"></div>
                        <div class="form-group"><label>তৈরি করেছেন (আপনার নাম)</label><input type="text" name="creator_name" value="<?= htmlspecialchars($settings['creator_name'] ?? '') ?>"></div>
                    </div>
                </div>
                <div class="settings-card">
                    <h3>সোশ্যাল ও প্রমোশন</h3>
                    <div class="settings-grid">
                        <div class="form-group"><label>ফেসবুক পেজ/প্রোফাইল লিংক</label><input type="url" name="facebook_link" value="<?= htmlspecialchars($settings['facebook_link'] ?? '') ?>"></div>
                        <div class="form-group"><label>প্রমোশন টেক্সট (যেমন: স্পন্সর পোস্ট)</label><input type="text" name="promo_text" value="<?= htmlspecialchars($settings['promo_text'] ?? '') ?>"></div>
                        <div class="form-group"><label>প্রমোশন লিংক</label><input type="url" name="promo_link" value="<?= htmlspecialchars($settings['promo_link'] ?? '') ?>"></div>
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="margin-top: 10px;">সেটিংস আপডেট করুন</button>
            </form>
        </div>

        <div id="tab-system" class="tab-content">
            <div class="settings-card">
                <h3>🚀 সিস্টেম আপডেট (Zip File)</h3>
                <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.6;">ভবিষ্যতে ওয়েবসাইট আপডেট করার জন্য আপনার নতুন কোডের ফাইলগুলো <strong>.zip</strong> করে এখানে আপলোড করুন। <br><span style="color: var(--danger);">সতর্কতা:</span> সিস্টেম অটোমেটিকভাবে আপনার লাইভ <strong>db.php</strong> এবং <strong>uploads</strong> ফোল্ডার স্কিপ করবে, তাই আপডেটের সময় কোনো ডেটা হারাবে না!</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="system_update">
                    <div class="form-group">
                        <input type="file" name="update_zip" accept=".zip" required style="padding: 14px; border: 2px dashed #cbd5e1; width: 100%; border-radius: var(--radius-md); cursor: pointer; background: #f8fafc; font-weight: 600; color: var(--text-muted);">
                    </div>
                    <button type="submit" class="btn-submit" onclick="return confirm('আপনি কি নিশ্চিত যে ওয়েবসাইটটি আপডেট করতে চান?');">আপডেট শুরু করুন</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const target = link.getAttribute('data-tab');

            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            link.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    // AJAX দিয়ে পেজ রিফ্রেশ ছাড়াই কমেন্ট ডিলিট করার লজিক
    const deleteCommentForms = document.querySelectorAll('.delete-comment-form');
    deleteCommentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('আপনি কি নিশ্চিত যে মতামতটি ডিলিট করবেন?')) {
                return;
            }

            const btn = this.querySelector('.delete-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ হচ্ছে...';
            btn.style.pointerEvents = 'none';

            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(() => {
                const commentItem = this.closest('.comment-item');
                if (commentItem) {
                    commentItem.style.opacity = '0'; // ফেড আউট অ্যানিমেশন
                    setTimeout(() => { commentItem.remove(); }, 300); // গায়েব করা
                }
            })
            .catch(() => {
                alert('কোনো সমস্যা হয়েছে, আবার চেষ্টা করুন!');
                btn.innerHTML = originalText;
                btn.style.pointerEvents = 'auto';
            });
        });
    });
});
</script>

</body>
</html>