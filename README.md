# Simple PHP Markdown Blog

A reusable PHP Markdown blog script. This project demonstrates clean PHP architecture, reusable helpers, secure content rendering, and responsive Bootstrap design.

## What this project shows

- Markdown-driven blog posts and static pages
- Shared helper functions for metadata parsing, slug routing, and navigation
- Responsive UI with Bootstrap and custom styling
- Safe output escaping and secure Markdown rendering
- Simple file-based architecture for easy customization

## Installation

1. Copy the repository files to a PHP-capable web server directory.
2. Ensure the web server user can read the `posts/`, `pages/`, `includes/`, and `assets/` directories.
3. Open `config.php` and update the following values:
   - `site_url`
   - `blog_name`
   - `tagline`
   - `short_name`
   - `author_name`
   - `footer_text`
4. (Optional) Replace `default_image` with your own fallback image URL.
5. Visit `index.php` in your browser to verify the homepage loads.

## Usage

1. Create a new Markdown file in `posts/` for each blog entry.
2. Create a new Markdown file in `pages/` for each static page.
3. Add a YAML-style front matter block at the top of each Markdown file.
4. Use the `slug` value to link to posts and pages from the site.
5. To view a page, open `page.php?slug=your-page-slug` in your browser.
6. To view a post, open `post.php?slug=your-post-slug` in your browser.

### Example markdown file

```markdown
---
title: Sample Post
date: 2026-04-30
slug: sample-post
excerpt: A short summary shown on the homepage.
image: https://example.com/image.jpg
---

Your markdown content here.
```

### Notes

- The homepage automatically lists posts from the `posts/` folder.
- Static pages are generated from files in the `pages/` folder.
- No database is required; the site is file-based.

## Project structure

- `index.php` — homepage listing of blog posts
- `post.php` — individual post pages
- `page.php` — static page renderer
- `includes/functions.php` — shared helper functions
- `includes/header.php` / `includes/footer.php` — common layout partials
- `config.php` — site configuration and branding
- `assets/css/style.css` — custom styling
- `posts/` — markdown blog posts
- `pages/` — markdown portfolio pages

## Use case

- Use this script as a starter for portfolios, documentation, or project demos.
- Customize the content and metadata to match your own branding or site.
- Keep the project generic so it can be reused across multiple PHP-powered websites.

## License

MIT License

Copyright (c) 2026 PHP Markdown Blog

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

This project uses [Parsedown](https://parsedown.org) by Emanuil Rusev for Markdown parsing.