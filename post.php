<?php
require 'includes/functions.php';
$config = require 'config.php';

$Parsedown = new Parsedown();
if (method_exists($Parsedown, 'setSafeMode')) {
    $Parsedown->setSafeMode(true);
}

$postsDir = 'posts';
$pagesDir = 'pages';
$slug = normalizeSlug($_GET['slug'] ?? '');
$post = $slug ? findMarkdownBySlug($slug, $postsDir) : false;

if ($post) {
    $metadata = $post['metadata'];
    $content = removeFrontMatter($post['content']);
    $htmlContent = $Parsedown->text($content);
    $imageUrl = !empty($metadata['image']) ? $metadata['image'] : $config['default_image'];
    $pageTitle = pageTitle($metadata, $config, 'Blog Post');
    $pageDescription = pageDescription($metadata, $config);
    $readingTime = calculateReadingTime($post['content']);
} else {
    header('Location: ' . rtrim($config['base_path'] ?? '/', '/') . '/404.php', true, 404);
    exit;
}
$base   = rtrim($config['base_path'] ?? '/', '/');
$active = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($pageTitle) ?></title>
    <meta name="description" content="<?= html($pageDescription) ?>">
    <?php $canonicalUrl = rtrim($config['site_url'], '/') . '/post/' . urlencode($slug); ?>
    <link rel="canonical" href="<?= html($canonicalUrl) ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="article">
    <meta property="og:site_name"   content="<?= html($config['blog_name']) ?>">
    <meta property="og:title"       content="<?= html($pageTitle) ?>">
    <meta property="og:description" content="<?= html($pageDescription) ?>">
    <meta property="og:image"       content="<?= html($imageUrl) ?>">
    <meta property="og:url"         content="<?= html($canonicalUrl) ?>">
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= html($pageTitle) ?>">
    <meta name="twitter:description" content="<?= html($pageDescription) ?>">
    <meta name="twitter:image"       content="<?= html($imageUrl) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

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

                <a href="<?= $base ?>/" class="btn btn-primary mt-4">Back to Blog</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
