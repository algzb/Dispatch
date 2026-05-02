---
title: Getting Started with Node.js
date: 2025-05-01
categories: Development, JavaScript
tags: nodejs, javascript, backend, server
slug: getting-started-with-nodejs
image: https://images.pexels.com/photos/11035380/pexels-photo-11035380.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: Node.js brings JavaScript to the server. Here is what you need to know to build your first backend with it.
---

Node.js lets you run JavaScript outside the browser — on servers, in build tools, in CLI scripts. It's built on Chrome's V8 engine and uses an event-driven, non-blocking I/O model that makes it fast for I/O-heavy workloads.

## Your first server

```js
import { createServer } from 'node:http';

const server = createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('Hello, world!');
});

server.listen(3000, () => {
    console.log('Server running on http://localhost:3000');
});
```

## The npm ecosystem

Node comes with npm — the world's largest package registry. Installing a package:

```bash
npm install express
```

## Building a REST API with Express

```js
import express from 'express';

const app = express();
app.use(express.json());

const posts = [{ id: 1, title: 'Hello World' }];

app.get('/posts', (req, res) => res.json(posts));
app.get('/posts/:id', (req, res) => {
    const post = posts.find(p => p.id === Number(req.params.id));
    post ? res.json(post) : res.status(404).json({ error: 'Not found' });
});

app.listen(3000);
```

## When to use Node.js

- REST APIs and GraphQL servers
- Real-time apps (chat, notifications) via WebSockets
- CLI tools and build scripts
- Serverless functions

Node is not ideal for CPU-intensive work (image processing, machine learning) — use Python or Go for those.
