<footer class="bg-dark py-4 mt-auto">
    <div class="container px-5">
        <div class="row align-items-center justify-content-between flex-column flex-sm-row">
            <div class="col-auto">
                <div class="small text-white m-0"><?= html($config['footer_text']) ?></div>
            </div>
            <div class="col-auto">
                <?php $base = rtrim($config['base_path'] ?? '/', '/'); ?>
                <a class="link-light small" href="<?= $base ?>/archive">Archive</a>
                <span class="text-white mx-1">&middot;</span>
                <a class="link-light small" href="<?= $base ?>/feed.xml">RSS</a>
                <span class="text-white mx-1">&middot;</span>
                <a class="link-light small" href="<?= $base ?>/<?= html(ltrim($config['privacy_policy_link'], '/')) ?>">Privacy</a>
                <span class="text-white mx-1">&middot;</span>
                <a class="link-light small" href="<?= $base ?>/<?= html(ltrim($config['terms_service_link'], '/')) ?>">Terms</a>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>
</html>
