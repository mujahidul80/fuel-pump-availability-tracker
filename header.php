<?php
// ডাইনামিক পেজ টাইটেল এবং ডেসক্রিপশন সেট করা
$site_title = htmlspecialchars($settings['site_name'] ?? 'তেল দিচ্ছে?');
$site_desc = htmlspecialchars($settings['site_desc'] ?? 'কোথায় তেল পাওয়া যাচ্ছে? আপনার জানা তথ্য অন্যদের সাথে শেয়ার করুন।');

$page_title = isset($pump) ? htmlspecialchars($pump['name']) . " - " . $site_title : $site_title . " - জ্বালানি তথ্য সেবা";
$page_desc = isset($pump) ? htmlspecialchars($pump['address']) . " এর বর্তমান তেলের অবস্থা জানুন এবং আপনার মতামত দিন।" : $site_desc;

// সোশ্যাল মিডিয়ায় শেয়ার করার জন্য ডাইনামিক ইমেজের ফুল লিংক (Absolute URL) তৈরি করা
$base_url = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$base_url = rtrim($base_url, '/\\');
$current_url = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$og_image_url = isset($pump) && !empty($pump['image_path']) ? $base_url . '/' . htmlspecialchars($pump['image_path']) : $base_url . '/og-image.jpg';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <meta name="description" content="<?= $page_desc ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $current_url ?>">
    <meta property="og:title" content="<?= $page_title ?>">
    <meta property="og:description" content="<?= $page_desc ?>">
    <meta property="og:image" content="<?= $og_image_url ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $current_url ?>">
    <meta property="twitter:title" content="<?= $page_title ?>">
    <meta property="twitter:description" content="<?= $page_desc ?>">
    <meta property="twitter:image" content="<?= $og_image_url ?>">

    <!-- বাংলা ফন্টের জন্য Google Fonts যুক্ত করা হলো -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <div class="nav-icon"><i class="fa-solid fa-gas-pump"></i></div>
        <div>
            <h1><?= htmlspecialchars($settings['site_name'] ?? 'তেল দিচ্ছে?') ?></h1>
            <p><?= htmlspecialchars($settings['site_desc'] ?? 'কোথায় তেল পাওয়া যাচ্ছে জানুন') ?></p>
        </div>
    </div>
    <div class="plus-icon" id="navPlusIcon"><i class="fa-solid fa-plus"></i></div>
</div>