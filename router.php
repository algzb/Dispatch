<?php
// Dev router for PHP's built-in server — emulates the .htaccess rewrite rules.
// Usage: php -S localhost:8000 router.php   (run from the project root)
// NOT for production; Apache + .htaccess handles routing there.

$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = __DIR__ . $uri;

// Serve existing static files (css, images, uploads, js) directly.
if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    return false;
}

// Map pretty URLs to their PHP entry points.
$routes = [
    '#^/sitemap\.xml$#'        => fn($m) => ['sitemap.php', []],
    '#^/robots\.txt$#'         => fn($m) => ['robots.php', []],
    '#^/feed\.xml$#'           => fn($m) => ['feed.php', []],
    '#^/admin/?$#'             => fn($m) => ['admin.php', []],
    '#^/blog/?$#'              => fn($m) => ['blog.php', []],
    '#^/contact/?$#'           => fn($m) => ['contact.php', []],
    '#^/post/([^/]+)/?$#'      => fn($m) => ['post.php', ['slug' => $m[1]]],
    '#^/store/?$#'             => fn($m) => ['store.php', []],
    '#^/product/([^/]+)/?$#'   => fn($m) => ['product.php', ['slug' => $m[1]]],
    '#^/page/([^/]+)/?$#'      => fn($m) => ['page.php', ['slug' => $m[1]]],
    '#^/tag/([^/]+)/?$#'       => fn($m) => ['archive.php', ['tag' => $m[1]]],
    '#^/category/([^/]+)/?$#'  => fn($m) => ['archive.php', ['category' => $m[1]]],
    '#^/archive/?$#'           => fn($m) => ['archive.php', []],
    '#^/$#'                    => fn($m) => ['index.php', []],
];

foreach ($routes as $pattern => $resolver) {
    if (preg_match($pattern, $uri, $m)) {
        [$script, $params] = $resolver($m);
        // Merge derived params without clobbering real query-string values.
        $_GET += $params;
        require __DIR__ . '/' . $script;
        return true;
    }
}

// Fall back to index.php for anything else (matches the catch-all feel of the app).
http_response_code(404);
require __DIR__ . '/404.php';
return true;
