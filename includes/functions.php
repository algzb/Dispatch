<?php
require_once __DIR__ . '/../libs/Parsedown.php';

// Escape a value for safe HTML output.
function html($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Emit baseline security headers. Call before any output is sent.
// CSP allows the CDNs this project loads (jsdelivr, unpkg) plus inline styles
// (the optional custom-colors <style> block) and remote images.
function sendSecurityHeaders(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Note: 'unsafe-inline' is required for scripts because the admin panel uses
    // inline <script> blocks and inline event handlers (onclick/onsubmit). This
    // still blocks script injection from *unknown external origins* — the common
    // stored-XSS vector here. Tightening to a nonce-based policy would require
    // refactoring those inline handlers to addEventListener first.
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://www.google.com https://www.gstatic.com; " .
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; " .
        "font-src 'self' https://cdn.jsdelivr.net; " .
        "img-src 'self' data: https:; " .
        "frame-src https://www.google.com; " .
        "frame-ancestors 'self'; " .
        "base-uri 'self'"
    );
}

// Verify a submitted password against the stored credential.
// Accepts both a bcrypt hash (set once the admin changes the password) and a
// plaintext default, so config.php never has to run bcrypt on every request.
function verifyAdminPassword(string $submitted, string $stored): bool {
    if ($stored === '') return false;
    if (preg_match('/^\$2[aby]\$/', $stored)) {
        return password_verify($submitted, $stored);
    }
    return hash_equals($stored, $submitted);
}

// Validate a "#rrggbb" hex color. Returns the lowercased value, or the fallback
// when the input is not a well-formed 6-digit hex color.
function sanitizeHexColor(string $value, string $fallback): string {
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
}

// Convert any string to a lowercase, hyphen-separated URL slug.
// Non-alphanumeric characters (except hyphens) are replaced, then leading/trailing hyphens stripped.
function normalizeSlug($slug) {
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
    return trim($slug, '-');
}

// Parse the YAML-like front matter block from a Markdown file.
// Only flat key: value pairs are supported — no nested keys, arrays, or multi-line values.
// Returns an empty array if no front matter block is found.
function parseMetadata($content) {
    if (preg_match('/^---\s*(.*?)\s*---/s', $content, $matches)) {
        $lines = preg_split('/\r\n|\r|\n/', trim($matches[1]));
        $metadata = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($key, $value) = explode(':', $line, 2);
            $metadata[trim($key)] = trim($value);
        }
        return $metadata;
    }
    return [];
}

// Strip the front matter block (--- ... ---) from the top of a Markdown string.
function removeFrontMatter($content) {
    return preg_replace('/^---\s*.*?\s*---\s*/s', '', $content, 1);
}

// Read a Markdown file from disk. Returns false if the file does not exist.
function loadMarkdownFile($path) {
    if (!is_file($path)) {
        return false;
    }
    return file_get_contents($path);
}

// Load all .md files from a directory and return them as an array of post arrays.
// The slug comes from the front matter 'slug' key; falls back to the filename stem.
// The excerpt comes from the front matter 'excerpt' key; falls back to auto-generated.
// Performs a linear scan — avoid calling this multiple times per request.
function loadPosts($postsDir) {
    if (!is_dir($postsDir)) return [];
    $files = scandir($postsDir);
    $posts = [];

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'md') {
            continue;
        }

        $content = loadMarkdownFile("$postsDir/$file");
        if ($content === false) {
            continue;
        }

        $metadata = parseMetadata($content);
        $slug = normalizeSlug($metadata['slug'] ?? pathinfo($file, PATHINFO_FILENAME));
        $title = $metadata['title'] ?? ucfirst(str_replace('-', ' ', $slug));
        $excerpt = $metadata['excerpt'] ?? generateExcerpt($content);
        $image = $metadata['image'] ?? null;
        $date = $metadata['date'] ?? '';

        $posts[] = [
            'slug'           => $slug,
            'title'          => $title,
            'date'           => $date,
            'formatted_date' => formatDate($date),
            'excerpt'        => $excerpt,
            'image'          => $image,
            'content'        => $content,
            'metadata'       => $metadata,
            'file'           => $file,
        ];
    }

    return $posts;
}

// Sort posts newest-first. Posts with no date sort to the bottom (strtotime returns false → 0).
function sortPostsByDate($posts) {
    usort($posts, function ($a, $b) {
        $timeA = strtotime($a['date'] ?? '');
        $timeB = strtotime($b['date'] ?? '');
        return $timeB <=> $timeA;
    });
    return $posts;
}

// Find a single post or page by slug within a directory.
// Scans every .md file and checks the slug from front matter (falling back to filename).
// Returns false if not found. This is an O(n) scan — not suitable for large sets per request.
function findMarkdownBySlug($slug, $dir) {
    $slug = normalizeSlug($slug);
    if (!is_dir($dir)) return false;
    $files = scandir($dir);

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'md') {
            continue;
        }

        $content = loadMarkdownFile("$dir/$file");
        if ($content === false) {
            continue;
        }

        $metadata = parseMetadata($content);
        $itemSlug = normalizeSlug($metadata['slug'] ?? pathinfo($file, PATHINFO_FILENAME));

        if ($itemSlug === $slug) {
            return [
                'slug'     => $itemSlug,
                'content'  => $content,
                'metadata' => $metadata,
                'file'     => $file,
            ];
        }
    }

    return false;
}

// Build the navbar <li> items from all pages in the given directory.
// Pages whose slugs match $excludeSlugs are intentionally hidden from the menu
// (privacy and terms are linked in the footer instead).
function generateMenu($pagesDir, $activeSlug = '', $basePath = '/') {
    if (!is_dir($pagesDir)) return '';
    $menuItems = '';
    $files = scandir($pagesDir);
    $excludeSlugs = ['privacy', 'terms'];

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'md') {
            continue;
        }

        $content = loadMarkdownFile("$pagesDir/$file");
        if ($content === false) {
            continue;
        }

        $metadata = parseMetadata($content);
        $slug = normalizeSlug($metadata['slug'] ?? pathinfo($file, PATHINFO_FILENAME));
        if (in_array($slug, $excludeSlugs, true)) {
            continue;
        }

        $title = $metadata['title'] ?? ucfirst(str_replace('-', ' ', $slug));
        $activeClass = $slug === normalizeSlug($activeSlug) ? 'active' : '';

        $menuItems .= "<li class='nav-item'>";
        $menuItems .= "<a class='nav-link $activeClass' href='" . rtrim($basePath, '/') . "/page/" . urlencode($slug) . "'>" . html($title) . "</a>";
        $menuItems .= "</li>\n";
    }

    return $menuItems;
}

// Generate a plain-text excerpt from Markdown content.
// Front matter is stripped before excerpting so --- blocks don't appear in summaries.
// Truncates at the nearest word boundary below $length characters.
function generateExcerpt($content, $length = 160) {
    $plainText = trim(strip_tags(removeFrontMatter($content)));
    if (strlen($plainText) <= $length) {
        return $plainText;
    }

    $excerpt = substr($plainText, 0, $length);
    if (preg_match('/^(.+)\b/', $excerpt, $matches)) {
        $excerpt = $matches[1];
    }

    return rtrim($excerpt, " \t\n\r\0\x0B,.;:!?") . '...';
}

// Format a date string using the given format. Returns the raw string if parsing fails
// so that malformed dates surface visibly rather than silently becoming empty.
function formatDate($dateString, $format = 'F j, Y') {
    $date = date_create($dateString);
    return $date ? date_format($date, $format) : $dateString;
}

// Build the <title> tag value. Falls back to the site name when the page has no title.
function pageTitle($metadata, $config, $default = '') {
    if (!empty($metadata['title'])) {
        return $metadata['title'] . ' | ' . $config['blog_name'];
    }
    return $default ?: $config['blog_name'];
}

// Return the meta description for a page. Prefers the front matter excerpt over the site tagline.
function pageDescription($metadata, $config) {
    if (!empty($metadata['excerpt'])) {
        return $metadata['excerpt'];
    }

    return $config['tagline'];
}

// Convert a hex color string to an "r, g, b" string for use in rgba().
function hexToRgb(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
}

// Darken a hex color by multiplying each channel by $factor (0–1).
function hexDarken(string $hex, float $factor = 0.85): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = str_pad(dechex(max(0, min(255, (int) round(hexdec(substr($hex, 0, 2)) * $factor)))), 2, '0', STR_PAD_LEFT);
    $g = str_pad(dechex(max(0, min(255, (int) round(hexdec(substr($hex, 2, 2)) * $factor)))), 2, '0', STR_PAD_LEFT);
    $b = str_pad(dechex(max(0, min(255, (int) round(hexdec(substr($hex, 4, 2)) * $factor)))), 2, '0', STR_PAD_LEFT);
    return '#' . $r . $g . $b;
}

// Verify a reCAPTCHA v2 response token against Google's API.
// Returns true when the token is valid, false on failure or when the secret is empty.
function verifyRecaptcha(string $secret, string $token): bool
{
    if ($secret === '' || $token === '') return false;

    $url  = 'https://www.google.com/recaptcha/api/siteverify';
    $data = http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        $response = file_get_contents($url, false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $data, 'timeout' => 5],
        ]));
    }

    if (!$response) return false;
    $json = json_decode($response, true);
    return !empty($json['success']);
}

// Estimate reading time in minutes. Minimum of 1 minute regardless of word count.
function calculateReadingTime($content, $wordsPerMinute = 200) {
    $plainText = strip_tags(removeFrontMatter($content));
    $wordCount = str_word_count($plainText);
    $minutes = ceil($wordCount / $wordsPerMinute);
    return max(1, $minutes);
}
