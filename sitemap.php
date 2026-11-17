<?php
header("Content-Type: application/xml; charset=utf-8");

require_once 'db.php';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = "$protocol://$host";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= htmlspecialchars($baseUrl) ?>/</loc>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>
    <?php
    try {
        $stmt = $pdo->query("SELECT id, last_updated FROM pumps ORDER BY last_updated DESC LIMIT 500");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastMod = date('Y-m-d', strtotime($row['last_updated']));
            $url = htmlspecialchars("$baseUrl/details.php?id=" . $row['id']);
            echo "    <url>\n";
            echo "        <loc>$url</loc>\n";
            echo "        <lastmod>$lastMod</lastmod>\n";
            echo "        <changefreq>hourly</changefreq>\n";
            echo "        <priority>0.8</priority>\n";
            echo "    </url>\n";
        }
    } catch (Exception $e) {}
    ?>
</urlset>
