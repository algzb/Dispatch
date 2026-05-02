# Simple PHP Markdown Blog

A fast, file-based blog engine built with PHP and Markdown. No database, no framework, no bloat — just files, PHP, and a clean admin panel to manage everything from the browser.

**Live demo:** [puropixel.com/blog](https://puropixel.com/blog)

---

## Screenshots

| Homepage | Post |
|---|---|
| ![Homepage](https://imgur.com/DzZnb1z.png) | ![Post](https://imgur.com/Kg1tG8U.png) |

---

## Features

**Content**
- Markdown-powered posts and static pages with YAML front matter
- Auto-generated excerpts, reading time estimates, and featured images
- Static pages auto-populate the navigation menu

**Admin panel** — manage everything from the browser
- Create, edit, and delete posts and pages
- Visual Markdown editor (EasyMDE) with live preview, side-by-side mode, and autosave
- YAML front matter handled via form fields — no raw YAML needed
- Upload images directly from the editor or the media library
- Media library with thumbnail grid, one-click copy URL, and delete
- Site settings editor — update blog name, tagline, URLs, author, and admin credentials

**RSS feed**
- Auto-generated feed at `/feed.php` — works with any RSS reader out of the box

**Technical**
- Zero database — all content is plain `.md` files
- PHP 7.4+ compatible, no Composer required
- Bootstrap 5 UI, fully responsive
- Safe Markdown rendering via Parsedown (safe mode)
- Output escaping with `htmlspecialchars` throughout
- Path traversal protection on all file operations

---

## Installation

1. Upload all files to a PHP 7.4+ web server.
2. Make sure the server can **write** to `posts/`, `pages/`, `assets/uploads/`, and `config.php`.
3. If the blog lives in a subdirectory (e.g. `/blog/`), update `RewriteBase` in `.htaccess`:
   ```apache
   RewriteBase /blog/
   ```
4. Open `config.php` and set your site details and admin credentials:
   ```php
   'site_url'   => 'https://yoursite.com',
   'blog_name'  => 'My Blog',
   'admin_user' => 'admin',
   'admin_pass' => 'your-password',
   ```
5. Visit `yoursite.com/admin.php` to log in and start writing.

---

## Admin panel

Go to `admin.php` and log in. From there:

| Section | What you can do |
|---|---|
| **Posts** | List, create, edit, delete blog posts |
| **Pages** | List, create, edit, delete static pages |
| **Media** | Upload images, copy URLs, delete files |
| **Settings** | Edit all site config without touching files |

### Writing content

The editor is [EasyMDE](https://github.com/Ionaru/easy-markdown-editor) — a full Markdown editor with toolbar, live preview, side-by-side mode, and autosave. Images can be uploaded directly from the toolbar (drag & drop or click).

### Front matter fields

| Field | Description |
|---|---|
| `title` | Post or page title |
| `date` | Publication date (`YYYY-MM-DD`) |
| `slug` | URL identifier — auto-generated from title if left blank |
| `categories` | Comma-separated (posts only) |
| `tags` | Comma-separated (posts only) |
| `image` | Featured image URL |
| `excerpt` | Summary for listings and SEO — auto-generated if omitted |

---

## RSS feed

The feed is available at `/feed.php` and includes the 20 most recent posts with titles, excerpts, dates, and thumbnails. Add it to any RSS reader or use it with feed aggregators.

---

## Project structure

```
├── admin.php              — admin panel (login, CRUD, media, settings)
├── feed.php               — RSS 2.0 feed
├── index.php              — homepage (post listing)
├── post.php               — individual post renderer
├── page.php               — static page renderer
├── 404.php                — not found page
├── config.php             — site config and admin credentials
├── .htaccess              — URL routing and charset
├── includes/
│   ├── functions.php      — shared helpers (parsing, routing, formatting)
│   ├── header.php         — site header partial
│   └── footer.php         — site footer partial
├── libs/
│   └── Parsedown.php      — Markdown parser
├── assets/
│   ├── css/style.css      — custom styles
│   └── uploads/           — uploaded media (created automatically)
├── posts/                 — blog post Markdown files
└── pages/                 — static page Markdown files
```

---

## License

MIT — uses [Parsedown](https://parsedown.org) by Emanuil Rusev.
