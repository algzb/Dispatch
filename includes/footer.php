<footer class="site-footer bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-2"><?= html($config['footer_text']) ?></p>
        <p class="mb-0 small">
            <?php $base = rtrim($config['base_path'] ?? '/', '/'); ?>
            <a class="text-decoration-underline text-white" href="<?= $base ?>/archive">Archive</a>
            &nbsp;|&nbsp;
            <a class="text-decoration-underline text-white" href="<?= $base ?>/feed.xml">RSS</a>
            &nbsp;|&nbsp;
            <a class="text-decoration-underline text-white" href="<?= $base ?>/<?= html(ltrim($config['privacy_policy_link'], '/')) ?>">Privacy Policy</a>
            &nbsp;|&nbsp;
            <a class="text-decoration-underline text-white" href="<?= $base ?>/<?= html(ltrim($config['terms_service_link'], '/')) ?>">Terms of Service</a>
        </p>
    </div>
</footer>
