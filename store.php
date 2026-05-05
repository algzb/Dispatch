<?php
require 'includes/functions.php';
$config = require 'config.php';

$productsDir = 'products';
$pagesDir    = 'pages';
$products    = loadPosts($productsDir);

$base       = rtrim($config['base_path'] ?? '/', '/');
$active     = 'store';
$headTitle  = 'Store | ' . $config['blog_name'];
$headDesc   = $config['tagline'];
$headCanon  = rtrim($config['site_url'], '/') . '/store';

include 'includes/header.php';
?>

<main class="container my-5">
    <h2 class="mb-4">Store</h2>
    <div class="row g-4">

        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-warning">No products yet. Add Markdown files to the <strong>products/</strong> directory.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($products as $product):
            $price   = $product['metadata']['price']    ?? '';
            $currency = $product['metadata']['currency'] ?? 'USD';
            $buyUrl  = $product['metadata']['buy_url']  ?? '';
            $stock   = $product['metadata']['stock']    ?? 'In Stock';
            $inStock = strtolower($stock) !== 'out of stock';
            $image   = $product['image'] ?? $config['default_image'];
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <a href="<?= $base ?>/product/<?= urlencode($product['slug']) ?>">
                    <img src="<?= html($image) ?>" class="card-img-top"
                         style="height:220px;object-fit:cover;" alt="<?= html($product['title']) ?>">
                </a>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">
                        <a href="<?= $base ?>/product/<?= urlencode($product['slug']) ?>"
                           class="text-decoration-none text-dark"><?= html($product['title']) ?></a>
                    </h5>
                    <?php if ($price !== ''): ?>
                        <p class="fw-bold fs-5 mb-2 text-primary"><?= html($currency) ?> <?= html($price) ?></p>
                    <?php endif; ?>
                    <p class="card-text text-muted mb-3 flex-grow-1"><?= html($product['excerpt']) ?></p>
                    <?php if (!$inStock): ?>
                        <span class="badge bg-secondary mb-2 align-self-start">Out of Stock</span>
                    <?php endif; ?>
                    <?php if ($buyUrl && $inStock): ?>
                        <a href="<?= html($buyUrl) ?>" target="_blank" rel="noopener noreferrer"
                           class="btn btn-primary mt-auto">Buy now</a>
                    <?php elseif (!$inStock): ?>
                        <button class="btn btn-secondary mt-auto" disabled>Out of Stock</button>
                    <?php else: ?>
                        <a href="<?= $base ?>/product/<?= urlencode($product['slug']) ?>"
                           class="btn btn-outline-primary mt-auto">View product</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</main>

<?php include 'includes/footer.php'; ?>
