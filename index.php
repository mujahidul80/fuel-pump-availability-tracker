<?php
session_start();

// ইনস্টল করা না থাকলে ইনস্টল পেজে নিয়ে যাবে
if (!file_exists('db.php')) {
    header("Location: install.php");
    exit;
}

require_once 'db.php';

$user_ip = $_SERVER['REMOTE_ADDR'];

// মোট ভিজিটর কাউন্ট (সেশন প্রতি একবার)
if (!isset($_SESSION['site_visited'])) {
    try {
        $pdo->exec("UPDATE settings SET total_visits = total_visits + 1 WHERE id = 1");
    } catch (Exception $e) {}
    $_SESSION['site_visited'] = true;
}

// সময়ের হিসাব (Time Ago) বের করার ফাংশন
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

// সেটিংস ফেচ করা হচ্ছে (ফুটার ও অন্যান্য কাজের জন্য)
$stmtSet = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmtSet->fetch(PDO::FETCH_ASSOC) ?: [];
$ip_restricted = $settings['ip_restriction'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pump') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $has_fuel = isset($_POST['has_fuel']) ? 1 : 0;
    $petrol = isset($_POST['petrol']) ? 1 : 0;
    $octane = isset($_POST['octane']) ? 1 : 0;
    $diesel = isset($_POST['diesel']) ? 1 : 0;
    $fuel_limit = !empty($_POST['fuel_limit']) ? trim($_POST['fuel_limit']) : null;

    $map_link = !empty($_POST['map_link']) ? trim($_POST['map_link']) : null;
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $added_by = !empty($_POST['added_by']) ? trim($_POST['added_by']) : null;
    $image_path = null;

    // ছবি আপলোড এবং WebP কনভার্ট লজিক
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $mime = mime_content_type($tmpName);
        
        if (!in_array($mime, ['image/jpeg', 'image/png'])) {
            $_SESSION['toast_msg'] = "❌ শুধুমাত্র JPG বা PNG ছবি আপলোড করা যাবে!";
            header("Location: ./");
            exit;
        }
        
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        
        $converted = false;
        if (function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
            $targetPath = $uploadDir . time() . '_' . uniqid() . '.webp';
            $img = ($mime === 'image/jpeg') ? @imagecreatefromjpeg($tmpName) : @imagecreatefrompng($tmpName);
            if ($img) {
                if ($mime === 'image/png') {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
                if (imagewebp($img, $targetPath, 80)) {
                    $image_path = $targetPath;
                    $converted = true;
                }
                imagedestroy($img);
            }
        }
        
        if (!$converted) {
            $ext = ($mime === 'image/jpeg') ? '.jpg' : '.png';
            $targetPath = $uploadDir . time() . '_' . uniqid() . $ext;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $image_path = $targetPath;
            }
        }
    }

    if (!empty($name) && !empty($address)) {
        $insertStmt = $pdo->prepare("INSERT INTO pumps (name, address, has_fuel, petrol, octane, diesel, fuel_limit, map_link, phone, image_path, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$name, $address, $has_fuel, $petrol, $octane, $diesel, $fuel_limit, $map_link, $phone, $image_path, $added_by]);
    }
    
    header("Location: ./");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pump') {
    $id = (int)$_POST['pump_id'];
    $has_fuel = isset($_POST['has_fuel']) ? 1 : 0;
    $petrol = isset($_POST['petrol']) ? 1 : 0;
    $octane = isset($_POST['octane']) ? 1 : 0;
    $diesel = isset($_POST['diesel']) ? 1 : 0;
    $fuel_limit = !empty($_POST['fuel_limit']) ? trim($_POST['fuel_limit']) : null;

    $comment = !empty($_POST['comment']) ? trim($_POST['comment']) : '';
    $author = !empty($_POST['author']) ? trim($_POST['author']) : 'অজ্ঞাত';
    
    $image_path = null;
    if (isset($_FILES['update_image']) && $_FILES['update_image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['update_image']['tmp_name'];
        $mime = mime_content_type($tmpName);
        
        if (!in_array($mime, ['image/jpeg', 'image/png'])) {
            $_SESSION['toast_msg'] = "❌ শুধুমাত্র JPG বা PNG ছবি আপলোড করা যাবে!";
            header("Location: ./");
            exit;
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        
        $converted = false;
        if (function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
            $targetPath = $uploadDir . time() . '_upd_' . uniqid() . '.webp';
            $img = ($mime === 'image/jpeg') ? @imagecreatefromjpeg($tmpName) : @imagecreatefrompng($tmpName);
            if ($img) {
                if ($mime === 'image/png') {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
                if (imagewebp($img, $targetPath, 80)) {
                    $image_path = $targetPath;
                    $converted = true;
                }
                imagedestroy($img);
            }
        }
        
        if (!$converted) {
            $ext = ($mime === 'image/jpeg') ? '.jpg' : '.png';
            $targetPath = $uploadDir . time() . '_upd_' . uniqid() . $ext;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $image_path = $targetPath;
            }
        }
    }

    if ($id > 0) {
        $query = "UPDATE pumps SET has_fuel = ?, petrol = ?, octane = ?, diesel = ?, fuel_limit = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?";
        $params = [$has_fuel, $petrol, $octane, $diesel, $fuel_limit, $id];
        $pdo->prepare($query)->execute($params);
        
        if ($comment !== '' || $image_path) {
            if ($ip_restricted) {
                $daily_comment_limit = $settings['daily_comment_limit'] ?? 5;
                $checkDailyComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE ip_address = ? AND DATE(created_at) = CURDATE()");
                $checkDailyComments->execute([$user_ip]);
                if ($checkDailyComments->fetchColumn() >= $daily_comment_limit) {
                    $_SESSION['toast_msg'] = "❌ আপনি আজকের মতামতের লিমিট পার করেছেন!";
                    header("Location: ./");
                    exit;
                }

                $check = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE pump_id = ? AND ip_address = ?");
                $check->execute([$id, $user_ip]);
                if ($check->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO comments (pump_id, ip_address, author, comment_text, image_path) VALUES (?, ?, ?, ?, ?)")->execute([$id, $user_ip, $author, $comment, $image_path]);
                }
            } else {
                $pdo->prepare("INSERT INTO comments (pump_id, ip_address, author, comment_text, image_path) VALUES (?, ?, ?, ?, ?)")->execute([$id, $user_ip, $author, $comment, $image_path]);
            }
        }
    }
    
    header("Location: ./");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_pump') {
    $id = (int)$_POST['pump_id'];
    if ($id > 0) {
        $updateStmt = $pdo->prepare("UPDATE pumps SET report_count = report_count + 1 WHERE id = ?");
        $updateStmt->execute([$id]);
    }
    
    header("Location: ./");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote_pump') {
    $id = (int)$_POST['pump_id'];
    $type = $_POST['vote_type']; // 'likes' or 'dislikes'
    if ($id > 0 && in_array($type, ['likes', 'dislikes'])) {
        if ($ip_restricted) {
            $checkLast = $pdo->prepare("SELECT vote_type, created_at FROM pump_votes WHERE pump_id = ? AND ip_address = ?");
            $checkLast->execute([$id, $user_ip]);
            $lastVote = $checkLast->fetch(PDO::FETCH_ASSOC);

            if ($lastVote) {
                if ($lastVote['vote_type'] === $type) {
                    // একই বাটনে আবার ক্লিক করলে ভোট রিমুভ হয়ে যাবে
                    $pdo->prepare("UPDATE pumps SET $type = GREATEST(0, $type - 1) WHERE id = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM pump_votes WHERE pump_id = ? AND ip_address = ?")->execute([$id, $user_ip]);
                    $_SESSION['toast_msg'] = "✅ আপনার ভোট তুলে নেওয়া হয়েছে!";
                } else {
                    // লাইক থেকে ডিসলাইকে সুইচ করা
                    $old_type = $lastVote['vote_type'];
                    $pdo->prepare("UPDATE pumps SET $old_type = GREATEST(0, $old_type - 1), $type = $type + 1 WHERE id = ?")->execute([$id]);
                    $pdo->prepare("UPDATE pump_votes SET vote_type = ?, created_at = CURRENT_TIMESTAMP WHERE pump_id = ? AND ip_address = ?")->execute([$type, $id, $user_ip]);
                    $_SESSION['toast_msg'] = "✅ আপনার ভোট পরিবর্তন করা হয়েছে!";
                }
            } else {
                $daily_vote_limit = $settings['daily_vote_limit'] ?? 5;
                $checkDaily = $pdo->prepare("SELECT COUNT(*) FROM pump_votes WHERE ip_address = ? AND DATE(created_at) = CURDATE()");
                $checkDaily->execute([$user_ip]);
                if ($checkDaily->fetchColumn() >= $daily_vote_limit) {
                    $_SESSION['toast_msg'] = "❌ আপনি আজকের ভোটের লিমিট পার করেছেন!";
                    header("Location: ./");
                    exit;
                }

                $pdo->prepare("INSERT INTO pump_votes (pump_id, ip_address, vote_type) VALUES (?, ?, ?)")->execute([$id, $user_ip, $type]);
                $pdo->prepare("UPDATE pumps SET $type = $type + 1 WHERE id = ?")->execute([$id]);
                $_SESSION['toast_msg'] = "✅ আপনার ভোট গ্রহণ করা হয়েছে!";
            }
        } else {
            $pdo->prepare("UPDATE pumps SET $type = $type + 1 WHERE id = ?")->execute([$id]);
            $_SESSION['toast_msg'] = "✅ আপনার ভোট গ্রহণ করা হয়েছে!";
        }
    }
    header("Location: ./");
    exit;
}

// পাম্পগুলো প্রথমে তেল আছে কিনা (has_fuel) এবং তারপর আপডেটের সময় অনুযায়ী সাজানো হচ্ছে
$stmt = $pdo->query("SELECT * FROM pumps ORDER BY has_fuel DESC, last_updated DESC");
$pumps = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtComments = $pdo->query("SELECT * FROM comments ORDER BY created_at ASC");
$allComments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
$commentsByPump = [];
foreach($allComments as $c) {
    $commentsByPump[$c['pump_id']][] = $c;
}

require_once 'header.php';
?>

<?php if (isset($_SESSION['toast_msg'])): ?>
    <div id="toast" class="toast show"><?= htmlspecialchars($_SESSION['toast_msg']) ?></div>
    <?php unset($_SESSION['toast_msg']); ?>
<?php endif; ?>

<div class="main-container">
    <!-- Hero Section -->
    <div class="hero-banner">
        <div>
            <h2>কোথায় তেল পাওয়া যাচ্ছে?</h2>
            <p>আপনার জানা তথ্য অন্যদের সাথে শেয়ার করুন।</p>
        </div>
        <button class="hero-btn" id="mainAddBtn">নতুন পাম্প যোগ করুন</button>
    </div>

    <!-- Notice Alerts -->
    <div class="alert-box">
        <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation" style="color: var(--primary);"></i></div>
        <div class="alert-content">
            <h3>জরুরি বিজ্ঞপ্তি</h3>
            <ul style="margin: 8px 0 0 0; padding-left: 20px; color: var(--text-muted); font-size: 15px; line-height: 1.6;">
                <li style="margin-bottom: 8px;">নতুন পাম্প যোগ করার আগে অনুগ্রহ করে একবার লিস্টটি চেক করুন। পাম্পটি কি আগে থেকেই যুক্ত আছে? থাকলে নতুন করে যোগ করার প্রয়োজন নেই।</li>
                <li style="margin-bottom: 8px;">পাম্পে গিয়ে বাস্তব অবস্থা দেখলে <span class="highlight-text">“অবস্থা জানান”</span> বাটনে ক্লিক করে আপডেট দিন। আপনার একটু সহযোগিতায় বাঁচবে অনেকের সময় ও শ্রম।</li>
                <li>লিস্টে নেই এমন কোনো পাম্পের খোঁজ পেলে, দয়া করে একটু সময় নিয়ে সেটি যুক্ত করুন। আপনার এই ছোট্ট অবদানটি অন্যদের অনেক উপকারে আসবে! 🙌</li>
            </ul>
        </div>
    </div>

    <div class="alert-box alert-warning">
        <div class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="alert-content">
            <h3>সতর্কতা: সঠিক তথ্য দিন!</h3>
            <p>অনুগ্রহ করে কোনো ভুয়া বা ভুল আপডেট দিয়ে মানুষকে বিভ্রান্ত করবেন না। আপনার দেওয়া তথ্যের ওপর ভিত্তি করেই অন্যরা পাম্পে যান। তাই দয়া করে দায়িত্বশীল হোন।</p>
        </div>
    </div>

    <!-- Filters & Stats -->
    <div class="filters-wrapper">
        <div class="filters-header">
            <h3>সকল পাম্প (<?= count($pumps) ?>)</h3>
            <button class="refresh-btn" id="refreshBtn"><i class="fa-solid fa-rotate-right"></i> রিফ্রেশ</button>
        </div>
        <div class="filters-scroll">
            <button class="pill active" data-filter="all">সব পাম্প</button>
            <button class="pill" data-filter="has-fuel"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> তেল আছে</button>
            <button class="pill" data-filter="no-fuel"><i class="fa-solid fa-circle-xmark" style="color:var(--danger)"></i> তেল নেই</button>
            <button class="pill" data-filter="petrol"><i class="fa-solid fa-gas-pump"></i> পেট্রোল</button>
            <button class="pill" data-filter="octane"><i class="fa-solid fa-droplet"></i> অকটেন</button>
            <button class="pill" data-filter="diesel"><i class="fa-solid fa-oil-can"></i> ডিজেল</button>
        </div>
    </div>

    <!-- ডাইনামিক পাম্প লিস্ট -->
    <div class="pump-list">
        <?php if (count($pumps) > 0): ?>
            <?php foreach ($pumps as $pump): ?>
                <div class="pump-card" 
                     data-has-fuel="<?= $pump['has_fuel'] ? '1' : '0' ?>"
                     data-petrol="<?= $pump['petrol'] ? '1' : '0' ?>"
                     data-octane="<?= $pump['octane'] ? '1' : '0' ?>"
                     data-diesel="<?= $pump['diesel'] ? '1' : '0' ?>"
                >
                    <?php if (!empty($pump['image_path'])): ?>
                    <div class="pump-image-wrapper">
                        <img src="<?= htmlspecialchars($pump['image_path']) ?>" alt="Pump" class="pump-thumb">
                    </div>
                    <?php endif; ?>

                    <div class="card-header">
                        <div class="card-header-text">
                            <h2 class="pump-name"><?= htmlspecialchars($pump['name']) ?></h2>
                            <p class="pump-address">
                                <i class="fa-solid fa-location-dot"></i>
                                <?= htmlspecialchars($pump['address']) ?>
                            </p>
                        </div>
                        <?php if ($pump['has_fuel']): ?>
                        <span class="status-badge status-yes"><i class="fa-solid fa-circle-check"></i> তেল আছে</span>
                        <?php else: ?>
                        <span class="status-badge status-no"><i class="fa-solid fa-circle-xmark"></i> তেল নেই</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-content">
                        <?php if (!empty($pump['phone']) || !empty($pump['map_link'])): ?>
                        <div class="contact-links-row">
                            <?php if (!empty($pump['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($pump['phone']) ?>" class="contact-pill"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($pump['phone']) ?></a>
                            <?php endif; ?>
                            <?php if (!empty($pump['map_link'])): ?>
                            <a href="<?= htmlspecialchars($pump['map_link']) ?>" target="_blank" rel="noopener noreferrer" class="contact-pill"><i class="fa-solid fa-map-location-dot"></i> ম্যাপে দেখুন</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="fuel-grid">
                            <?php if ($pump['petrol']): ?>
                            <span class="fuel-item active"><i class="fa-solid fa-gas-pump"></i> পেট্রোল</span>
                            <?php endif; ?>
                            <?php if ($pump['octane']): ?>
                            <span class="fuel-item active"><i class="fa-solid fa-droplet"></i> অকটেন</span>
                            <?php endif; ?>
                            <?php if ($pump['diesel']): ?>
                            <span class="fuel-item active"><i class="fa-solid fa-oil-can"></i> ডিজেল</span>
                            <?php endif; ?>
                            <?php if (!empty($pump['fuel_limit'])): ?>
                            <?php 
                                $limit_val = trim($pump['fuel_limit']);
                                $limit_display = is_numeric($limit_val) ? $limit_val . ' টাকার' : $limit_val;
                            ?>
                            <span class="limit-badge" title="তেল দেওয়ার লিমিট"><i class="fa-solid fa-money-bill-wave"></i> <?= htmlspecialchars($limit_display) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                
                    <?php 
                        $pumpComments = $commentsByPump[$pump['id']] ?? [];
                        $commentCount = count($pumpComments);
                        $latestComment = $commentCount > 0 ? end($pumpComments) : null;
                    ?>

                    <div class="card-feedback">
                        <div class="feedback-actions">
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="vote_pump">
                                <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                <input type="hidden" name="vote_type" value="likes">
                                <button type="submit" class="vote-btn vote-like" title="এই তথ্যটি সঠিক"><i class="fa-solid fa-check"></i> <span><?= $pump['likes'] ?? 0 ?> সত্যি</span></button>
                            </form>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="vote_pump">
                                <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                <input type="hidden" name="vote_type" value="dislikes">
                                <button type="submit" class="vote-btn vote-dislike" title="এই তথ্যটি মিথ্যা"><i class="fa-solid fa-times"></i> <span><?= $pump['dislikes'] ?? 0 ?> মিথ্যা</span></button>
                            </form>
                        </div>
                        <?php if ($latestComment): ?>
                        <div class="latest-comment">
                            <span class="comment-author"><i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($latestComment['author'] ?: 'অজ্ঞাত') ?>:</span>
                            <p class="comment-text">"<?= nl2br(htmlspecialchars($latestComment['comment_text'])) ?>"</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <div class="footer-meta">
                            <div class="meta-text">
                                <span class="last-update"><i class="fa-regular fa-clock"></i> আপডেট: <strong><?= time_elapsed_string($pump['last_updated']) ?></strong></span>
                                <?php if (!empty($pump['added_by'])): ?>
                                <span class="added-by-text"><i class="fa-regular fa-user"></i> যুক্ত করেছেন: <strong><?= htmlspecialchars($pump['added_by']) ?></strong></span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই তথ্যটি ভুল এবং আপনি রিপোর্ট করতে চান?');" style="margin:0;">
                                <input type="hidden" name="action" value="report_pump">
                                <input type="hidden" name="pump_id" value="<?= $pump['id'] ?>">
                                <button type="submit" class="report-btn" title="ভুল তথ্য রিপোর্ট করুন"><i class="fa-regular fa-flag"></i> রিপোর্ট</button>
                            </form>
                        </div>
                        <div class="card-actions">
                            <button class="update-btn open-update-modal" 
                                    data-id="<?= $pump['id'] ?>"
                                    data-name="<?= htmlspecialchars($pump['name']) ?>"
                                    data-has-fuel="<?= $pump['has_fuel'] ? '1' : '0' ?>"
                                    data-petrol="<?= $pump['petrol'] ? '1' : '0' ?>"
                                    data-octane="<?= $pump['octane'] ? '1' : '0' ?>"
                                    data-diesel="<?= $pump['diesel'] ? '1' : '0' ?>"
                                    data-fuel-limit="<?= htmlspecialchars($pump['fuel_limit'] ?? '') ?>"
                            ><i class="fa-regular fa-comment-dots"></i> অবস্থা জানান (<?= $commentCount ?>)</button>
                            <a href="details.php?id=<?= $pump['id'] ?>" class="details-btn">বিস্তারিত</a>
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="empty-state" <?= count($pumps) === 0 ? 'style="display: block;"' : '' ?>>
            <p>কোনো পাম্পের তথ্য পাওয়া যায়নি।</p>
        </div>
    </div>
</div>

<!-- Add Pump Modal -->
<div class="modal-overlay" id="addPumpModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fa-solid fa-gas-pump"></i> নতুন পাম্প যোগ করুন</h2>
            <button class="close-btn" id="closeModalCross">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_pump">
            <div class="form-group">
                <label>পাম্পের নাম <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" required placeholder="যেমন: রহমান ফিলিং স্টেশন">
            </div>
            <div class="form-group">
                <label>ঠিকানা <span style="color:var(--danger)">*</span></label>
                <input type="text" name="address" required placeholder="যেমন: চুয়াডাঙ্গা সদর">
            </div>
            
            <div class="form-section-title">তেলের বর্তমান অবস্থা</div>
            
            <label class="toggle-container highlight-box">
                <div class="toggle-switch">
                    <input type="checkbox" name="has_fuel" id="addHasFuel" class="dynamic-toggle" data-text-id="addHasFuelText" data-on="<i class='fa-solid fa-circle-check'></i> তেল দিচ্ছে" data-off="<i class='fa-solid fa-circle-xmark'></i> তেল দিচ্ছে না" checked>
                    <span class="toggle-slider"></span>
                </div>
                <span id="addHasFuelText" class="toggle-text"><i class='fa-solid fa-circle-check'></i> তেল দিচ্ছে</span>
            </label>
            
            <div class="fuel-checkboxes">
                <label class="toggle-container">
                    <div class="toggle-switch">
                        <input type="checkbox" name="petrol" id="addPetrol" class="dynamic-toggle" data-text-id="addPetrolText" data-on="<i class='fa-solid fa-gas-pump'></i> পেট্রোল দিচ্ছে" data-off="<i class='fa-solid fa-circle-xmark'></i> পেট্রোল দিচ্ছে না">
                        <span class="toggle-slider"></span>
                    </div>
                    <span id="addPetrolText" class="toggle-text"><i class='fa-solid fa-circle-xmark'></i> পেট্রোল দিচ্ছে না</span>
                </label>
                <label class="toggle-container">
                    <div class="toggle-switch">
                        <input type="checkbox" name="octane" id="addOctane" class="dynamic-toggle" data-text-id="addOctaneText" data-on="<i class='fa-solid fa-droplet'></i> অকটেন দিচ্ছে" data-off="<i class='fa-solid fa-circle-xmark'></i> অকটেন দিচ্ছে না">
                        <span class="toggle-slider"></span>
                    </div>
                    <span id="addOctaneText" class="toggle-text"><i class='fa-solid fa-circle-xmark'></i> অকটেন দিচ্ছে না</span>
                </label>
                <label class="toggle-container">
                    <div class="toggle-switch">
                        <input type="checkbox" name="diesel" id="addDiesel" class="dynamic-toggle" data-text-id="addDieselText" data-on="<i class='fa-solid fa-oil-can'></i> ডিজেল দিচ্ছে" data-off="<i class='fa-solid fa-circle-xmark'></i> ডিজেল দিচ্ছে না">
                        <span class="toggle-slider"></span>
                    </div>
                    <span id="addDieselText" class="toggle-text"><i class='fa-solid fa-circle-xmark'></i> ডিজেল দিচ্ছে না</span>
                </label>
            </div>

            <div class="form-group mt-3">
                <label>কত টাকার তেল দিচ্ছে? <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">(ঐচ্ছিক)</span></label>
                <div style="position: relative;">
                    <input type="text" name="fuel_limit" placeholder="যেমন: 500" style="padding-right: 100px;">
                    <span style="position: absolute; right: 16px; top: 16px; color: var(--text-muted); font-weight: 600; font-size: 14px;">টাকার দিচ্ছে</span>
                </div>
            </div>

            <div class="form-group mt-3">
                <label>গুগল ম্যাপ লিংক <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">(চাইলে নাও দিতে পারেন)</span></label>
                <input type="url" name="map_link" placeholder="https://maps.google.com/...">
                <a href="#" id="mapHelpToggle" style="font-size:13px; color:var(--primary); text-decoration:none; margin-top:6px; display:inline-block; font-weight:600;">লিংক কিভাবে পাবেন?</a>
                <div id="mapHelpContent" class="map-help-content">
                    <strong>গুগল ম্যাপে লিংক পাওয়ার নিয়ম:</strong>
                    <ol>
                        <li>আপনার ফোনে Google Maps অ্যাপ খুলুন</li>
                        <li>পাম্পের জায়গায় ম্যাপে ট্যাপ করুন (লাল পিন আসবে)</li>
                        <li>"Share" বা "শেয়ার" বাটনে ক্লিক করুন</li>
                        <li>"Copy link" বা "লিংক কপি" করুন</li>
                        <li>এখানে এসে পেস্ট করুন</li>
                    </ol>
                </div>
            </div>

            <div class="form-group">
                <label>ফোন নম্বর <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">(চাইলে নাও দিতে পারেন)</span></label>
                <input type="tel" name="phone" placeholder="01XXXXXXXXX">
            </div>

            <div class="form-group">
                <label>পাম্পের ছবি <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">(চাইলে নাও দিতে পারেন)</span></label>
                <div class="file-upload-wrapper">
                    <input type="file" name="image" id="pumpImage" accept=".jpg, .jpeg, .png" class="file-input">
                    <label for="pumpImage" class="file-label" id="fileLabel"><i class="fa-solid fa-camera"></i> ছবি তুলুন বা গ্যালারি থেকে বাছাই করুন</label>
                </div>
            </div>

            <div class="form-group">
                <label>আপনার নাম <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">(চাইলে নাও দিতে পারেন)</span></label>
                <input type="text" name="added_by" placeholder="আপনার নাম লিখুন">
            </div>

            <div class="modal-actions" style="margin-top:24px;">
                <button type="submit" class="btn-submit" style="width:100%; padding:16px; font-size:18px;">পাম্প যোগ করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Pump Modal -->
<div class="modal-overlay" id="updatePumpModal">
    <div class="modal">
        <div class="modal-header" style="justify-content: center; position: relative;">
            <h2 style="font-size: 22px;">অবস্থা জানান</h2>
            <button type="button" class="close-btn" id="closeUpdateModalCross" style="position: absolute; right: 0; top: -5px;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_pump">
            <input type="hidden" name="pump_id" id="updatePumpId">
            
            <div style="text-align: center; margin-bottom: 24px;">
                <input type="text" id="updatePumpName" disabled style="background-color: transparent; border: none; font-size: 20px; font-weight: 700; color: var(--primary); padding: 0; box-shadow: none; text-align: center; width: 100%;">
            </div>
            
            <div class="form-section-title"><span>1</span> তেল পাওয়া যাচ্ছে?</div>
            <label class="toggle-container highlight-box">
                <div class="toggle-switch">
                    <input type="checkbox" name="has_fuel" id="updateHasFuel" class="dynamic-toggle" data-text-id="updateHasFuelText" data-on="✅ তেল দিচ্ছে" data-off="❌ তেল দিচ্ছে না">
                    <span class="toggle-slider"></span>
                </div>
                <span id="updateHasFuelText" class="toggle-text">❌ তেল দিচ্ছে না</span>
            </label>
            
            <div class="fuel-checkboxes">
                <label class="toggle-container">
                    <div class="toggle-switch">
                        <input type="checkbox" name="petrol" id="updatePetrol" class="dynamic-toggle" data-text-id="updatePetrolText" data-on="⛽ পেট্রোল দিচ্ছে" data-off="❌ পেট্রোল দিচ্ছে না">
                        <span class="toggle-slider"></span>
                    </div>
                    <span id="updatePetrolText" class="toggle-text">❌ পেট্রোল দিচ্ছে না</span>
                </label>
                <label class="toggle-container">
                    <div class="toggle-switch">
                        <input type="checkbox" name="octane" id="updateOctane" class="dynamic-toggle" data-text-id="updateOctaneText" data-on="🔵 অকটেন দিচ্ছে" data-off="❌ অকটেন দিচ্ছে না">
                        <span class="toggle-slider"></span>
                    </div>
                    <span id="updateOctaneText" class="toggle-text">❌ অকটেন দিচ্ছে না</span>
                </label>
                <label class="toggle-container">
                    <div class="toggle-switch">
                        <input type="checkbox" name="diesel" id="updateDiesel" class="dynamic-toggle" data-text-id="updateDieselText" data-on="🟤 ডিজেল দিচ্ছে" data-off="❌ ডিজেল দিচ্ছে না">
                        <span class="toggle-slider"></span>
                    </div>
                    <span id="updateDieselText" class="toggle-text">❌ ডিজেল দিচ্ছে না</span>
                </label>
            </div>

            <div class="form-group mt-3">
                <div class="form-section-title mt-3"><span>2</span> কত টাকার দিচ্ছে? <small style="font-weight:normal; text-transform:none; font-size:12px; color:var(--text-muted); margin-left:4px;">(চাইলে নাও দিতে পারেন)</small></div>
                <div style="position: relative;">
                    <input type="text" name="fuel_limit" id="updateFuelLimit" placeholder="যেমন: 130" style="padding-right: 100px;">
                    <span style="position: absolute; right: 16px; top: 16px; color: var(--text-muted); font-weight: 600; font-size: 14px;">টাকার দিচ্ছে</span>
                </div>
            </div>

            <div class="form-group">
                <div class="form-section-title mt-3"><span>3</span> বর্তমান অবস্থার ছবি <small style="font-weight:normal; text-transform:none; font-size:12px; color:var(--text-muted); margin-left:4px;">(চাইলে নাও দিতে পারেন)</small></div>
                <div class="file-upload-wrapper">
                    <input type="file" name="update_image" id="updatePumpImage" accept=".jpg, .jpeg, .png" class="file-input">
                    <label for="updatePumpImage" class="file-label" id="updateFileLabel"><i class="fa-solid fa-camera"></i> ছবি তুলুন বা বাছাই করুন</label>
                </div>
            </div>

            <div class="form-group">
                <div class="form-section-title mt-3"><span>4</span> মন্তব্য <small style="font-weight:normal; text-transform:none; font-size:12px; color:var(--text-muted); margin-left:4px;">(চাইলে নাও দিতে পারেন)</small></div>
                <textarea name="comment" placeholder="যেমন: অনেক ভিড়, ১ ঘণ্টা লাইন..." style="width: 100%; padding: 14px; border: 2px solid var(--border); border-radius: var(--radius-md); font-size: 15px; font-family: inherit; resize: vertical; min-height: 80px; outline:none; transition:0.2s; margin-bottom:12px;"></textarea>
                <input type="text" name="author" placeholder="আপনার নাম (চাইলে নাও দিতে পারেন)">
            </div>

            <div class="modal-actions" style="margin-top: 30px; display: block;">
                <button type="submit" class="btn-submit" style="width: 100%; padding: 16px; font-size: 18px;">তথ্য জমা দিন</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>