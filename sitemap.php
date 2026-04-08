<?php
require_once 'config.php';

// Ensure database connection is available
if (!isset($pdo)) {
    die('Database connection failed.');
}

// Set Content-Type to XML
header('Content-Type: application/xml; charset=utf-8');

// Define the base URL for the sitemap (prefer PUBLIC_URL for canonical links)
$baseUrl = defined('PUBLIC_URL') ? rtrim(PUBLIC_URL, '/') : rtrim(BASE_URL, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc><?php echo $baseUrl; ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/products.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/shipping.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/login.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?php echo $baseUrl; ?>/register.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>

    <!-- Categories -->
    <?php
    try {
        $stmt = $pdo->query("SELECT id, updated_at FROM categories ORDER BY id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastMod = !empty($row['updated_at']) ? date('c', strtotime($row['updated_at'])) : date('c');
            echo "    <url>\n";
            echo "        <loc>" . $baseUrl . "/products.php?category=" . $row['id'] . "</loc>\n";
            echo "        <lastmod>" . $lastMod . "</lastmod>\n";
            echo "        <changefreq>weekly</changefreq>\n";
            echo "        <priority>0.8</priority>\n";
            echo "    </url>\n";
        }
    } catch (PDOException $e) {
        // Silently fail or log error, but don't break XML structure if possible
    }
    ?>

    <!-- Products -->
    <?php
    try {
        $stmt = $pdo->query("SELECT id, updated_at FROM products ORDER BY id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastMod = !empty($row['updated_at']) ? date('c', strtotime($row['updated_at'])) : date('c');
            echo "    <url>\n";
            echo "        <loc>" . $baseUrl . "/product.php?id=" . $row['id'] . "</loc>\n";
            echo "        <lastmod>" . $lastMod . "</lastmod>\n";
            echo "        <changefreq>weekly</changefreq>\n";
            echo "        <priority>0.8</priority>\n";
            echo "    </url>\n";
        }
    } catch (PDOException $e) {
         // Silently fail
    }
    ?>
</urlset>
