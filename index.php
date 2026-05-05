<?php
require 'includes/functions.php';
$config = require 'config.php';

$postsDir    = 'posts';
$pagesDir    = 'pages';
$allPosts    = sortPostsByDate(loadPosts($postsDir));
$latestPosts = array_slice($allPosts, 0, 3);

$base       = rtrim($config['base_path'] ?? '/', '/');
$active     = 'home';
$headTitle  = $config['blog_name'];
$headDesc   = $config['tagline'];
$headCanon  = rtrim($config['site_url'], '/') . '/';
$headImage  = $config['default_image'];

include 'includes/header.php';
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<?php if (!empty($config['hero_active'])): ?>
<header class="bg-dark py-5">
    <div class="container px-5">
        <div class="row gx-5 align-items-center justify-content-center">
            <div class="col-lg-8 col-xl-7 col-xxl-6">
                <div class="my-5 text-center text-xl-start">
                    <?php if (!empty($config['hero_title'])): ?>
                        <h1 class="display-5 fw-bolder text-white mb-2"><?= html($config['hero_title']) ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($config['hero_description'])): ?>
                        <p class="lead fw-normal text-white-50 mb-4"><?= html($config['hero_description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($config['hero_button_text'])): ?>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center justify-content-xl-start">
                        <a class="btn btn-primary btn-lg px-4 me-sm-3"
                           href="<?= html($config['hero_button_url'] ?: ($base . '/#articles')) ?>">
                            <?= html($config['hero_button_text']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($config['hero_image'])): ?>
            <div class="col-xl-5 col-xxl-6 d-none d-xl-block text-center">
                <img class="img-fluid rounded-3 my-5"
                     src="<?= html($config['hero_image']) ?>"
                     alt="<?= html($config['hero_title'] ?? '') ?>">
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>

<!-- ── Features ──────────────────────────────────────────────────────────────── -->
<?php if (!empty($config['features_active'])): ?>
<section class="py-5" id="features">
    <div class="container px-5 my-5">
        <div class="row gx-5">
            <?php if (!empty($config['features_heading'])): ?>
            <div class="col-lg-4 mb-5 mb-lg-0">
                <h2 class="fw-bolder mb-0"><?= html($config['features_heading']) ?></h2>
            </div>
            <div class="col-lg-8">
            <?php else: ?>
            <div class="col-12">
            <?php endif; ?>
                <div class="row gx-5 row-cols-1 row-cols-md-2">
                    <?php for ($i = 1; $i <= 4; $i++):
                        $fIcon  = $config["feature_{$i}_icon"]  ?? '';
                        $fTitle = $config["feature_{$i}_title"] ?? '';
                        $fText  = $config["feature_{$i}_text"]  ?? '';
                        if (!$fTitle) continue;
                    ?>
                    <div class="col mb-5 h-100">
                        <?php if ($fIcon): ?>
                        <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3">
                            <i class="bi <?= html($fIcon) ?>"></i>
                        </div>
                        <?php endif; ?>
                        <h2 class="h5"><?= html($fTitle) ?></h2>
                        <p class="mb-0"><?= html($fText) ?></p>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── Testimonial ───────────────────────────────────────────────────────────── -->
<?php if (!empty($config['testimonial_active'])): ?>
<div class="py-5 bg-light">
    <div class="container px-5 my-5">
        <div class="row gx-5 justify-content-center">
            <div class="col-lg-10 col-xl-7">
                <div class="text-center">
                    <div class="fs-4 mb-4 fst-italic">"<?= html($config['testimonial_quote']) ?>"</div>
                    <div class="d-flex align-items-center justify-content-center">
                        <?php if (!empty($config['testimonial_avatar'])): ?>
                        <img class="rounded-circle me-3"
                             src="<?= html($config['testimonial_avatar']) ?>"
                             style="width:40px;height:40px;object-fit:cover;" alt="">
                        <?php endif; ?>
                        <div class="fw-bold">
                            <?= html($config['testimonial_author'] ?? '') ?>
                            <?php if (!empty($config['testimonial_role'])): ?>
                                <span class="fw-bold text-primary mx-1">/</span>
                                <?= html($config['testimonial_role']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Latest articles ───────────────────────────────────────────────────────── -->
<?php if (!empty($config['articles_active'])): ?>
<section class="py-5" id="articles">
    <div class="container px-5 my-5">

        <div class="row gx-5 justify-content-center">
            <div class="col-lg-8 col-xl-6 text-center">
                <h2 class="fw-bolder"><?= html($config['articles_heading'] ?? 'Latest from us') ?></h2>
                <?php if (!empty($config['articles_subtitle'])): ?>
                    <p class="lead fw-normal text-muted mb-5"><?= html($config['articles_subtitle']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($latestPosts)): ?>
            <div class="alert alert-warning">No articles yet. Add Markdown files to the <strong>posts/</strong> directory.</div>
        <?php else: ?>
        <div class="row gx-5">
            <?php foreach ($latestPosts as $post):
                $cats     = !empty($post['metadata']['categories'])
                    ? array_filter(array_map('trim', explode(',', $post['metadata']['categories'])))
                    : [];
                $firstCat = reset($cats) ?: '';
                $image    = $post['image'] ?? $config['default_image'];
            ?>
            <div class="col-lg-4 mb-5">
                <div class="card h-100 shadow border-0">
                    <img class="card-img-top" src="<?= html($image) ?>"
                         style="height:200px;object-fit:cover;"
                         alt="<?= html($post['title']) ?>">
                    <div class="card-body p-4">
                        <?php if ($firstCat): ?>
                            <div class="badge bg-primary bg-gradient rounded-pill mb-2"><?= html($firstCat) ?></div>
                        <?php endif; ?>
                        <a class="text-decoration-none link-dark stretched-link"
                           href="<?= $base ?>/post/<?= urlencode($post['slug']) ?>">
                            <h5 class="card-title mb-3"><?= html($post['title']) ?></h5>
                        </a>
                        <p class="card-text mb-0"><?= html($post['excerpt']) ?></p>
                    </div>
                    <div class="card-footer p-4 pt-0 bg-transparent border-top-0">
                        <div class="small text-muted">
                            <?= html($post['formatted_date']) ?> &middot; <?= calculateReadingTime($post['content']) ?> min read
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── CTA ────────────────────────────────────────────────────────────── -->
        <?php if (!empty($config['cta_active'])): ?>
        <aside class="bg-primary bg-gradient rounded-3 p-4 p-sm-5 mt-5">
            <div class="d-flex align-items-center justify-content-between flex-column flex-xl-row text-center text-xl-start">
                <div class="mb-4 mb-xl-0">
                    <div class="fs-3 fw-bold text-white"><?= html($config['cta_title']) ?></div>
                    <?php if (!empty($config['cta_text'])): ?>
                        <div class="text-white-50"><?= html($config['cta_text']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($config['cta_button_text'])): ?>
                <div class="ms-xl-4">
                    <a class="btn btn-outline-light btn-lg px-4"
                       href="<?= html($config['cta_button_url'] ?: ($base . '/')) ?>">
                        <?= html($config['cta_button_text']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </aside>
        <?php endif; ?>

        <?php if (count($allPosts) > 3): ?>
        <div class="text-center mt-5">
            <a href="<?= $base ?>/blog" class="btn btn-outline-dark btn-lg px-5">View all articles</a>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
