<?php
$active     = $active     ?? 'home';
$pagesDir   = $pagesDir   ?? 'pages';
$base       = $base       ?? rtrim($config['base_path'] ?? '/', '/');
$headTitle  = $headTitle  ?? $config['blog_name'];
$headDesc   = $headDesc   ?? $config['tagline'];
$headCanon  = $headCanon  ?? '';
$headOgType = $headOgType ?? 'website';
$headImage  = $headImage  ?? $config['default_image'];
$headExtra  = $headExtra  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($headTitle) ?></title>
    <meta name="description" content="<?= html($headDesc) ?>">
    <?php if ($headCanon): ?>
    <link rel="canonical" href="<?= html($headCanon) ?>">
    <?php endif; ?>
    <meta property="og:type"        content="<?= html($headOgType) ?>">
    <meta property="og:site_name"   content="<?= html($config['blog_name']) ?>">
    <meta property="og:title"       content="<?= html($headTitle) ?>">
    <meta property="og:description" content="<?= html($headDesc) ?>">
    <meta property="og:image"       content="<?= html($headImage) ?>">
    <?php if ($headCanon): ?>
    <meta property="og:url"         content="<?= html($headCanon) ?>">
    <?php endif; ?>
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= html($headTitle) ?>">
    <meta name="twitter:description" content="<?= html($headDesc) ?>">
    <meta name="twitter:image"       content="<?= html($headImage) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $base ?>/assets/css/style.css" rel="stylesheet">
    <?php if (!empty($config['colors_active'])): ?>
    <?php
        $cp  = $config['color_primary'] ?? '#0d6efd';
        $cd  = $config['color_dark']    ?? '#212529';
        $cpH = hexDarken($cp, 0.85);
        $cdH = hexDarken($cd, 0.85);
        $rgb = hexToRgb($cp);
    ?>
    <style>
        /* ── Custom site colors ─────────────────────────────── */
        .bg-primary                              { background-color: <?= $cp ?> !important; }
        .btn-primary                             { background-color: <?= $cp ?> !important; border-color: <?= $cp ?> !important; }
        .btn-primary:hover,.btn-primary:active   { background-color: <?= $cpH ?> !important; border-color: <?= $cpH ?> !important; }
        .btn-primary:focus                       { box-shadow: 0 0 0 .25rem rgba(<?= $rgb ?>,.35) !important; }
        .btn-outline-primary                     { color: <?= $cp ?> !important; border-color: <?= $cp ?> !important; }
        .btn-outline-primary:hover               { background-color: <?= $cp ?> !important; color: #fff !important; }
        .text-primary                            { color: <?= $cp ?> !important; }
        .link-primary                            { color: <?= $cp ?> !important; }
        .border-primary                          { border-color: <?= $cp ?> !important; }
        .page-item.active .page-link             { background-color: <?= $cp ?> !important; border-color: <?= $cp ?> !important; }
        .bg-dark                                 { background-color: <?= $cd ?> !important; }
        .btn-dark                                { background-color: <?= $cd ?> !important; border-color: <?= $cd ?> !important; }
        .btn-dark:hover                          { background-color: <?= $cdH ?> !important; border-color: <?= $cdH ?> !important; }
        .btn-outline-dark                        { color: <?= $cd ?> !important; border-color: <?= $cd ?> !important; }
        .btn-outline-dark:hover                  { background-color: <?= $cd ?> !important; color: #fff !important; }
    </style>
    <?php endif; ?>
    <?= $headExtra ?>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container px-5">
        <a class="navbar-brand fw-bold" href="<?= $base ?>/"><?= html($config['short_name'] ?? $config['blog_name']) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= $base ?>/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'blog' ? 'active' : '' ?>" href="<?= $base ?>/blog">Blog</a>
                </li>
                <?php if (!empty(glob('products/*.md'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'store' ? 'active' : '' ?>" href="<?= $base ?>/store">Store</a>
                </li>
                <?php endif; ?>
                <?php if (!empty($config['contact_active'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'contact' ? 'active' : '' ?>" href="<?= $base ?>/contact">Contact</a>
                </li>
                <?php endif; ?>
                <?= generateMenu($pagesDir, $active, $config['base_path'] ?? '/') ?>
            </ul>
        </div>
    </div>
</nav>
