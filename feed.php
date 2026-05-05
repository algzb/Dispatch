<?php
require_once __DIR__ . '/includes/functions.php';
$config = require __DIR__ . '/config.php';

$Parsedown = new Parsedown();
if (method_exists($Parsedown, 'setSafeMode')) {
    $Parsedown->setSafeMode(true);
}

$posts   = sortPostsByDate(loadPosts(__DIR__ . '/posts'));
$siteUrl = rtrim($config['site_url'], '/');
$feedUrl = $siteUrl . '/feed.xml';

header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title><?= htmlspecialchars($config['blog_name'], ENT_XML1) ?></title>
    <link><?= htmlspecialchars($siteUrl, ENT_XML1) ?></link>
    <description><?= htmlspecialchars($config['tagline'], ENT_XML1) ?></description>
    <language>en</language>
    <generator>Dispatch</generator>
    <atom:link href="<?= htmlspecialchars($feedUrl, ENT_XML1) ?>" rel="self" type="application/rss+xml"/>
<?php foreach (array_slice($posts, 0, 20) as $post):
    $postUrl     = $siteUrl . '/post/' . urlencode($post['slug']);
    $pubDate     = !empty($post['date']) ? date(DATE_RSS, strtotime($post['date'])) : date(DATE_RSS);
    $image       = !empty($post['image']) ? $post['image'] : ($config['default_image'] ?? '');
    $fullHtml    = $Parsedown->text(removeFrontMatter($post['content']));
?>
    <item>
        <title><?= htmlspecialchars($post['title'], ENT_XML1) ?></title>
        <link><?= htmlspecialchars($postUrl, ENT_XML1) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($postUrl, ENT_XML1) ?></guid>
        <description><?= htmlspecialchars($post['excerpt'], ENT_XML1) ?></description>
        <content:encoded><![CDATA[<?= $fullHtml ?>]]></content:encoded>
        <pubDate><?= $pubDate ?></pubDate>
        <?php if ($image): ?>
        <media:thumbnail url="<?= htmlspecialchars($image, ENT_XML1) ?>"/>
        <?php endif; ?>
    </item>
<?php endforeach; ?>
</channel>
</rss>
