<?php
require 'includes/functions.php';
$config = require 'config.php';

$postsDir = 'posts';
$pagesDir = 'pages';
$allPosts = sortPostsByDate(loadPosts($postsDir));

$filterTag      = trim($_GET['tag']      ?? '');
$filterCategory = trim($_GET['category'] ?? '');

// ─── Build tag and category maps ──────────────────────────────────────────────

$tagMap      = [];
$categoryMap = [];

foreach ($allPosts as $post) {
    $tags       = !empty($post['metadata']['tags'])       ? array_filter(array_map('trim', explode(',', $post['metadata']['tags'])))       : [];
    $categories = !empty($post['metadata']['categories']) ? array_filter(array_map('trim', explode(',', $post['metadata']['categories']))) : [];

    foreach ($tags as $tag) {
        $tagMap[$tag][] = $post;
    }
    foreach ($categories as $cat) {
        $categoryMap[$cat][] = $post;
    }
}

ksort($tagMap);
ksort($categoryMap);

// ─── Filter posts ─────────────────────────────────────────────────────────────

if ($filterTag !== '') {
    $filteredPosts = $tagMap[$filterTag] ?? [];
    $pageTitle     = 'Tag: ' . $filterTag;
} elseif ($filterCategory !== '') {
    $filteredPosts = $categoryMap[$filterCategory] ?? [];
    $pageTitle     = 'Category: ' . $filterCategory;
} else {
    $filteredPosts = $allPosts;
    $pageTitle     = 'Archive';
}

$active = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($pageTitle . ' | ' . $config['blog_name']) ?></title>
    <meta name="description" content="<?= html($config['tagline']) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?= html($pageTitle) ?></h2>
        <?php if ($filterTag || $filterCategory): ?>
            <a href="archive.php" class="btn btn-sm btn-outline-secondary">← All posts</a>
        <?php endif; ?>
    </div>

    <?php if (!$filterTag && !$filterCategory): ?>
    <!-- ── Tag cloud ──────────────────────────────────────────────────────── -->
    <?php if ($tagMap): ?>
    <div class="card shadow-sm p-4 mb-4">
        <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Tags</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($tagMap as $tag => $posts): ?>
                <a href="archive.php?tag=<?= urlencode($tag) ?>"
                   class="badge bg-secondary text-decoration-none fs-6 fw-normal">
                    <?= html($tag) ?> <span class="opacity-75">(<?= count($posts) ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Category cloud ────────────────────────────────────────────────── -->
    <?php if ($categoryMap): ?>
    <div class="card shadow-sm p-4 mb-4">
        <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Categories</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($categoryMap as $cat => $posts): ?>
                <a href="archive.php?category=<?= urlencode($cat) ?>"
                   class="badge bg-primary text-decoration-none fs-6 fw-normal">
                    <?= html($cat) ?> <span class="opacity-75">(<?= count($posts) ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ── Post list ──────────────────────────────────────────────────────── -->
    <?php if (empty($filteredPosts)): ?>
        <p class="text-muted">No posts found.</p>
    <?php else: ?>
    <?php
    $grouped = [];
    foreach ($filteredPosts as $post) {
        $year = !empty($post['date']) ? date('Y', strtotime($post['date'])) : 'Unknown';
        $grouped[$year][] = $post;
    }
    ?>
    <?php foreach ($grouped as $year => $posts): ?>
        <h5 class="text-muted mt-4 mb-3 border-bottom pb-2"><?= html($year) ?></h5>
        <ul class="list-unstyled">
        <?php foreach ($posts as $post): ?>
            <?php
            $tags       = !empty($post['metadata']['tags'])       ? array_filter(array_map('trim', explode(',', $post['metadata']['tags'])))       : [];
            $categories = !empty($post['metadata']['categories']) ? array_filter(array_map('trim', explode(',', $post['metadata']['categories']))) : [];
            ?>
            <li class="d-flex align-items-start gap-3 py-2 border-bottom">
                <span class="text-muted small text-nowrap" style="min-width:90px"><?= html(formatDate($post['date'], 'M j')) ?></span>
                <div>
                    <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="fw-semibold text-decoration-none text-dark">
                        <?= html($post['title']) ?>
                    </a>
                    <div class="mt-1">
                        <?php foreach ($categories as $cat): ?>
                            <a href="archive.php?category=<?= urlencode($cat) ?>" class="badge bg-primary text-decoration-none me-1" style="font-size:.7rem"><?= html($cat) ?></a>
                        <?php endforeach; ?>
                        <?php foreach ($tags as $tag): ?>
                            <a href="archive.php?tag=<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none me-1" style="font-size:.7rem"><?= html($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
