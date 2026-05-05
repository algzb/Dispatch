<?php
require_once 'includes/functions.php';
$config = require 'config.php';

$base      = rtrim($config['base_path'] ?? '/', '/');
$active    = '';
$headTitle = 'Page Not Found | ' . $config['blog_name'];
$headDesc  = "The page you're looking for could not be found.";

include 'includes/header.php';
?>

    <main class="container my-5 text-center">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h1 class="display-1 fw-bold text-danger">404</h1>
                <h2 class="mb-3">Page Not Found</h2>
                <p class="lead mb-4">Sorry, the page you're looking for doesn't exist or has been moved.</p>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?= $base ?>/" class="btn btn-primary">Back to Home</a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
                </div>

                <hr class="my-5">
                <p class="text-muted">Try browsing recent posts or use the navigation menu above.</p>
            </div>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
