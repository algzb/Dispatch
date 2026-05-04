<?php
require_once __DIR__ . '/../libs/Parsedown.php';

function html($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalizeSlug($slug) {
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
    return trim($slug, '-');
}

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

function removeFrontMatter($content) {
    return preg_replace('/^---\s*.*?\s*---\s*/s', '', $content, 1);
}

function loadMarkdownFile($path) {
    if (!is_file($path)) {
        return false;
    }
    return file_get_contents($path);
}

function loadPosts($postsDir) {
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
            'slug' => $slug,
            'title' => $title,
            'date' => $date,
            'formatted_date' => formatDate($date),
            'excerpt' => $excerpt,
            'image' => $image,
            'content' => $content,
            'metadata' => $metadata,
            'file' => $file,
        ];
    }

    return $posts;
}

function sortPostsByDate($posts) {
    usort($posts, function ($a, $b) {
        $timeA = strtotime($a['date'] ?? '');
        $timeB = strtotime($b['date'] ?? '');
        return $timeB <=> $timeA;
    });
    return $posts;
}

function findMarkdownBySlug($slug, $dir) {
    $slug = normalizeSlug($slug);
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
                'slug' => $itemSlug,
                'content' => $content,
                'metadata' => $metadata,
                'file' => $file,
            ];
        }
    }

    return false;
}

function generateMenu($pagesDir, $activeSlug = '', $basePath = '/') {
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

function formatDate($dateString, $format = 'F j, Y') {
    $date = date_create($dateString);
    return $date ? date_format($date, $format) : $dateString;
}

function pageTitle($metadata, $config, $default = '') {
    if (!empty($metadata['title'])) {
        return $metadata['title'] . ' | ' . $config['blog_name'];
    }
    return $default ?: $config['blog_name'];
}

function pageDescription($metadata, $config) {
    if (!empty($metadata['excerpt'])) {
        return $metadata['excerpt'];
    }

    return $config['tagline'];
}

function calculateReadingTime($content, $wordsPerMinute = 200) {
    $plainText = strip_tags(removeFrontMatter($content));
    $wordCount = str_word_count($plainText);
    $minutes = ceil($wordCount / $wordsPerMinute);
    return max(1, $minutes);
}
