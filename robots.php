<?php
$config = require __DIR__ . '/config.php';
$siteUrl = rtrim($config['site_url'], '/');

header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin\n";
echo "\n";
echo "Sitemap: $siteUrl/sitemap.xml\n";
