<?php
session_start();

// ইনস্টল করা না থাকলে ইনস্টল পেজে নিয়ে যাবে
if (!file_exists('db.php')) {
    header("Location: install.php");
    exit;
}

require_once 'db.php';

$user_ip = $_SERVER['REMOTE_ADDR'];

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

// আইডি না থাকলে হোমপেজে পাঠিয়ে দেবে
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ./");
    exit;
}

$pump_id = (int)$_GET['id'];

// পাম্পের তথ্য ফেচ করা হচ্ছে
$stmt = $pdo->prepare("SELECT * FROM pumps WHERE id = ?");
$stmt->execute([$pump_id]);
$pump = $stmt->fetch(PDO::FETCH_ASSOC);

// পাম্প ভিউ কাউন্ট (সেশন প্রতি একবার)
if (!isset($_SESSION['viewed_pumps']) || !in_array($pump_id, $_SESSION['viewed_pumps'])) {
    try {
        $pdo->prepare("UPDATE pumps SET view_count = view_count + 1 WHERE id = ?")->execute([$pump_id]);
    } catch (Exception $e) {}
    $_SESSION['viewed_pumps'][] = $pump_id;
}

if (!$pump) {
    header("Location: ./");
    exit;
}

// ফুটারের জন্য সেটিংস ফেচ
$stmtSet = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmtSet->fetch(PDO::FETCH_ASSOC) ?: [];
$ip_restricted = $settings['ip_restriction'] ?? 1;

// আপডেট করার লজিক (মোডাল থেকে)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pump') {
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
            header("Location: details.php?id=" . $pump_id);
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

    $query = "UPDATE pumps SET has_fuel = ?, petrol = ?, octane = ?, diesel = ?, fuel_limit = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?";
    $params = [$has_fuel, $petrol, $octane, $diesel, $fuel_limit, $pump_id];
    $pdo->prepare($query)->execute($params);
    
    if ($comment !== '' || $image_path) {
        if ($ip_restricted) {
            $daily_comment_limit = $settings['daily_comment_limit'] ?? 5;
            $checkDailyComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE ip_address = ? AND DATE(created_at) = CURDATE()");
            $checkDailyComments->execute([$user_ip]);
            if ($checkDailyComments->fetchColumn() >= $daily_comment_limit) {
                $_SESSION['toast_msg'] = "❌ আপনি আজকের মতামতের লিমিট পার করেছেন!";
                header("Location: details.php?id=" . $pump_id);
                exit;
            }

            $check = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE pump_id = ? AND ip_address = ?");
            $check->execute([$pump_id, $user_ip]);
            if ($check->fetchColumn() == 0) {
                $pdo->prepare("INSERT INTO comments (pump_id, ip_address, author, comment_text, image_path) VALUES (?, ?, ?, ?, ?)")->execute([$pump_id, $user_ip, $author, $comment, $image_path]);
            }
        } else {
            $pdo->prepare("INSERT INTO comments (pump_id, ip_address, author, comment_text, image_path) VALUES (?, ?, ?, ?, ?)")->execute([$pump_id, $user_ip, $author, $comment, $image_path]);
        }
    }
    $pdo->prepare("UPDATE pumps SET last_updated = CURRENT_TIMESTAMP WHERE id = ?")->execute([$pump_id]);
    
    header("Location: details.php?id=" . $pump_id);
    exit;
}

// ভোট (লাইক/ডিসলাইক) দেওয়ার লজিক (নতুন যুক্ত করা হলো)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote_pump') {
    $type = $_POST['vote_type'];
    if (in_array($type, ['likes', 'dislikes'])) {
        if ($ip_restricted) {
            $checkLast = $pdo->prepare("SELECT vote_type, created_at FROM pump_votes WHERE pump_id = ? AND ip_address = ?");
            $checkLast->execute([$pump_id, $user_ip]);
            $lastVote = $checkLast->fetch(PDO::FETCH_ASSOC);

            if ($lastVote) {
                if ($lastVote['vote_type'] === $type) {
                    // Remove vote (toggle off)
                    $pdo->prepare("UPDATE pumps SET $type = GREATEST(0, $type - 1) WHERE id = ?")->execute([$pump_id]);
                    $pdo->prepare("DELETE FROM pump_votes WHERE pump_id = ? AND ip_address = ?")->execute([$pump_id, $user_ip]);
                    $_SESSION['toast_msg'] = "✅ আপনার ভোট তুলে নেওয়া হয়েছে!";
                } else {
                    // Switch vote
                    $old_type = $lastVote['vote_type'];
                    $pdo->prepare("UPDATE pumps SET $old_type = GREATEST(0, $old_type - 1), $type = $type + 1 WHERE id = ?")->execute([$pump_id]);
                    $pdo->prepare("UPDATE pump_votes SET vote_type = ?, created_at = CURRENT_TIMESTAMP WHERE pump_id = ? AND ip_address = ?")->execute([$type, $pump_id, $user_ip]);
                    $_SESSION['toast_msg'] = "✅ আপনার ভোট পরিবর্তন করা হয়েছে!";
                }
            } else {
                $daily_vote_limit = $settings['daily_vote_limit'] ?? 5;
                $checkDaily = $pdo->prepare("SELECT COUNT(*) FROM pump_votes WHERE ip_address = ? AND DATE(created_at) = CURDATE()");
                $checkDaily->execute([$user_ip]);
                if ($checkDaily->fetchColumn() >= $daily_vote_limit) {
                    $_SESSION['toast_msg'] = "❌ আপনি আজকের ভোটের লিমিট পার করেছেন!";
                    header("Location: details.php?id=" . $pump_id);
                    exit;
                }

                $pdo->prepare("INSERT INTO pump_votes (pump_id, ip_address, vote_type) VALUES (?, ?, ?)")->execute([$pump_id, $user_ip, $type]);
                $pdo->prepare("UPDATE pumps SET $type = $type + 1 WHERE id = ?")->execute([$pump_id]);
                $_SESSION['toast_msg'] = "✅ আপনার ভোট গ্রহণ করা হয়েছে!";
            }
        } else {
            $pdo->prepare("UPDATE pumps SET $type = $type + 1 WHERE id = ?")->execute([$pump_id]);
            $_SESSION['toast_msg'] = "✅ আপনার ভোট গ্রহণ করা হয়েছে!";
        }
    }
    header("Location: details.php?id=" . $pump_id);
    exit;
}

// কমেন্টগুলো ফেচ করা হচ্ছে
$stmtComments = $pdo->prepare("SELECT * FROM comments WHERE pump_id = ? ORDER BY created_at DESC");
$stmtComments->execute([$pump_id]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

// পরিসংখ্যান (Stats) ক্যালকুলেট করা
$total_reports = $pump['report_count'] ?? 0;
$total_updates = count($comments);
$today_updates = 0;
foreach($comments as $c) {
    if(date('Y-m-d', strtotime($c['created_at'])) === date('Y-m-d')) {
        $today_updates++;
    }
}

require_once 'header.php';
?>

<?php if (isset($_SESSION['toast_msg'])): ?>
    <div id="toast" class="toast show"><?= htmlspecialchars($_SESSION['toast_msg']) ?></div>
    <?php unset($_SESSION['toast_msg']); ?>
<?php endif; ?>

<div class="main-container">
    <a href="./" class="back-btn mb-4"><i class="fa-solid fa-arrow-left"></i> ফিরে যান</a>

    <div class="details-card">
        <?php if (!empty($pump['image_path'])): ?>
            <div class="details-image-wrapper">
                <img src="<?= htmlspecialchars($pump['image_path']) ?>" alt="Pump" class="pump-thumb" onerror="this.parentElement.style.display='none';">
            </div>
        <?php endif; ?>

        <div class="details-grid-compact">
            <div class="details-main-info">
                <h1 class="details-title"><?= htmlspecialchars($pump['name']) ?></h1>
                <p class="details-address"><i class="fa-solid fa-location-dot"></i><?= htmlspecialchars($pump['address']) ?></p>
                
                <div class="contact-links-row" style="margin-top: 20px; margin-bottom: 24px;">
                    <?php if (!empty($pump['phone'])): ?><a href="tel:<?= htmlspecialchars($pump['phone']) ?>" class="contact-pill"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($pump['phone']) ?></a><?php endif; ?>
                    <?php if (!empty($pump['map_link'])): ?><a href="<?= htmlspecialchars($pump['map_link']) ?>" target="_blank" rel="noopener noreferrer" class="contact-pill"><i class="fa-solid fa-map-location-dot"></i> ম্যাপে দেখুন</a><?php endif; ?>
                </div>

                <button class="btn-submit update-btn open-update-modal" style="width: 100%; padding: 16px; font-size: 18px; flex: unset;" data-id="<?= $pump['id'] ?>" data-name="<?= htmlspecialchars($pump['name']) ?>" data-has-fuel="<?= $pump['has_fuel'] ? '1' : '0' ?>" data-petrol="<?= $pump['petrol'] ? '1' : '0' ?>" data-octane="<?= $pump['octane'] ? '1' : '0' ?>" data-diesel="<?= $pump['diesel'] ? '1' : '0' ?>" data-fuel-limit="<?= htmlspecialchars($pump['fuel_limit'] ?? '') ?>">
                    <i class="fa-solid fa-pen-to-square"></i> মতামত দিন / অবস্থা জানান
                </button>

                <!-- Comments Section Moved Here to fill space -->
                <div class="comments-section" style="margin-top: 40px;">
                    <h3 class="section-title" style="margin-bottom: 24px; font-size: 18px;"><i class="fa-regular fa-comments"></i> কমিউনিটি আপডেট (<?= $total_updates ?>)</h3>
                    
                    <?php if (count($comments) > 0): ?>
                        <?php foreach($comments as $c): ?>
                            <div class="community-card" style="padding: 20px;">
                                <div class="community-card-header" style="padding-bottom: 12px; margin-bottom: 12px;">
                                    <div class="community-author"><i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($c['author'] ?: 'অজ্ঞাত') ?></div>
                                    <div class="community-time"><i class="fa-regular fa-clock"></i> <?= time_elapsed_string($c['created_at']) ?></div>
                                </div>
                                
                                <?php if(!empty($c['comment_text'])): ?>
                                    <div class="community-text"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></div>
                                <?php endif; ?>
                                <?php if(!empty($c['image_path'])): ?>
                                    <div style="margin-top: 12px;">
                                        <a href="<?= htmlspecialchars($c['image_path']) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($c['image_path']) ?>" alt="Update Photo" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid var(--border); display: block;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="display: block; padding: 40px 20px; background: #f8fafc; border-style: solid;">
                            <p>এখনো কোনো আপডেট দেওয়া হয়নি।</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="details-status-info">
                <div class="details-widget-box">
                    <div class="widget-header">
                        <?php if ($pump['has_fuel']): ?><span class="status-badge status-yes"><i class="fa-solid fa-circle-check"></i> তেল আছে</span><?php else: ?><span class="status-badge status-no"><i class="fa-solid fa-circle-xmark"></i> তেল নেই</span><?php endif; ?>
                        <div class="details-meta"><span class="meta-item"><i class="fa-regular fa-clock"></i> <strong><?= time_elapsed_string($pump['last_updated']) ?></strong></span></div>
                    </div>
                    <div class="fuel-grid justify-start" style="padding-top: 16px; border-top: 1px dashed var(--border);">
                        <?php if ($pump['petrol']): ?><span class="fuel-item active"><i class="fa-solid fa-gas-pump"></i> পেট্রোল</span><?php endif; ?>
                        <?php if ($pump['octane']): ?><span class="fuel-item active"><i class="fa-solid fa-droplet"></i> অকটেন</span><?php endif; ?>
                        <?php if ($pump['diesel']): ?><span class="fuel-item active"><i class="fa-solid fa-oil-can"></i> ডিজেল</span><?php endif; ?>
                    </div>
                    <?php if (!empty($pump['fuel_limit'])): ?>
                        <?php $limit_val = trim($pump['fuel_limit']); $limit_display = is_numeric($limit_val) ? $limit_val . ' টাকার' : $limit_val; ?>
                        <div class="limit-badge" style="margin-top: 12px; width: 100%; text-align: center; padding: 8px; justify-content: center;"><i class="fa-solid fa-money-bill-wave"></i> <span><?= htmlspecialchars($limit_display) ?></span></div>
                    <?php endif; ?>
                </div>
                
                <div class="details-widget-box">
                    <h3 class="widget-title">তথ্যটি সঠিক?</h3>
                    <div class="feedback-actions" style="gap: 12px;">
                        <form method="POST" style="margin: 0; flex: 1;"><input type="hidden" name="action" value="vote_pump"><input type="hidden" name="vote_type" value="likes"><button type="submit" class="vote-btn vote-like" style="padding: 10px;" title="এই তথ্যটি সঠিক"><i class="fa-solid fa-check"></i> <span><?= $pump['likes'] ?? 0 ?> সত্যি</span></button></form>
                        <form method="POST" style="margin: 0; flex: 1;"><input type="hidden" name="action" value="vote_pump"><input type="hidden" name="vote_type" value="dislikes"><button type="submit" class="vote-btn vote-dislike" style="padding: 10px;" title="এই তথ্যটি মিথ্যা"><i class="fa-solid fa-times"></i> <span><?= $pump['dislikes'] ?? 0 ?> মিথ্যা</span></button></form>
                    </div>
                </div>

                <div class="details-widget-box">
                    <h3 class="widget-title">পরিসংখ্যান</h3>
                    <div class="compact-stats">
                        <div class="compact-stat-item"><span><?= $pump['view_count'] ?? 0 ?></span><i class="fa-solid fa-eye"></i> ভিউ</div>
                        <div class="compact-stat-item"><span><?= $total_updates ?></span><i class="fa-solid fa-clock-rotate-left"></i> আপডেট</div>
                        <div class="compact-stat-item"><span><?= $total_reports ?></span><i class="fa-regular fa-flag"></i> রিপোর্ট</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Pump Modal (Copied for functionality) -->
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
                <label class="toggle-container"><div class="toggle-switch"><input type="checkbox" name="petrol" id="updatePetrol" class="dynamic-toggle" data-text-id="updatePetrolText" data-on="⛽ পেট্রোল দিচ্ছে" data-off="❌ পেট্রোল দিচ্ছে না"><span class="toggle-slider"></span></div><span id="updatePetrolText" class="toggle-text">❌ পেট্রোল দিচ্ছে না</span></label>
                <label class="toggle-container"><div class="toggle-switch"><input type="checkbox" name="octane" id="updateOctane" class="dynamic-toggle" data-text-id="updateOctaneText" data-on="🔵 অকটেন দিচ্ছে" data-off="❌ অকটেন দিচ্ছে না"><span class="toggle-slider"></span></div><span id="updateOctaneText" class="toggle-text">❌ অকটেন দিচ্ছে না</span></label>
                <label class="toggle-container"><div class="toggle-switch"><input type="checkbox" name="diesel" id="updateDiesel" class="dynamic-toggle" data-text-id="updateDieselText" data-on="🟤 ডিজেল দিচ্ছে" data-off="❌ ডিজেল দিচ্ছে না"><span class="toggle-slider"></span></div><span id="updateDieselText" class="toggle-text">❌ ডিজেল দিচ্ছে না</span></label>
            </div>
            <div class="form-group mt-3">
                <div class="form-section-title mt-3"><span>2</span> কত টাকার দিচ্ছে? <small style="font-weight:normal; text-transform:none; font-size:12px; color:var(--text-muted); margin-left:4px;">(ঐচ্ছিক)</small></div>
                <div style="position: relative;">
                    <input type="text" name="fuel_limit" id="updateFuelLimit" placeholder="যেমন: 500" style="padding-right: 100px;">
                    <span style="position: absolute; right: 16px; top: 16px; color: var(--text-muted); font-weight: 600; font-size: 14px;">টাকার দিচ্ছে</span>
                </div>
            </div>
            <div class="form-group">
                <div class="form-section-title mt-3"><span>3</span> বর্তমান অবস্থার ছবি <small style="font-weight:normal; text-transform:none; font-size:12px; color:var(--text-muted); margin-left:4px;">(ঐচ্ছিক)</small></div>
                <div class="file-upload-wrapper">
                    <input type="file" name="update_image" id="updatePumpImage" accept=".jpg, .jpeg, .png" class="file-input">
                    <label for="updatePumpImage" class="file-label" id="updateFileLabel">📸 ছবি তুলুন বা বাছাই করুন</label>
                </div>
            </div>
            <div class="form-group">
                <div class="form-section-title mt-3"><span>4</span> মন্তব্য <small style="font-weight:normal; text-transform:none; font-size:12px; color:var(--text-muted); margin-left:4px;">(ঐচ্ছিক)</small></div>
                <textarea name="comment" placeholder="যেমন: অনেক ভিড়, ১ ঘণ্টা লাইন..." style="width: 100%; padding: 14px; border: 2px solid var(--border); border-radius: var(--radius-md); font-size: 15px; font-family: inherit; resize: vertical; min-height: 80px; outline:none; transition:0.2s; margin-bottom:12px;"></textarea>
                <input type="text" name="author" placeholder="আপনার নাম (ঐচ্ছিক)">
            </div>
            <div class="modal-actions" style="margin-top: 30px; display: block;">
                <button type="submit" class="btn-submit" style="width: 100%; padding: 16px; font-size: 18px;">তথ্য জমা দিন</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>