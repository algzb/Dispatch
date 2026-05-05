<?php
require 'includes/functions.php';
$config = require 'config.php';

$Parsedown = new Parsedown();
if (method_exists($Parsedown, 'setSafeMode')) {
    $Parsedown->setSafeMode(true);
}

$postsDir = 'posts';
$pagesDir = 'pages';
$slug     = normalizeSlug($_GET['slug'] ?? '');
$post     = $slug ? findMarkdownBySlug($slug, $postsDir) : false;

if ($post) {
    $metadata    = $post['metadata'];
    $content     = removeFrontMatter($post['content']);
    $htmlContent = $Parsedown->text($content);
    $imageUrl    = !empty($metadata['image']) ? $metadata['image'] : $config['default_image'];
    $readingTime = calculateReadingTime($post['content']);
} else {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$base       = rtrim($config['base_path'] ?? '/', '/');
$active     = '';
$headTitle  = pageTitle($metadata, $config, 'Article');
$headDesc   = pageDescription($metadata, $config);
$headCanon  = rtrim($config['site_url'], '/') . '/post/' . urlencode($slug);
$headOgType = 'article';
$headImage  = $imageUrl;

include 'includes/header.php';
?>

    <div class="hero-banner" style="background-image: url('<?= html($imageUrl) ?>');">
        <div class="hero-content">
            <h1><?= html($metadata['title'] ?? 'Untitled') ?></h1>
            <p><?= html(formatDate($metadata['date'] ?? '')) ?> • <?= $readingTime ?> min read</p>
        </div>
    </div>

    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <?= $htmlContent ?>

                <?php
                $tags       = !empty($metadata['tags'])       ? array_filter(array_map('trim', explode(',', $metadata['tags'])))       : [];
                $categories = !empty($metadata['categories']) ? array_filter(array_map('trim', explode(',', $metadata['categories']))) : [];
                ?>
                <?php if ($tags || $categories): ?>
                <div class="mt-5 pt-4 border-top">
                    <?php if ($categories): ?>
                    <span class="text-muted small me-2">Categories:</span>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= $base ?>/category/<?= urlencode($cat) ?>" class="badge bg-primary text-decoration-none me-1"><?= html($cat) ?></a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($tags): ?>
                    <span class="text-muted small me-2 <?= $categories ? 'ms-3' : '' ?>">Tags:</span>
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?= $base ?>/tag/<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none me-1"><?= html($tag) ?></a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <a href="<?= $base ?>/" class="btn btn-outline-secondary mt-4">← Back</a>
            </div>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
