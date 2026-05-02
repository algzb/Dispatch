---
title: TypeScript for JavaScript Developers
date: 2025-02-28
categories: Development, JavaScript
tags: typescript, javascript, types, frontend
slug: typescript-for-javascript-developers
image: https://images.pexels.com/photos/4164418/pexels-photo-4164418.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: TypeScript catches entire categories of bugs before they reach production. Here is what you need to know to start using it today.
---

TypeScript is JavaScript with types. It compiles to plain JavaScript, so it runs everywhere JS runs. The types exist only at development time — they vanish at runtime.

## The core value

TypeScript catches bugs at compile time that JavaScript only catches at runtime (if at all).

```ts
function greet(name: string): string {
    return `Hello, ${name}`;
}

greet(42); // Error: Argument of type 'number' is not assignable to 'string'
```

## Basic types

```ts
const age: number = 30;
const name: string = 'Alice';
const active: boolean = true;
const tags: string[] = ['ts', 'js'];
```

## Interfaces

```ts
interface User {
    id: number;
    name: string;
    email: string;
    role?: 'admin' | 'editor' | 'viewer'; // optional, union type
}

function getUser(id: number): Promise<User> {
    return fetch(`/api/users/${id}`).then(r => r.json());
}
```

## Migrating an existing project

You don't have to convert everything at once. Start with `allowJs: true` and rename files to `.ts` one at a time. The compiler works progressively.

The productivity gain comes from your editor knowing the shape of every object. Autocomplete becomes accurate, refactors become safe.
