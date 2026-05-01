# Simple PHP Markdown Blog

A portfolio-ready PHP blog built with Markdown content. This project demonstrates clean PHP architecture, reusable helpers, secure content rendering, and responsive Bootstrap design.

## What this project shows

- Markdown-driven blog posts and static portfolio pages
- Shared helper functions for metadata parsing, slug routing, and navigation
- Responsive UI with Bootstrap and custom branding
- Safe output escaping and secure Markdown rendering
- Simple file-based architecture for easy customization

## Installation

1. Clone the repository into your PHP server document root.
2. Make sure the server has read access to the `posts/` and `pages/` directories.
3. Update `config.php` with your own branding.
4. Open `index.php` in your browser.

## Usage

- Add new blog posts as Markdown files in `posts/`.
- Add portfolio pages in `pages/`.
- Include front-matter metadata in each file:

```markdown
---
title: My Portfolio Post
date: 2026-04-30
slug: portfolio-post
excerpt: A short summary shown on the homepage.
image: https://example.com/hero-image.jpg
---

Your markdown content here.
```

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

## Portfolio enhancements

- Add a `pages/about-us.md` page with your skills and experience.
- Use the blog to show real writing, technical thinking, or project notes.
- Keep the project on your portfolio to show reusable and maintainable PHP code.

## License

MIT License

Copyright (c) 2026 Angel Gonzalez

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

This project uses [Parsedown](https://parsedown.org) by Emanuil Rusev for Markdown parsing.