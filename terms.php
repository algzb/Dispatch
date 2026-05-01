<?php
require 'includes/functions.php';
$config = require 'config.php';
$active = 'terms';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html('Terms of Service | ' . $config['blog_name']) ?></title>
    <meta name="description" content="Terms of service for <?= html($config['blog_name']) ?>.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <h1>Terms of Service</h1>
        <p>This portfolio blog is provided for demonstration purposes. Use of the site is subject to the terms described here.</p>
        <p>All content is owned by the site author unless otherwise noted.</p>
        <a href="index.php" class="btn btn-primary mt-4">Back to Blog</a>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
