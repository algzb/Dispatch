---
title: Web Performance Optimization — Where to Start
date: 2024-12-03
categories: Development, Performance
tags: performance, web, optimization, core-web-vitals
slug: web-performance-optimization
image: https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: A slow site loses users and rankings. Here are the highest-impact changes you can make today.
---

Performance is a feature. Studies consistently show that every 100ms of load time costs conversions. Here's where to focus first.

## Measure before you optimize

Use [PageSpeed Insights](https://pagespeed.web.dev) or Lighthouse in Chrome DevTools. Focus on Core Web Vitals:

- **LCP** (Largest Contentful Paint) — how fast the main content loads
- **CLS** (Cumulative Layout Shift) — how stable the layout is
- **INP** (Interaction to Next Paint) — how responsive the page is to input

## Images — the biggest win

Images are typically 60–80% of page weight. Fix them first:

- Use modern formats (WebP, AVIF)
- Add `loading="lazy"` to below-the-fold images
- Set explicit `width` and `height` to prevent CLS
- Compress with [Squoosh](https://squoosh.app)

## Reduce render-blocking resources

```html
<!-- Defer non-critical JS -->
<script src="app.js" defer></script>

<!-- Preload critical fonts -->
<link rel="preload" href="font.woff2" as="font" crossorigin>
```

## Use a CDN

Static assets (images, CSS, JS) served from a CDN cut latency for global users dramatically. Most hosting platforms offer this built in.

Small improvements compound. Start with images and JavaScript — you will see results immediately.
