<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
$config = require __DIR__ . '/config.php';

define('POSTS_DIR',    __DIR__ . '/posts');
define('PAGES_DIR',    __DIR__ . '/pages');
define('PRODUCTS_DIR', __DIR__ . '/products');

$base = rtrim($config['base_path'] ?? '/', '/');

// ─── Helpers ──────────────────────────────────────────────────────────────────

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function go(string $url): void {
    header('Location: ' . $url);
    exit;
}

function contentDir(string $type): string {
    if ($type === 'page')    return PAGES_DIR;
    if ($type === 'product') return PRODUCTS_DIR;
    return POSTS_DIR;
}

// basename() prevents path traversal — a filename like '../../config.php' is reduced to 'config.php'.
function safePath(string $type, string $filename): string {
    return contentDir($type) . DIRECTORY_SEPARATOR . basename($filename);
}

function buildFileContent(array $meta, string $body): string {
    $fm = "---\n";
    foreach ($meta as $k => $v) {
        if ($v !== '') $fm .= "$k: $v\n";
    }
    $fm .= "---\n\n";
    return $fm . ltrim($body);
}

function loadItems(string $type): array {
    $items = loadPosts(contentDir($type));
    return $type === 'post' ? sortPostsByDate($items) : $items;
}

function e(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Generate (or retrieve) the session CSRF token. Called lazily so the token is only
// created after a successful login, never on the public login form.
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify the CSRF token submitted with a POST request.
// hash_equals() prevents timing attacks that could leak the token via response time.
function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Request blocked: invalid CSRF token.');
    }
}

// ─── Routing ──────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? '';
$type   = in_array($_GET['type'] ?? '', ['page', 'product']) ? $_GET['type'] : 'post';
$slug   = $_GET['slug'] ?? '';
$flash  = ['type' => '', 'msg' => ''];

if ($action === 'logout') {
    session_destroy();
    go('admin.php');
}

if ($action === 'login' && isPost()) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === ($config['admin_user'] ?? '') && password_verify($pass, $config['admin_pass'] ?? '')) {
        $_SESSION['admin'] = true;
        go('admin.php');
    }
    $flash = ['type' => 'danger', 'msg' => 'Invalid credentials.'];
}

// ─── Login gate ───────────────────────────────────────────────────────────────

if (empty($_SESSION['admin'])) {
    $err = $flash['msg'];
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — <?= e($config['blog_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
<div class="container" style="max-width:380px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-4 text-center fw-bold">Admin Login</h4>
            <?php if ($err): ?>
                <div class="alert alert-danger py-2"><?= e($err) ?></div>
            <?php endif; ?>
            <form method="POST" action="admin.php?action=login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html><?php
    exit;
}

// ─── CRUD actions (authenticated) ─────────────────────────────────────────────

$metaKeys = $type === 'product'
    ? ['title', 'slug', 'price', 'currency', 'buy_url', 'stock', 'image', 'excerpt']
    : ['title', 'date', 'slug', 'categories', 'tags', 'image', 'excerpt'];

if ($action === 'save' && isPost()) {
    verifyCsrf();
    $item = findMarkdownBySlug($slug, contentDir($type));
    if ($item) {
        $meta = [];
        foreach ($metaKeys as $k) {
            $meta[$k] = trim($_POST[$k] ?? '');
        }
        if (empty($meta['image']) && !empty($_SESSION['featured_image_url'])) {
            $meta['image'] = $_SESSION['featured_image_url'];
        }
        unset($_SESSION['featured_image_url']);
        $meta['slug'] = normalizeSlug($meta['slug'] ?: $slug);
        $body = $_POST['body'] ?? '';

        $oldPath = safePath($type, $item['file']);
        file_put_contents($oldPath, buildFileContent(array_filter($meta, fn($v) => $v !== ''), $body));

        if ($meta['slug'] !== $slug) {
            rename($oldPath, safePath($type, $meta['slug'] . '.md'));
        }
        go("admin.php?action=edit&type=$type&slug={$meta['slug']}&saved=1");
    }
}

if ($action === 'create' && isPost()) {
    verifyCsrf();
    $meta = [];
    foreach ($metaKeys as $k) {
        $meta[$k] = trim($_POST[$k] ?? '');
    }
    if (empty($meta['image']) && !empty($_SESSION['featured_image_url'])) {
        $meta['image'] = $_SESSION['featured_image_url'];
    }
    unset($_SESSION['featured_image_url']);
    if ($type !== 'product') {
        $meta['date'] = $meta['date'] ?: date('Y-m-d');
    }
    $meta['slug'] = normalizeSlug($meta['slug'] ?: $meta['title']);
    $body = $_POST['body'] ?? '';

    if (empty($meta['slug'])) {
        $flash = ['type' => 'danger', 'msg' => 'A slug or title is required.'];
    } else {
        $path = safePath($type, $meta['slug'] . '.md');
        if (file_exists($path)) {
            $flash = ['type' => 'danger', 'msg' => "A file with the slug \"{$meta['slug']}\" already exists."];
        } else {
            file_put_contents($path, buildFileContent(array_filter($meta, fn($v) => $v !== ''), $body));
            go("admin.php?action=edit&type=$type&slug={$meta['slug']}&saved=1");
        }
    }
}

if ($action === 'delete' && isPost()) {
    verifyCsrf();
    $item = findMarkdownBySlug($slug, contentDir($type));
    if ($item) {
        unlink(safePath($type, $item['file']));
    }
    go("admin.php?type=$type");
}

// ─── Image upload (returns JSON, exits early) ─────────────────────────────────

define('UPLOADS_DIR', __DIR__ . '/assets/uploads');

if ($action === 'upload' && isPost()) {
    header('Content-Type: application/json');
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['error' => 'Invalid CSRF token.']); exit;
    }
    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Upload failed.']); exit;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        echo json_encode(['error' => 'Only jpg, png, gif, webp allowed.']); exit;
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = date('Ymd-His') . '-' . normalizeSlug(pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
    $dest = UPLOADS_DIR . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['error' => 'Could not save file.']); exit;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base     = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $url      = $base . '/assets/uploads/' . $name;

    $_SESSION['featured_image_url'] = $url;

    echo json_encode(['url' => $url]);
    exit;
}

if ($action === 'delete-media' && isPost()) {
    verifyCsrf();
    $file = basename($_POST['file'] ?? '');
    if ($file) @unlink(UPLOADS_DIR . '/' . $file);
    go('admin.php?action=media');
}

if ($action === 'save-settings' && isPost()) {
    verifyCsrf();
    $configPath = __DIR__ . '/config.php';
    $checkboxKeys = ['hero_active','features_active','testimonial_active','articles_active','cta_active','contact_active','colors_active'];
    $formKeys = ['site_url','blog_name','tagline','short_name','author_name',
                 'footer_text','privacy_policy_link','terms_service_link','default_image',
                 'hero_active','hero_title','hero_description','hero_image','hero_button_text','hero_button_url',
                 'features_active','features_heading',
                 'feature_1_icon','feature_1_title','feature_1_text',
                 'feature_2_icon','feature_2_title','feature_2_text',
                 'feature_3_icon','feature_3_title','feature_3_text',
                 'feature_4_icon','feature_4_title','feature_4_text',
                 'testimonial_active','testimonial_quote','testimonial_author','testimonial_role','testimonial_avatar',
                 'articles_active','articles_heading','articles_subtitle',
                 'cta_active','cta_title','cta_text','cta_button_text','cta_button_url',
                 'contact_active','contact_title','contact_subtitle','contact_email','contact_success','mail_from_name',
                 'recaptcha_site_key','recaptcha_secret_key',
                 'colors_active','color_primary','color_dark',
                 'admin_user','admin_pass'];
    $new = $config;
    foreach ($formKeys as $k) {
        if (in_array($k, $checkboxKeys)) {
            $new[$k] = isset($_POST[$k]) ? '1' : '';
        } elseif ($k === 'admin_pass') {
            $v = trim($_POST[$k] ?? '');
            if ($v !== '') {
                $new[$k] = password_hash($v, PASSWORD_DEFAULT);
            }
        } else {
            $new[$k] = trim($_POST[$k] ?? '');
        }
    }
    file_put_contents($configPath, '<?php' . "\nreturn " . var_export($new, true) . ";\n");
    go('admin.php?action=settings&saved=1');
}

// ─── View data ────────────────────────────────────────────────────────────────

$view     = in_array($action, ['edit', 'new', 'settings', 'media']) ? $action : 'list';
$editItem = null;
$items    = [];

if ($view === 'edit') {
    $editItem = findMarkdownBySlug($slug, contentDir($type));
    if (!$editItem) go("admin.php?type=$type");
}

if ($view === 'list') {
    $items = loadItems($type);
}

if (!empty($_GET['saved'])) {
    $flash = ['type' => 'success', 'msg' => 'Saved successfully.'];
}

$typeLabel       = $type === 'page' ? 'Page' : ($type === 'product' ? 'Product' : 'Post');
$typeLabelPlural = $type === 'page' ? 'Pages' : ($type === 'product' ? 'Products' : 'Posts');

// ─── HTML ─────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — <?= e($config['blog_name']) ?></title>
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <style>
        .sidebar { min-height: calc(100vh - 56px); border-right: 1px solid #dee2e6; }
        .EasyMDEContainer .CodeMirror { min-height: 340px; font-size: 14px; }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand fw-bold"><?= e($config['blog_name']) ?> — Admin</span>
    <div class="d-flex gap-2">
        <a href="<?= $base ?>/" target="_blank" class="btn btn-sm btn-outline-light">View site</a>
        <a href="admin.php?action=logout" class="btn btn-sm btn-danger">Logout</a>
    </div>
</nav>

<div class="container-fluid">
<div class="row">

<!-- Sidebar -->
<div class="col-auto col-md-2 bg-white sidebar py-3 px-2">
    <div class="d-flex flex-column gap-1">
        <a href="admin.php?type=post" class="btn btn-sm <?= $type === 'post' && $view !== 'settings' ? 'btn-primary' : 'btn-outline-secondary' ?>">Posts</a>
        <a href="admin.php?type=page" class="btn btn-sm <?= $type === 'page' && $view !== 'settings' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pages</a>
        <a href="admin.php?type=product" class="btn btn-sm <?= $type === 'product' && $view !== 'settings' ? 'btn-primary' : 'btn-outline-secondary' ?>">Products</a>
        <a href="admin.php?action=media" class="btn btn-sm <?= $view === 'media' ? 'btn-primary' : 'btn-outline-secondary' ?>">Media</a>
        <a href="admin.php?action=settings" class="btn btn-sm <?= $view === 'settings' ? 'btn-primary' : 'btn-outline-secondary' ?>">Settings</a>
        <hr class="my-2">
        <?php if ($view !== 'settings'): ?>
        <a href="admin.php?action=new&type=<?= $type ?>" class="btn btn-sm btn-success">+ New <?= $typeLabel ?></a>
        <?php endif; ?>
    </div>
</div>

<!-- Main -->
<div class="col py-4 px-4">

<?php if ($flash['msg']): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <?= e($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($view === 'list'): ?>
<!-- ── LIST ─────────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $typeLabelPlural ?> <span class="text-muted fw-normal">(<?= count($items) ?>)</span></h5>
    <a href="admin.php?action=new&type=<?= $type ?>" class="btn btn-sm btn-success">+ New <?= $typeLabel ?></a>
</div>

<div class="card shadow-sm">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th><?= $type === 'product' ? 'Price' : 'Date' ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="4" class="text-muted text-center py-4">No <?= strtolower($typeLabelPlural) ?> yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['title']) ?></td>
                <td><code class="text-muted"><?= e($item['slug']) ?></code></td>
                <td><?= $type === 'product' ? e(($item['metadata']['currency'] ?? 'USD') . ' ' . ($item['metadata']['price'] ?? '—')) : e($item['date']) ?></td>
                <td class="text-end">
                    <a href="admin.php?action=edit&type=<?= $type ?>&slug=<?= urlencode($item['slug']) ?>"
                       class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="POST"
                          action="admin.php?action=delete&type=<?= $type ?>&slug=<?= urlencode($item['slug']) ?>"
                          class="d-inline"
                          onsubmit="return confirm('Delete \"<?= e(addslashes($item['title'])) ?>\"? This cannot be undone.')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($view === 'new' || $view === 'edit'): ?>
<!-- ── FORM ─────────────────────────────────────────────────────────────── -->
<?php
$isEdit     = $view === 'edit';
$m          = $isEdit ? $editItem['metadata'] : [];
$body       = $isEdit ? trim(removeFrontMatter($editItem['content'])) : '';
$saveAction = $isEdit ? 'save' : 'create';
$formTitle  = $isEdit ? "Edit $typeLabel" : "New $typeLabel";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $formTitle ?></h5>
    <a href="admin.php?type=<?= $type ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<form method="POST" action="admin.php?action=<?= $saveAction ?>&type=<?= $type ?>&slug=<?= urlencode($slug) ?>">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="card shadow-sm p-4">

    <div class="row g-3 mb-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?= e($m['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-<?= $type === 'product' ? '6' : '3' ?>">
            <label class="form-label fw-semibold">Slug</label>
            <input type="text" name="slug" class="form-control"
                   value="<?= e($m['slug'] ?? '') ?>" placeholder="auto from title">
        </div>
        <?php if ($type !== 'product'): ?>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Date</label>
            <input type="date" name="date" class="form-control"
                   value="<?= e($m['date'] ?? date('Y-m-d')) ?>">
        </div>
        <?php endif; ?>
    </div>

    <?php if ($type === 'product'): ?>
    <div class="row g-3 mb-2">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Price</label>
            <input type="text" name="price" class="form-control"
                   value="<?= e($m['price'] ?? '') ?>" placeholder="29.99">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Currency</label>
            <input type="text" name="currency" class="form-control"
                   value="<?= e($m['currency'] ?? 'USD') ?>" placeholder="USD">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Stock</label>
            <select name="stock" class="form-select">
                <option value="In Stock" <?= ($m['stock'] ?? '') === 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                <option value="Out of Stock" <?= ($m['stock'] ?? '') === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Buy URL</label>
            <input type="url" name="buy_url" class="form-control"
                   value="<?= e($m['buy_url'] ?? '') ?>" placeholder="https://buy.stripe.com/...">
        </div>
    </div>
    <?php elseif ($type === 'post'): ?>
    <div class="row g-3 mb-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Categories</label>
            <input type="text" name="categories" class="form-control"
                   value="<?= e($m['categories'] ?? '') ?>" placeholder="Dev, PHP">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Tags</label>
            <input type="text" name="tags" class="form-control"
                   value="<?= e($m['tags'] ?? '') ?>" placeholder="markdown, bootstrap, webdev">
        </div>
    </div>
    <?php else: ?>
        <input type="hidden" name="categories" value="">
        <input type="hidden" name="tags" value="">
    <?php endif; ?>

    <div class="mb-2">
        <label class="form-label fw-semibold">Image (URL)</label>
        <input type="hidden" name="image" id="featuredImageField" value="<?= e($m['image'] ?? '') ?>">
        <div class="input-group">
            <input type="text" id="featuredImageUrl" class="form-control"
                   value="<?= e($m['image'] ?? '') ?>" placeholder="https://...">
            <label class="btn btn-outline-secondary mb-0" title="Upload featured image">
                <span id="featuredUploadLabel">Upload</span>
                <input type="file" id="featuredImageUpload" accept="image/*" class="d-none">
            </label>
        </div>
        <div id="featuredImagePreview" class="mt-2" style="<?= !empty($m['image']) ? '' : 'display:none' ?>">
            <img src="<?= e($m['image'] ?? '') ?>" alt="Preview"
                 style="max-height:120px;border-radius:6px;object-fit:cover;">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Excerpt</label>
        <textarea name="excerpt" class="form-control" rows="2"
                  placeholder="Short summary for listings and SEO..."><?= e($m['excerpt'] ?? '') ?></textarea>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold">Content</label>
        <textarea id="body" name="body"><?= e($body) ?></textarea>
    </div>

    <div class="d-flex gap-2 align-items-center">
        <button type="submit" class="btn btn-primary">
            <?= $isEdit ? 'Save changes' : "Create $typeLabel" ?>
        </button>
        <a href="admin.php?type=<?= $type ?>" class="btn btn-outline-secondary">Cancel</a>
        <?php if ($isEdit): ?>
            <?php $viewPath = $type === 'post' ? 'post' : ($type === 'product' ? 'product' : 'page'); ?>
            <a href="<?= $base ?>/<?= $viewPath ?>/<?= urlencode($slug) ?>"
               target="_blank" class="btn btn-outline-info ms-auto">View published ↗</a>
        <?php endif; ?>
    </div>

</div>
</form>

<?php elseif ($view === 'media'): ?>
<!-- ── MEDIA ──────────────────────────────────────────────────────────────── -->
<?php
if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
$mediaFiles = array_values(array_filter(scandir(UPLOADS_DIR), function($f) {
    return preg_match('/\.(jpe?g|png|gif|webp)$/i', $f);
}));
rsort($mediaFiles);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Media <span class="text-muted fw-normal">(<?= count($mediaFiles) ?>)</span></h5>
    <label class="btn btn-sm btn-success mb-0">
        Upload image <input type="file" id="mediaUpload" accept="image/*" class="d-none">
    </label>
</div>

<div id="uploadAlert" class="alert d-none"></div>

<?php if (empty($mediaFiles)): ?>
    <p class="text-muted">No images uploaded yet.</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($mediaFiles as $file):
    $url = $baseUrl . '/assets/uploads/' . $file; ?>
    <div class="col-6 col-md-3 col-lg-2" id="card-<?= e(urlencode($file)) ?>">
        <div class="card h-100 shadow-sm">
            <img src="<?= e($url) ?>" class="card-img-top"
                 style="height:100px;object-fit:cover;" loading="lazy">
            <div class="card-body p-2">
                <p class="card-text small text-muted text-truncate mb-2" title="<?= e($file) ?>"><?= e($file) ?></p>
                <div class="d-flex gap-1">
                    <button class="btn btn-xs btn-outline-secondary flex-grow-1"
                            style="font-size:.75rem;padding:.2rem .4rem"
                            onclick="copyUrl('<?= e($url) ?>', this)">Copy URL</button>
                    <form method="POST" action="admin.php?action=delete-media"
                          onsubmit="return confirm('Delete <?= e(addslashes($file)) ?>?')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="file" value="<?= e($file) ?>">
                        <button class="btn btn-xs btn-outline-danger"
                                style="font-size:.75rem;padding:.2rem .4rem">✕</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($view === 'settings'): ?>
<!-- ── SETTINGS ──────────────────────────────────────────────────────────── -->
<h5 class="mb-3">Settings</h5>
<form method="POST" action="admin.php?action=save-settings">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Colors</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="colors_active" id="colorsActive"
                   value="1" <?= !empty($config['colors_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="colorsActive">Use custom colors</label>
        </div>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Primary color</label>
            <div class="form-text mb-1">Buttons, badges, links, active states.</div>
            <div class="d-flex align-items-center gap-2">
                <input type="color" name="color_primary" class="form-control form-control-color"
                       value="<?= e($config['color_primary'] ?? '#0d6efd') ?>" title="Primary color">
                <input type="text" id="colorPrimaryText" class="form-control form-control-sm font-monospace"
                       value="<?= e($config['color_primary'] ?? '#0d6efd') ?>" maxlength="7" placeholder="#0d6efd">
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Dark color</label>
            <div class="form-text mb-1">Navbar, page headers, footer.</div>
            <div class="d-flex align-items-center gap-2">
                <input type="color" name="color_dark" class="form-control form-control-color"
                       value="<?= e($config['color_dark'] ?? '#212529') ?>" title="Dark color">
                <input type="text" id="colorDarkText" class="form-control form-control-sm font-monospace"
                       value="<?= e($config['color_dark'] ?? '#212529') ?>" maxlength="7" placeholder="#212529">
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Site</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Site name</label>
            <input type="text" name="blog_name" class="form-control" value="<?= e($config['blog_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Short name</label>
            <input type="text" name="short_name" class="form-control" value="<?= e($config['short_name'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Tagline</label>
            <input type="text" name="tagline" class="form-control" value="<?= e($config['tagline'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Site URL</label>
            <input type="url" name="site_url" class="form-control" value="<?= e($config['site_url'] ?? '') ?>" placeholder="https://example.com">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Homepage hero</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="hero_active" id="heroActive"
                   value="1" <?= !empty($config['hero_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="heroActive">Show section</label>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" name="hero_title" class="form-control" value="<?= e($config['hero_title'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Description</label>
            <input type="text" name="hero_description" class="form-control" value="<?= e($config['hero_description'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Hero image</label>
            <input type="hidden" name="hero_image" id="heroImageField" value="<?= e($config['hero_image'] ?? '') ?>">
            <div class="input-group">
                <input type="text" id="heroImageUrl" class="form-control"
                       value="<?= e($config['hero_image'] ?? '') ?>" placeholder="https://...">
                <label class="btn btn-outline-secondary mb-0" title="Upload hero image">
                    <span id="heroUploadLabel">Upload</span>
                    <input type="file" id="heroImageUpload" accept="image/*" class="d-none">
                </label>
            </div>
            <div id="heroImagePreview" class="mt-2" style="<?= !empty($config['hero_image']) ? '' : 'display:none' ?>">
                <img src="<?= e($config['hero_image'] ?? '') ?>" alt="Hero preview"
                     style="max-height:120px;border-radius:6px;object-fit:cover;">
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Button text</label>
            <input type="text" name="hero_button_text" class="form-control" value="<?= e($config['hero_button_text'] ?? '') ?>" placeholder="Learn more">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Button URL</label>
            <input type="url" name="hero_button_url" class="form-control" value="<?= e($config['hero_button_url'] ?? '') ?>" placeholder="Leave blank to scroll to posts">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Features section</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="features_active" id="featuresActive"
                   value="1" <?= !empty($config['features_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="featuresActive">Show section</label>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Section heading</label>
        <input type="text" name="features_heading" class="form-control" value="<?= e($config['features_heading'] ?? '') ?>">
    </div>
    <div class="alert alert-light border mb-3 py-2 px-3 small">
        <strong>Choosing icons:</strong> browse the full library at
        <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">icons.getbootstrap.com</a>,
        search for the icon you want, and copy its name (e.g. <code>bi-star</code>, <code>bi-envelope</code>, <code>bi-shield-check</code>).
        Paste that name into the Icon field below.
    </div>
    <?php for ($i = 1; $i <= 4; $i++): ?>
    <div class="border rounded p-3 mb-3">
        <div class="fw-semibold mb-2">Feature <?= $i ?></div>
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Icon</label>
                <input type="text" name="feature_<?= $i ?>_icon" class="form-control form-control-sm"
                       value="<?= e($config["feature_{$i}_icon"] ?? '') ?>" placeholder="bi-star">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Title</label>
                <input type="text" name="feature_<?= $i ?>_title" class="form-control form-control-sm"
                       value="<?= e($config["feature_{$i}_title"] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Text</label>
                <input type="text" name="feature_<?= $i ?>_text" class="form-control form-control-sm"
                       value="<?= e($config["feature_{$i}_text"] ?? '') ?>">
            </div>
        </div>
    </div>
    <?php endfor; ?>
</div>

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Testimonial</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="testimonial_active" id="testimonialActive"
                   value="1" <?= !empty($config['testimonial_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="testimonialActive">Show section</label>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-semibold">Quote</label>
            <input type="text" name="testimonial_quote" class="form-control" value="<?= e($config['testimonial_quote'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Author</label>
            <input type="text" name="testimonial_author" class="form-control" value="<?= e($config['testimonial_author'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Role</label>
            <input type="text" name="testimonial_role" class="form-control" value="<?= e($config['testimonial_role'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Avatar URL</label>
            <input type="url" name="testimonial_avatar" class="form-control" value="<?= e($config['testimonial_avatar'] ?? '') ?>" placeholder="https://...">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Articles section</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="articles_active" id="articlesActive"
                   value="1" <?= !empty($config['articles_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="articlesActive">Show section</label>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Heading</label>
            <input type="text" name="articles_heading" class="form-control" value="<?= e($config['articles_heading'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Subtitle</label>
            <input type="text" name="articles_subtitle" class="form-control" value="<?= e($config['articles_subtitle'] ?? '') ?>">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Call to action banner</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="cta_active" id="ctaActive"
                   value="1" <?= !empty($config['cta_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="ctaActive">Show section</label>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" name="cta_title" class="form-control" value="<?= e($config['cta_title'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Text</label>
            <input type="text" name="cta_text" class="form-control" value="<?= e($config['cta_text'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Button text</label>
            <input type="text" name="cta_button_text" class="form-control" value="<?= e($config['cta_button_text'] ?? '') ?>" placeholder="Contact us">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Button URL</label>
            <input type="url" name="cta_button_url" class="form-control" value="<?= e($config['cta_button_url'] ?? '') ?>" placeholder="Leave blank to link to home">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="text-muted text-uppercase fw-semibold mb-0" style="font-size:.75rem;letter-spacing:.08em">Contact page</h6>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="contact_active" id="contactActive"
                   value="1" <?= !empty($config['contact_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="contactActive">Show in nav</label>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Page title</label>
            <input type="text" name="contact_title" class="form-control" value="<?= e($config['contact_title'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Subtitle</label>
            <input type="text" name="contact_subtitle" class="form-control" value="<?= e($config['contact_subtitle'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Recipient email <span class="text-danger">*</span></label>
            <input type="email" name="contact_email" class="form-control" value="<?= e($config['contact_email'] ?? '') ?>" placeholder="you@example.com">
            <div class="form-text">Where form submissions are delivered.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">From name</label>
            <input type="text" name="mail_from_name" class="form-control" value="<?= e($config['mail_from_name'] ?? '') ?>" placeholder="Defaults to site name">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Success message</label>
            <input type="text" name="contact_success" class="form-control" value="<?= e($config['contact_success'] ?? '') ?>">
        </div>
        <div class="col-12"><hr class="my-1"></div>
        <div class="col-12">
            <p class="fw-semibold mb-1">reCAPTCHA v2 <span class="text-muted fw-normal small">(optional)</span></p>
            <p class="text-muted small mb-2">
                Get your keys at <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">google.com/recaptcha</a>.
                Choose <strong>reCAPTCHA v2 → "I'm not a robot" checkbox</strong>. Leave both fields blank to disable.
            </p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Site key <span class="text-muted fw-normal small">(public)</span></label>
            <input type="text" name="recaptcha_site_key" class="form-control font-monospace"
                   value="<?= e($config['recaptcha_site_key'] ?? '') ?>" placeholder="6Lc…">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Secret key <span class="text-muted fw-normal small">(private)</span></label>
            <input type="password" name="recaptcha_secret_key" class="form-control font-monospace"
                   value="<?= e($config['recaptcha_secret_key'] ?? '') ?>" placeholder="6Lc…">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Content</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Author name</label>
            <input type="text" name="author_name" class="form-control" value="<?= e($config['author_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Default image URL</label>
            <input type="url" name="default_image" class="form-control" value="<?= e($config['default_image'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Footer text</label>
            <input type="text" name="footer_text" class="form-control" value="<?= e($config['footer_text'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Privacy policy link</label>
            <input type="text" name="privacy_policy_link" class="form-control" value="<?= e($config['privacy_policy_link'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Terms of service link</label>
            <input type="text" name="terms_service_link" class="form-control" value="<?= e($config['terms_service_link'] ?? '') ?>">
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-3">
    <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Admin credentials</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" name="admin_user" class="form-control" value="<?= e($config['admin_user'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="admin_pass" class="form-control" placeholder="Leave blank to keep current">
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary">Save settings</button>
</form>

<?php endif; ?>

</div><!-- /col main -->
</div><!-- /row -->
</div><!-- /container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── EasyMDE with image upload ──────────────────────────────────────────────
const bodyEl = document.getElementById('body');
if (bodyEl) {
    new EasyMDE({
        element: bodyEl,
        spellChecker: false,
        autosave: {
            enabled: true,
            uniqueId: 'admin-<?= $type ?>-<?= $slug ?: 'new' ?>',
            delay: 3000,
        },
        toolbar: [
            'bold', 'italic', 'heading', '|',
            'quote', 'unordered-list', 'ordered-list', '|',
            'link', 'image', 'upload-image', '|',
            'preview', 'side-by-side', 'fullscreen', '|',
            'guide'
        ],
        uploadImage: true,
        imageUploadFunction(file, onSuccess, onError) {
            const fd = new FormData();
            fd.append('image', file);
            fd.append('csrf_token', csrfToken);
            fetch('admin.php?action=upload', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => d.url ? onSuccess(d.url) : onError(d.error || 'Upload failed'))
                .catch(() => onError('Upload failed'));
        },
    });
}

// ── Media library upload ───────────────────────────────────────────────────
const mediaUpload = document.getElementById('mediaUpload');
if (mediaUpload) {
    mediaUpload.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const alert = document.getElementById('uploadAlert');
        alert.className = 'alert alert-info';
        alert.textContent = 'Uploading…';

        const fd = new FormData();
        fd.append('image', file);
        fd.append('csrf_token', csrfToken);
        fetch('admin.php?action=upload', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.url) {
                    alert.className = 'alert alert-success';
                    alert.innerHTML = 'Uploaded: <code>' + d.url + '</code> — <a href="admin.php?action=media">Refresh</a> to see it.';
                } else {
                    alert.className = 'alert alert-danger';
                    alert.textContent = d.error || 'Upload failed.';
                }
            })
            .catch(() => {
                alert.className = 'alert alert-danger';
                alert.textContent = 'Upload failed.';
            });
        this.value = '';
    });
}

// ── Sync featured image to hidden field on submit ─────────────────────────
const postForm = document.querySelector('form[action*="action=save"], form[action*="action=create"]');
if (postForm) {
    postForm.addEventListener('submit', function () {
        const urlInput    = document.getElementById('featuredImageUrl');
        const hiddenInput = document.getElementById('featuredImageField');
        if (urlInput && hiddenInput) {
            hiddenInput.value = urlInput.value.trim();
        }
    });
}

// ── Featured image upload ──────────────────────────────────────────────────
function setFeaturedImage(url) {
    document.getElementById('featuredImageField').value = url;
    document.getElementById('featuredImageUrl').value   = url;
    const preview = document.getElementById('featuredImagePreview');
    if (url) {
        preview.querySelector('img').src = url;
        preview.style.display = '';
    } else {
        preview.style.display = 'none';
    }
}

const featuredUpload = document.getElementById('featuredImageUpload');
if (featuredUpload) {
    featuredUpload.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const label = document.getElementById('featuredUploadLabel');
        label.textContent = 'Uploading…';

        const fd = new FormData();
        fd.append('image', file);
        fd.append('csrf_token', csrfToken);
        fetch('admin.php?action=upload', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.url) { setFeaturedImage(d.url); }
                else { alert(d.error || 'Upload failed.'); }
                label.textContent = 'Upload';
            })
            .catch(() => { alert('Upload failed.'); label.textContent = 'Upload'; });
        this.value = '';
    });

    // Sync hidden field when URL is typed manually
    document.getElementById('featuredImageUrl').addEventListener('input', function () {
        setFeaturedImage(this.value.trim());
    });
}

// ── Hero image upload (Settings page) ─────────────────────────────────────
function setHeroImage(url) {
    document.getElementById('heroImageField').value = url;
    document.getElementById('heroImageUrl').value   = url;
    const preview = document.getElementById('heroImagePreview');
    if (url) {
        preview.querySelector('img').src = url;
        preview.style.display = '';
    } else {
        preview.style.display = 'none';
    }
}

const heroUpload = document.getElementById('heroImageUpload');
if (heroUpload) {
    heroUpload.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const label = document.getElementById('heroUploadLabel');
        label.textContent = 'Uploading…';

        const fd = new FormData();
        fd.append('image', file);
        fd.append('csrf_token', csrfToken);
        fetch('admin.php?action=upload', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.url) { setHeroImage(d.url); }
                else { alert(d.error || 'Upload failed.'); }
                label.textContent = 'Upload';
            })
            .catch(() => { alert('Upload failed.'); label.textContent = 'Upload'; });
        this.value = '';
    });

    document.getElementById('heroImageUrl').addEventListener('input', function () {
        setHeroImage(this.value.trim());
    });

    // Sync hidden field on settings form submit
    const settingsForm = document.querySelector('form[action*="action=save-settings"]');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function () {
            document.getElementById('heroImageField').value =
                document.getElementById('heroImageUrl').value.trim();
        });
    }
}

// ── Color picker ↔ text input sync ───────────────────────────────────────
function syncColor(pickerId, textId) {
    const picker = document.querySelector('[name="' + pickerId + '"]');
    const text   = document.getElementById(textId);
    if (!picker || !text) return;
    picker.addEventListener('input', () => text.value = picker.value);
    text.addEventListener('input', () => {
        if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value;
    });
}
syncColor('color_primary', 'colorPrimaryText');
syncColor('color_dark',    'colorDarkText');

// ── Copy URL to clipboard ──────────────────────────────────────────────────
function copyUrl(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = orig, 1500);
    });
}
</script>
</body>
</html>
