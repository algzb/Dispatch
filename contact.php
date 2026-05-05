<?php
require 'includes/functions.php';
require 'includes/mailer.php';
$config = require 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Generate a CSRF token specific to the contact form
if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$base     = rtrim($config['base_path'] ?? '/', '/');
$pagesDir = 'pages';
$active   = 'contact';

$errors = [];
$sent   = !empty($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['contact_csrf'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }

    // Honeypot — bots fill this hidden field; humans leave it blank
    if (!empty($_POST['_gotcha'])) {
        header('Location: ' . $base . '/contact?sent=1');
        exit;
    }

    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '')                                        $errors[] = 'Your name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'A valid email address is required.';
    if ($message === '')                                     $errors[] = 'A message is required.';

    // reCAPTCHA verification (only when keys are configured)
    $siteKey   = $config['recaptcha_site_key']   ?? '';
    $secretKey = $config['recaptcha_secret_key'] ?? '';
    if ($secretKey !== '') {
        if (!verifyRecaptcha($secretKey, $_POST['g-recaptcha-response'] ?? '')) {
            $errors[] = 'Please complete the CAPTCHA verification.';
        }
    }

    // Rate limit: one submission per 60 seconds
    $lastSubmit = $_SESSION['contact_last_submit'] ?? 0;
    if (time() - $lastSubmit < 60) {
        $errors[] = 'Please wait a moment before submitting again.';
    }

    if (empty($errors)) {
        $to       = $config['contact_email'] ?? '';
        $fromName = !empty($config['mail_from_name']) ? $config['mail_from_name'] : $config['blog_name'];
        $subj     = $subject ?: 'Contact form submission from ' . $config['blog_name'];
        $body     = "Name:    {$name}\nEmail:   {$email}\n\nMessage:\n{$message}";

        if (sendMail($to, $subj, $body, $email, $fromName)) {
            $_SESSION['contact_last_submit'] = time();
            // Regenerate token after use
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
            header('Location: ' . $base . '/contact?sent=1');
            exit;
        }

        $errors[] = 'Could not send your message. Please try again later.';
    }
}

$headTitle = ($config['contact_title'] ?? 'Contact') . ' | ' . $config['blog_name'];
$headDesc  = $config['contact_subtitle'] ?? $config['tagline'];
$headCanon = rtrim($config['site_url'], '/') . '/contact';
$siteKey   = $config['recaptcha_site_key'] ?? '';
$headExtra = $siteKey !== ''
    ? '<script src="https://www.google.com/recaptcha/api.js" async defer></script>'
    : '';

include 'includes/header.php';
?>

<div class="bg-dark py-5">
    <div class="container px-5 text-center">
        <h1 class="display-6 fw-bolder text-white mb-2"><?= html($config['contact_title'] ?? 'Contact Us') ?></h1>
        <?php if (!empty($config['contact_subtitle'])): ?>
            <p class="lead text-white-50 mb-0"><?= html($config['contact_subtitle']) ?></p>
        <?php endif; ?>
    </div>
</div>

<section class="py-5">
    <div class="container px-5 my-4">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <?php if ($sent): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= html($config['contact_success'] ?? 'Thank you! Your message has been sent.') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $e): ?>
                            <li><?= html($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="card shadow border-0 p-4 p-md-5">
                    <form method="POST" action="<?= $base ?>/contact" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= html($_SESSION['contact_csrf']) ?>">
                        <!-- Honeypot: hidden from real users, bots fill it -->
                        <input type="text" name="_gotcha" style="display:none" tabindex="-1" autocomplete="off">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="contactName">Name <span class="text-danger">*</span></label>
                                <input type="text" id="contactName" name="name" class="form-control"
                                       value="<?= html($_POST['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="contactEmail">Email <span class="text-danger">*</span></label>
                                <input type="email" id="contactEmail" name="email" class="form-control"
                                       value="<?= html($_POST['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="contactSubject">Subject</label>
                                <input type="text" id="contactSubject" name="subject" class="form-control"
                                       value="<?= html($_POST['subject'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="contactMessage">Message <span class="text-danger">*</span></label>
                                <textarea id="contactMessage" name="message" class="form-control" rows="6" required><?= html($_POST['message'] ?? '') ?></textarea>
                            </div>
                            <?php if ($siteKey !== ''): ?>
                            <div class="col-12">
                                <div class="g-recaptcha" data-sitekey="<?= html($siteKey) ?>"></div>
                            </div>
                            <?php endif; ?>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg px-5">Send message</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
