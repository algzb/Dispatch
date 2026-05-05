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

$perPage     = 20;
$totalPosts  = count($filteredPosts);
$totalPages  = (int) ceil($totalPosts / $perPage);
$currentPage = max(1, min($totalPages ?: 1, (int) ($_GET['page'] ?? 1)));
$pagedPosts  = array_slice($filteredPosts, ($currentPage - 1) * $perPage, $perPage);

$base      = rtrim($config['base_path'] ?? '/', '/');
$active    = '';
$headTitle = $pageTitle . ' | ' . $config['blog_name'];
$headDesc  = $config['tagline'];

include 'includes/header.php';
?>

<main class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?= html($pageTitle) ?> <span class="text-muted fw-normal fs-5">(<?= $totalPosts ?>)</span></h2>
        <?php if ($filterTag || $filterCategory): ?>
            <a href="<?= $base ?>/archive" class="btn btn-sm btn-outline-secondary">← All posts</a>
        <?php endif; ?>
    </div>

    <?php if (!$filterTag && !$filterCategory): ?>
    <!-- ── Tag cloud ──────────────────────────────────────────────────────── -->
    <?php if ($tagMap): ?>
    <div class="card shadow-sm p-4 mb-4">
        <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Tags</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($tagMap as $tag => $posts): ?>
                <a href="<?= $base ?>/tag/<?= urlencode($tag) ?>"
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
                <a href="<?= $base ?>/category/<?= urlencode($cat) ?>"
                   class="badge bg-primary text-decoration-none fs-6 fw-normal">
                    <?= html($cat) ?> <span class="opacity-75">(<?= count($posts) ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ── Post list ──────────────────────────────────────────────────────── -->
    <?php if (empty($pagedPosts)): ?>
        <p class="text-muted">No posts found.</p>
    <?php else: ?>
    <?php
    $grouped = [];
    foreach ($pagedPosts as $post) {
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
                    <a href="<?= $base ?>/post/<?= urlencode($post['slug']) ?>" class="fw-semibold text-decoration-none text-dark">
                        <?= html($post['title']) ?>
                    </a>
                    <div class="mt-1">
                        <?php foreach ($categories as $cat): ?>
                            <a href="<?= $base ?>/category/<?= urlencode($cat) ?>" class="badge bg-primary text-decoration-none me-1" style="font-size:.7rem"><?= html($cat) ?></a>
                        <?php endforeach; ?>
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?= $base ?>/tag/<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none me-1" style="font-size:.7rem"><?= html($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
    <?php endif; ?>

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
