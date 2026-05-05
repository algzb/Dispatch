<?php
require_once __DIR__ . '/includes/functions.php';
$config  = require __DIR__ . '/config.php';
$siteUrl = rtrim($config['site_url'], '/');

$posts = sortPostsByDate(loadPosts(__DIR__ . '/posts'));
$pages = loadPosts(__DIR__ . '/pages');

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc><?= htmlspecialchars($siteUrl . '/', ENT_XML1) ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <?php foreach ($posts as $post): ?>
    <url>
        <loc><?= htmlspecialchars($siteUrl . '/post/' . urlencode($post['slug']), ENT_XML1) ?></loc>
        <?php if (!empty($post['date'])): ?>
        <lastmod><?= htmlspecialchars($post['date'], ENT_XML1) ?></lastmod>
        <?php endif; ?>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <?php foreach ($pages as $page): ?>
    <url>
        <loc><?= htmlspecialchars($siteUrl . '/page/' . urlencode($page['slug']), ENT_XML1) ?></loc>
        <?php if (!empty($page['date'])): ?>
        <lastmod><?= htmlspecialchars($page['date'], ENT_XML1) ?></lastmod>
        <?php endif; ?>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>

</urlset>
