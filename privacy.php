<?php
require 'includes/functions.php';
$config = require 'config.php';
$active = 'privacy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html('Privacy Policy | ' . $config['blog_name']) ?></title>
    <meta name="description" content="Privacy policy for <?= html($config['blog_name']) ?>.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <h1>Privacy Policy</h1>
        <p>This site is a portfolio project and does not collect personal data.</p>
        <p>Any information you submit through contact forms or email is handled as described in the policy above.</p>
        <a href="index.php" class="btn btn-primary mt-4">Back to Blog</a>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
