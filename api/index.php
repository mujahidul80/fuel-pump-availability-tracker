<?php
// Vercel Serverless Entry Point

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// 1. Static asset handling (if bypassed by Vercel edge)
if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|webp|svg|css|js|txt|xml)$/', $uri)) {
    $filePath = realpath(__DIR__ . '/..' . $uri);
    if ($filePath && file_exists($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'txt'  => 'text/plain',
            'xml'  => 'application/xml',
            'webp' => 'image/webp',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'ico'  => 'image/x-icon',
            'svg'  => 'image/svg+xml'
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        readfile($filePath);
        exit;
    }
}

// 2. Route handling
if ($uri === '/' || $uri === '/index' || $uri === '/index.php') {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../index.php';
} elseif ($uri === '/details' || $uri === '/details.php') {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../details.php';
} elseif ($uri === '/admin' || $uri === '/admin/index' || $uri === '/admin/index.php') {
    chdir(__DIR__ . '/../admin');
    require __DIR__ . '/../admin/index.php';
} elseif ($uri === '/install' || $uri === '/install.php') {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../install.php';
} elseif ($uri === '/setup' || $uri === '/setup.php') {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../setup.php';
} elseif ($uri === '/sitemap' || $uri === '/sitemap.xml' || $uri === '/sitemap.php') {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../sitemap.php';
} else {
    $directFile = realpath(__DIR__ . '/..' . $uri);
    if ($directFile && file_exists($directFile) && !is_dir($directFile)) {
        chdir(dirname($directFile));
        require $directFile;
    } elseif (file_exists(__DIR__ . '/..' . $uri . '.php')) {
        chdir(__DIR__ . '/..');
        require __DIR__ . '/..' . $uri . '.php';
    } else {
        http_response_code(404);
        chdir(__DIR__ . '/..');
        require __DIR__ . '/../404.php';
    }
}
