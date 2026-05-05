<?php
require 'includes/functions.php';
$config = require 'config.php';

$postsDir  = 'posts';
$pagesDir  = 'pages';
$allPosts  = sortPostsByDate(loadPosts($postsDir));

$perPage     = 9;
$totalPosts  = count($allPosts);
$totalPages  = (int) ceil($totalPosts / $perPage);
$currentPage = max(1, min($totalPages ?: 1, (int) ($_GET['page'] ?? 1)));
$pagedPosts  = array_slice($allPosts, ($currentPage - 1) * $perPage, $perPage);

$base      = rtrim($config['base_path'] ?? '/', '/');
$active    = 'blog';
$headTitle = 'Blog | ' . $config['blog_name'];
$headDesc  = $config['tagline'];
$headCanon = rtrim($config['site_url'], '/') . '/blog' . ($currentPage > 1 ? '?page=' . $currentPage : '');

include 'includes/header.php';
?>

<div class="bg-dark py-5">
    <div class="container px-5 text-center">
        <h1 class="display-6 fw-bolder text-white mb-2">Blog</h1>
        <p class="lead text-white-50 mb-0"><?= html($config['tagline']) ?></p>
    </div>
</div>

<section class="py-5">
    <div class="container px-5 my-4">

        <?php if (empty($pagedPosts)): ?>
            <div class="alert alert-warning">No posts yet. Add Markdown files to the <strong>posts/</strong> directory.</div>
        <?php else: ?>

        <div class="row gx-5 gy-4 row-cols-1 row-cols-md-2 row-cols-lg-3">
            <?php foreach ($pagedPosts as $post):
                $cats     = !empty($post['metadata']['categories'])
                    ? array_filter(array_map('trim', explode(',', $post['metadata']['categories'])))
                    : [];
                $firstCat = reset($cats) ?: '';
                $image    = $post['image'] ?? $config['default_image'];
            ?>
            <div class="col">
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

        <?php if ($totalPages > 1): ?>
        <nav class="mt-5" aria-label="Blog pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $base ?>/blog<?= $currentPage > 2 ? '?page=' . ($currentPage - 1) : '' ?>">
                        &laquo; Previous
                    </a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $base ?>/blog<?= $p > 1 ? '?page=' . $p : '' ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $base ?>/blog?page=<?= $currentPage + 1 ?>">
                        Next &raquo;
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>
