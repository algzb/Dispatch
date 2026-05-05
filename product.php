<?php
require 'includes/functions.php';
$config = require 'config.php';

$Parsedown = new Parsedown();
if (method_exists($Parsedown, 'setSafeMode')) {
    $Parsedown->setSafeMode(true);
}

$productsDir = 'products';
$pagesDir    = 'pages';
$slug        = normalizeSlug($_GET['slug'] ?? '');
$product     = $slug ? findMarkdownBySlug($slug, $productsDir) : false;

if ($product) {
    $metadata    = $product['metadata'];
    $content     = removeFrontMatter($product['content']);
    $htmlContent = $Parsedown->text($content);
    $imageUrl    = !empty($metadata['image']) ? $metadata['image'] : $config['default_image'];
    $price       = $metadata['price']    ?? '';
    $currency    = $metadata['currency'] ?? 'USD';
    $buyUrl      = $metadata['buy_url']  ?? '';
    $stock       = $metadata['stock']    ?? 'In Stock';
    $inStock     = strtolower($stock) !== 'out of stock';
} else {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$base       = rtrim($config['base_path'] ?? '/', '/');
$active     = 'store';
$headTitle  = pageTitle($metadata, $config, 'Product');
$headDesc   = pageDescription($metadata, $config);
$headCanon  = rtrim($config['site_url'], '/') . '/product/' . urlencode($slug);
$headOgType = 'product';
$headImage  = $imageUrl;

include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row g-5">

        <!-- Image + description -->
        <div class="col-12 col-md-8">
            <img src="<?= html($imageUrl) ?>" alt="<?= html($metadata['title'] ?? '') ?>"
                 class="img-fluid rounded mb-4 w-100" style="object-fit:cover;max-height:480px;">
            <?= $htmlContent ?>
            <a href="<?= $base ?>/store" class="btn btn-outline-secondary mt-4">← Back to Store</a>
        </div>

        <!-- Buy box -->
        <div class="col-12 col-md-4">
            <div class="card shadow-sm p-4 sticky-top" style="top:1.5rem">
                <h4 class="mb-1"><?= html($metadata['title'] ?? '') ?></h4>
                <?php if ($price !== ''): ?>
                    <p class="fw-bold fs-3 text-primary mb-3"><?= html($currency) ?> <?= html($price) ?></p>
                <?php endif; ?>

                <?php if (!$inStock): ?>
                    <span class="badge bg-secondary mb-3">Out of Stock</span>
                    <button class="btn btn-secondary w-100" disabled>Out of Stock</button>
                <?php elseif ($buyUrl): ?>
                    <a href="<?= html($buyUrl) ?>" target="_blank" rel="noopener noreferrer"
                       class="btn btn-primary btn-lg w-100">Buy now</a>
                <?php else: ?>
                    <p class="text-muted small">No purchase link configured.</p>
                <?php endif; ?>

                <?php if (!empty($metadata['excerpt'])): ?>
                    <p class="text-muted small mt-3 mb-0"><?= html($metadata['excerpt']) ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>
