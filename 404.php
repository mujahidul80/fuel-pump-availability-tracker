<?php
http_response_code(404);
$site_title = "পেজ পাওয়া যায়নি - ৪MD৪";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>৪০৪ - পেজটি খুঁজে পাওয়া যায়নি | তেল দিচ্ছে?</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .error-container {
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: #ef4444;
            line-height: 1;
            margin-bottom: 15px;
        }
        .error-icon {
            font-size: 3.5rem;
            color: #f59e0b;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 1.6rem;
            margin-bottom: 12px;
            color: #1f2937;
        }
        .error-desc {
            color: #6b7280;
            max-width: 480px;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #2563eb;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <div class="nav-icon"><i class="fa-solid fa-gas-pump"></i></div>
        <div>
            <h1>তেল দিচ্ছে?</h1>
            <p>জ্বালানি তথ্য সেবা</p>
        </div>
    </div>
</div>

<div class="error-container">
    <div class="error-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div class="error-code">৪০৪</div>
    <h2 class="error-title">দুঃখিত! এই পেজটি খুঁজে পাওয়া যায়নি</h2>
    <p class="error-desc">আপনি যে লিঙ্কটিতে প্রবেশ করতে চেয়েছেন তা হয়তো পরিবর্তিত হয়েছে অথবা পাম্পের তথ্যটি মুছে ফেলা হয়েছে।</p>
    <a href="./" class="back-btn"><i class="fa-solid fa-house"></i> হোমপেজে ফিরে যান</a>
</div>

</body>
</html>
