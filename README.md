# Dispatch

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)

A blog engine that gets out of your way. Write Markdown files, drop them in a folder, done — no database, no setup, no dependencies to install.

Comes with a browser-based admin panel so you can write and publish without touching a file manager or FTP client.

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

Most blogs don't need one. A database adds a server requirement, a backup strategy, a migration step, and a point of failure — for content that is just text.

Dispatch stores everything as plain `.md` files. You can read them, edit them, back them up with a zip, or move them to another host in minutes.

---

## Features

**Writing**
- Markdown posts and static pages with YAML front matter
- Visual editor with live preview, side-by-side mode, and autosave
- No raw YAML — front matter fields are form inputs
- Featured image upload directly from the editor
- Auto-generated excerpts and reading time

**Admin panel**
- Create, edit, and delete posts and pages from the browser
- Media library — upload, preview, copy URL, delete
- Site settings editor — no need to edit config files manually

**Publishing**
- RSS feed at `/feed.xml`, ready for any feed reader
- Clean URLs (`/post/slug`, `/tag/name`, `/category/name`, `/archive`)
- Pagination on the homepage
- Static pages auto-populate the navigation menu

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
2. Make sure the server can **write** to `posts/`, `pages/`, `assets/uploads/`, and `config.php`.
3. Set your site URL and blog name in `config.php`:
   ```php
   'site_url'  => 'https://yoursite.com',
   'blog_name' => 'My Blog',
   ```
4. If Dispatch lives in a subdirectory (e.g. `/blog/`), update **two** settings to match:
   - `config.php`: `'base_path' => '/blog/',`
   - `.htaccess`: `RewriteBase /blog/`
5. Go to `yoursite.com/admin` and log in with the default credentials:
   - **Username:** `admin` **Password:** `demo`
6. Change your password immediately in **Settings** after first login.

> **Security:** The default password is public knowledge. Change it before the site is reachable.

---

## Admin panel

| Section | What you can do |
|---|---|
| **Posts** | List, create, edit, delete blog posts |
| **Pages** | List, create, edit, delete static pages |
| **Media** | Upload images, copy URLs, delete files |
| **Settings** | Edit all site config from the browser |

---

## Content format

Each post or page is a `.md` file with a front matter block at the top:

```markdown
---
title: My Post Title
date: 2026-01-15
slug: my-post-title
categories: Dev, PHP
tags: markdown, bootstrap
image: https://example.com/image.jpg
excerpt: Short summary shown on the homepage.
---

Your Markdown content here.
```

- `slug` is used in the URL (`/post/my-post-title`)
- `slug` is auto-generated from the title if left blank
- `excerpt` is auto-generated if omitted
- `image` falls back to the default image in `config.php`

---

## Project structure

```
├── admin.php              — admin panel (login, CRUD, media, settings)
├── feed.php               — RSS 2.0 feed
├── index.php              — homepage with pagination
├── post.php               — post renderer
├── page.php               — static page renderer
├── 404.php                — not found page
├── config.php             — site config and admin credentials
├── .htaccess              — URL routing
├── includes/
│   ├── functions.php      — shared helpers
│   ├── header.php         — header partial
│   └── footer.php         — footer partial
├── libs/
│   └── Parsedown.php      — Markdown parser
├── assets/
│   ├── css/style.css      — styles
│   └── uploads/           — uploaded media
├── posts/                 — blog post Markdown files
└── pages/                 — static page Markdown files
```

---

## License

MIT — uses [Parsedown](https://parsedown.org) by Emanuil Rusev.
