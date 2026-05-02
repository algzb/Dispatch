<?php
require 'includes/functions.php';
$config = require 'config.php';

$postsDir = 'posts';
$pagesDir = 'pages';
$posts = sortPostsByDate(loadPosts($postsDir));

$perPage     = 6;
$totalPosts  = count($posts);
$totalPages  = (int) ceil($totalPosts / $perPage);
$currentPage = max(1, min($totalPages ?: 1, (int) ($_GET['page'] ?? 1)));
$posts       = array_slice($posts, ($currentPage - 1) * $perPage, $perPage);

$active = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($config['blog_name']) ?></title>
    <meta name="description" content="<?= html($config['tagline']) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <div class="row g-4">
            <?php if (empty($posts)): ?>
                <div class="col-12">
                    <div class="alert alert-warning">No posts were found. Add markdown files to the <strong>posts/</strong> directory.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($posts as $post): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <a href="post.php?slug=<?= urlencode($post['slug']) ?>">
                            <img src="<?= html($post['image'] ?? $config['default_image']) ?>" class="card-img-top" alt="<?= html($post['title']) ?>">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="text-decoration-none text-dark"><?= html($post['title']) ?></a></h5>
                            <p class="card-text text-muted mb-3"><?= html($post['formatted_date']) ?></p>
                            <p class="card-text mb-4"><?= html($post['excerpt']) ?></p>
                            <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-primary mt-auto">Read article</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-5" aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $currentPage - 1 ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $currentPage + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
