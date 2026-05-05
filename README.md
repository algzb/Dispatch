# Dispatch

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)

A flat-file website engine for building business websites fast. Write content in Markdown, manage everything from the browser — no database, no setup, no dependencies to install.

Comes with a browser-based admin panel so you can publish and update without touching a file manager or FTP client.

**Live demo:** [puropixel.com/blog](https://puropixel.com/blog)

---

## Screenshots

| Homepage | Post |
|---|---|
| ![Homepage](https://i.imgur.com/DzZnb1z.png) | ![Post](https://i.imgur.com/Kg1tG8U.png) |

**Admin panel**

![Admin](https://i.imgur.com/FUvtyWB.png)

---

## Why no database?

Most business websites don't need one. A database adds a server requirement, a backup strategy, a migration step, and a point of failure — for content that is just text.

Dispatch stores everything as plain `.md` files. You can read them, edit them, back them up with a zip, or move them to another host in minutes.

---

## Features

**Content**
- Markdown articles and static pages with YAML front matter
- Product catalog with price, stock status, and external buy links
- Visual editor with live preview, side-by-side mode, and autosave
- No raw YAML — front matter fields are form inputs
- Featured image upload directly from the editor
- Auto-generated excerpts and reading time

**Admin panel**
- Create, edit, and delete articles, pages, and products from the browser
- Media library — upload, preview, copy URL, delete
- Site settings editor — name, tagline, hero banner, credentials

**Publishing**
- Customizable homepage hero banner with image, text, and CTA button
- Clean URLs (`/post/slug`, `/store`, `/product/slug`, `/page/slug`, `/archive`)
- RSS feed at `/feed.xml`
- Pagination on the homepage
- Static pages auto-populate the navigation menu
- Store link appears in navigation only when products exist

**Technical**
- No database — all content is plain `.md` files
- PHP 7.4+, no Composer, no framework
- Bootstrap 5, fully responsive
- Safe Markdown rendering via Parsedown
- CSRF protection on all admin forms
- Path traversal protection on all file operations

---

## Getting started

1. Upload the files to any PHP 7.4+ web server with `mod_rewrite` enabled.
2. Make sure the server can **write** to `posts/`, `pages/`, `products/`, `assets/uploads/`, and `config.php`.
3. Set your site URL and name in `config.php`:
   ```php
   'site_url'  => 'https://yoursite.com',
   'blog_name' => 'My Business',
   ```
4. If Dispatch lives in a subdirectory (e.g. `/mysite/`), update **two** settings to match:
   - `config.php`: `'base_path' => '/mysite/',`
   - `.htaccess`: `RewriteBase /mysite/`
5. Go to `yoursite.com/admin` and log in with the default credentials:
   - **Username:** `admin` **Password:** `demo`
6. Change your password immediately in **Settings** after first login.

> **Security:** The default password is public knowledge. Change it before the site is reachable.

---

## Nginx configuration

If your server runs Nginx instead of Apache, create a server block like this. The rewrite rules mirror what `.htaccess` does for Apache.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/dispatch;
    index index.php;

    # If installed in a subdirectory, adjust root and the location blocks below.

    # Deny access to hidden files (.htaccess, .git, etc.)
    location ~ /\. {
        deny all;
    }

    # Serve real files and directories directly; otherwise rewrite
    location / {
        try_files $uri $uri/ @rewrites;
    }

    location @rewrites {
        rewrite ^/sitemap\.xml$            /sitemap.php    last;
        rewrite ^/robots\.txt$             /robots.php     last;
        rewrite ^/feed\.xml$               /feed.php       last;
        rewrite ^/admin/?$                 /admin.php      last;
        rewrite ^/post/([^/]+)/?$          /post.php?slug=$1   last;
        rewrite ^/page/([^/]+)/?$          /page.php?slug=$1   last;
        rewrite ^/store/?$                 /store.php      last;
        rewrite ^/product/([^/]+)/?$       /product.php?slug=$1 last;
        rewrite ^/tag/([^/]+)/?$           /archive.php?tag=$1 last;
        rewrite ^/category/([^/]+)/?$      /archive.php?category=$1 last;
        rewrite ^/archive/?$               /archive.php    last;
        return 404;
    }

    # Pass PHP files to PHP-FPM
    location ~ \.php$ {
        include        fastcgi_params;
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;  # adjust to your PHP-FPM socket
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    error_page 404 /404.php;
    error_page 500 /500.php;
}
```

> Adjust `fastcgi_pass` to match your PHP-FPM socket or TCP address (e.g. `127.0.0.1:9000`).

---

## Admin panel

| Section | What you can do |
|---|---|
| **Posts** | List, create, edit, delete articles |
| **Pages** | List, create, edit, delete static pages |
| **Products** | List, create, edit, delete store products |
| **Media** | Upload images, copy URLs, delete files |
| **Settings** | Edit all site config from the browser |

---

## Content format

Each article, page, or product is a `.md` file with a front matter block at the top.

**Articles and pages:**
```markdown
---
title: My Article Title
date: 2026-01-15
slug: my-article-title
categories: News, Updates
tags: php, web
image: https://example.com/image.jpg
excerpt: Short summary shown on the homepage.
---

Your Markdown content here.
```

**Products:**
```markdown
---
title: Product Name
slug: product-name
price: 29.99
currency: USD
buy_url: https://buy.stripe.com/your-link
stock: In Stock
image: https://example.com/product.jpg
excerpt: Short product description.
---

Full product description in Markdown.
```

- `slug` is used in the URL and auto-generated from the title if left blank
- `excerpt` is auto-generated if omitted
- `image` falls back to the default image in `config.php`
- Set `stock` to `Out of Stock` to disable the buy button

---

## Project structure

```
├── admin.php              — admin panel (login, CRUD, media, settings)
├── feed.php               — RSS 2.0 feed
├── index.php              — homepage with hero banner and article grid
├── post.php               — article renderer
├── page.php               — static page renderer
├── store.php              — product listing
├── product.php            — single product page
├── archive.php            — article archive with tag/category filters
├── 404.php                — not found page
├── 500.php                — server error page
├── sitemap.php            — auto-generated XML sitemap
├── robots.php             — dynamic robots.txt
├── config.php             — site config and admin credentials
├── .htaccess              — URL routing (Apache)
├── includes/
│   ├── functions.php      — shared helpers
│   ├── header.php         — header and navigation
│   └── footer.php         — footer partial
├── libs/
│   └── Parsedown.php      — Markdown parser
├── assets/
│   ├── css/style.css      — styles
│   └── uploads/           — uploaded media
├── posts/                 — article Markdown files
├── pages/                 — static page Markdown files
└── products/              — product Markdown files
```

---

## License

MIT — uses [Parsedown](https://parsedown.org) by Emanuil Rusev.
