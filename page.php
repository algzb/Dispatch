<?php
require 'includes/functions.php';
$config = require 'config.php';

$Parsedown = new Parsedown();
if (method_exists($Parsedown, 'setSafeMode')) {
    $Parsedown->setSafeMode(true);
}

$pagesDir = 'pages';
$slug     = normalizeSlug($_GET['slug'] ?? '');
$page     = $slug ? findMarkdownBySlug($slug, $pagesDir) : false;

if ($page) {
    $metadata    = $page['metadata'];
    $content     = removeFrontMatter($page['content']);
    $htmlContent = $Parsedown->text($content);
    $imageUrl    = !empty($metadata['image']) ? $metadata['image'] : $config['default_image'];
} else {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$base       = rtrim($config['base_path'] ?? '/', '/');
$active     = $slug;
$headTitle  = pageTitle($metadata, $config, 'Page');
$headDesc   = pageDescription($metadata, $config);
$headCanon  = rtrim($config['site_url'], '/') . '/page/' . urlencode($slug);
$headImage  = $imageUrl;

include 'includes/header.php';
?>

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
                <a href="<?= $base ?>/" class="btn btn-outline-secondary mt-4">← Back</a>
            </div>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
