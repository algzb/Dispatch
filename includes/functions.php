<?php
require_once __DIR__ . '/../libs/Parsedown.php';

// Escape a value for safe HTML output.
function html($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

// Build the <title> tag value. Falls back to blog_name when the post has no title.
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

// Estimate reading time in minutes. Minimum of 1 minute regardless of word count.
function calculateReadingTime($content, $wordsPerMinute = 200) {
    $plainText = strip_tags(removeFrontMatter($content));
    $wordCount = str_word_count($plainText);
    $minutes = ceil($wordCount / $wordsPerMinute);
    return max(1, $minutes);
}
