<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
$config = require __DIR__ . '/config.php';

define('POSTS_DIR', __DIR__ . '/posts');
define('PAGES_DIR', __DIR__ . '/pages');

// ─── Helpers ──────────────────────────────────────────────────────────────────

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function go(string $url): void {
    header('Location: ' . $url);
    exit;
}

function contentDir(string $type): string {
    return $type === 'page' ? PAGES_DIR : POSTS_DIR;
}

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

// ─── Routing ──────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? '';
$type   = ($_GET['type'] ?? '') === 'page' ? 'page' : 'post';
$slug   = $_GET['slug'] ?? '';
$flash  = ['type' => '', 'msg' => ''];

if ($action === 'logout') {
    session_destroy();
    go('admin.php');
}

if ($action === 'login' && isPost()) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === ($config['admin_user'] ?? '') && $pass === ($config['admin_pass'] ?? '')) {
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

$metaKeys = ['title', 'date', 'slug', 'categories', 'tags', 'image', 'excerpt'];

if ($action === 'save' && isPost()) {
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
    $meta = [];
    foreach ($metaKeys as $k) {
        $meta[$k] = trim($_POST[$k] ?? '');
    }
    if (empty($meta['image']) && !empty($_SESSION['featured_image_url'])) {
        $meta['image'] = $_SESSION['featured_image_url'];
    }
    unset($_SESSION['featured_image_url']);
    $meta['date'] = $meta['date'] ?: date('Y-m-d');
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
    $file = basename($_POST['file'] ?? '');
    if ($file) @unlink(UPLOADS_DIR . '/' . $file);
    go('admin.php?action=media');
}

if ($action === 'save-settings' && isPost()) {
    $configPath = __DIR__ . '/config.php';
    $keys = ['domain','site_url','blog_name','tagline','short_name','author_name',
             'footer_text','privacy_policy_link','terms_service_link','default_image',
             'admin_user','admin_pass'];
    $out = "<?php\nreturn [\n";
    foreach ($keys as $k) {
        $v = $_POST[$k] ?? $config[$k] ?? '';
        $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $v);
        $out .= "    '$k' => '$escaped',\n";
    }
    $out .= "];\n";
    file_put_contents($configPath, $out);
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

$typeLabel       = $type === 'page' ? 'Page' : 'Post';
$typeLabelPlural = $type === 'page' ? 'Pages' : 'Posts';

// ─── HTML ─────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — <?= e($config['blog_name']) ?></title>
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
        <a href="index.php" target="_blank" class="btn btn-sm btn-outline-light">View blog</a>
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
                <th>Date</th>
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
                <td><?= e($item['date']) ?></td>
                <td class="text-end">
                    <a href="admin.php?action=edit&type=<?= $type ?>&slug=<?= urlencode($item['slug']) ?>"
                       class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="POST"
                          action="admin.php?action=delete&type=<?= $type ?>&slug=<?= urlencode($item['slug']) ?>"
                          class="d-inline"
                          onsubmit="return confirm('Delete \"<?= e(addslashes($item['title'])) ?>\"? This cannot be undone.')">
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
<div class="card shadow-sm p-4">

    <div class="row g-3 mb-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?= e($m['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Slug</label>
            <input type="text" name="slug" class="form-control"
                   value="<?= e($m['slug'] ?? '') ?>" placeholder="auto from title">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Date</label>
            <input type="date" name="date" class="form-control"
                   value="<?= e($m['date'] ?? date('Y-m-d')) ?>">
        </div>
    </div>

    <?php if ($type === 'post'): ?>
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
            <a href="<?= $type === 'post' ? 'post.php' : 'page.php' ?>?slug=<?= urlencode($slug) ?>"
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
<div class="card shadow-sm p-4 mb-3">
    <h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.08em">Site</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Blog name</label>
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
        <div class="col-md-6">
            <label class="form-label fw-semibold">Domain</label>
            <input type="text" name="domain" class="form-control" value="<?= e($config['domain'] ?? '') ?>" placeholder="example.com/blog">
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
            <input type="text" name="admin_pass" class="form-control" value="<?= e($config['admin_pass'] ?? '') ?>">
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
