---
title: CSS Grid — The Complete Guide
date: 2024-11-18
categories: Development, CSS
tags: css, grid, layout, frontend
slug: css-grid-complete-guide
image: https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: CSS Grid is the most powerful layout system in CSS. Here is everything you need to know to use it confidently.
---

CSS Grid gives you two-dimensional control over your layouts — rows and columns at the same time. It replaces a decade of float hacks and table-based layouts.

## Basic setup

```css
.container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}
```

## Naming areas

```css
.layout {
    display: grid;
    grid-template-areas:
        "header header"
        "sidebar main"
        "footer footer";
}

header { grid-area: header; }
aside  { grid-area: sidebar; }
main   { grid-area: main; }
footer { grid-area: footer; }
```

## Auto-fit responsive columns

```css
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}
```

This single rule creates a responsive card grid with no media queries.

## When to use Grid vs Flexbox

- **Grid** — two-dimensional layouts (rows and columns)
- **Flexbox** — one-dimensional layouts (a row or a column)

Use both. They are designed to complement each other.
