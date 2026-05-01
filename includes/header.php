<?php
$active = $active ?? 'home';
$pagesDir = $pagesDir ?? 'pages';
?>
<header class="site-header bg-primary text-white py-4 mb-4">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h2 mb-1"><?= html($config['blog_name']) ?></h1>
                <p class="lead mb-0"><?= html($config['tagline']) ?></p>
            </div>
        </div>
    </div>
</header>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><?= html($config['short_name'] ?? $config['blog_name']) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="index.php">Home</a>
                </li>
                <?= generateMenu($pagesDir, $active) ?>
            </ul>
        </div>
    </div>
</nav>
