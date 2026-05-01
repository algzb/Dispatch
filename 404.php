<?php
require 'includes/functions.php';
$config = require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html('Page Not Found | ' . $config['blog_name']) ?></title>
    <meta name="description" content="The page you're looking for could not be found.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php 
    $active = '';
    include 'includes/header.php'; 
    ?>

    <main class="container my-5 text-center">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h1 class="display-1 fw-bold text-danger">404</h1>
                <h2 class="mb-3">Page Not Found</h2>
                <p class="lead mb-4">Sorry, the page you're looking for doesn't exist or has been moved.</p>
                
                <div class="d-flex gap-2 justify-content-center">
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
                </div>

                <hr class="my-5">
                <p class="text-muted">Try browsing recent posts or use the navigation menu above.</p>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
