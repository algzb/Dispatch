<?php
require 'includes/functions.php';
$config = require 'config.php';

$Parsedown = new Parsedown();
if (method_exists($Parsedown, 'setSafeMode')) {
    $Parsedown->setSafeMode(true);
}

$pagesDir = 'pages';
$slug = normalizeSlug($_GET['slug'] ?? '');
$page = $slug ? findMarkdownBySlug($slug, $pagesDir) : false;

if ($page) {
    $metadata = $page['metadata'];
    $content = removeFrontMatter($page['content']);
    $htmlContent = $Parsedown->text($content);
    $imageUrl = !empty($metadata['image']) ? $metadata['image'] : $config['default_image'];
    $pageTitle = pageTitle($metadata, $config, 'Page');
    $pageDescription = pageDescription($metadata, $config);
} else {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$base   = rtrim($config['base_path'] ?? '/', '/');
$active = $slug;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($pageTitle) ?></title>
    <meta name="description" content="<?= html($pageDescription) ?>">
    <?php $canonicalUrl = rtrim($config['site_url'], '/') . '/page/' . urlencode($slug); ?>
    <link rel="canonical" href="<?= html($canonicalUrl) ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="website">
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
            <p><?= html(formatDate($metadata['date'] ?? '')) ?></p>
        </div>
    </div>

    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <?= $htmlContent ?>
                <a href="<?= $base ?>/" class="btn btn-primary mt-4">Back to Blog</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
