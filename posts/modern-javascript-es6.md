---
title: Modern JavaScript — ES6 and Beyond
date: 2024-11-05
categories: Development, JavaScript
tags: javascript, es6, frontend
slug: modern-javascript-es6
image: https://images.pexels.com/photos/574071/pexels-photo-574071.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: Arrow functions, destructuring, async/await, and the features that changed how we write JavaScript.
---

ES6 (ECMAScript 2015) was a turning point for JavaScript. It introduced syntax that made code more expressive, concise, and easier to reason about.

## Arrow functions

```js
// Before
function add(a, b) { return a + b; }

// After
const add = (a, b) => a + b;
```

## Destructuring

```js
const { name, age } = user;
const [first, ...rest] = items;
```

## Template literals

```js
const greeting = `Hello, ${name}! You have ${count} messages.`;
```

## Async / Await

Built on top of Promises, async/await makes asynchronous code read like synchronous code.

```js
async function fetchUser(id) {
    const res = await fetch(`/api/users/${id}`);
    return res.json();
}
```

These features are now table stakes for any JavaScript developer. If you haven't adopted them yet, start today.
