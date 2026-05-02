---
title: REST API Design Principles
date: 2025-02-04
categories: Development, Backend
tags: api, rest, backend, http
slug: rest-api-design-principles
image: https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: Good API design makes your service a pleasure to integrate. Bad API design haunts teams for years. Here is how to get it right.
---

A REST API is a contract between your server and every client that uses it. Breaking changes are expensive. Good design from the start pays dividends for years.

## Use nouns, not verbs

Resources are nouns. HTTP methods are the verbs.

```
# bad
GET /getUsers
POST /createUser
DELETE /deleteUser?id=5

# good
GET    /users
POST   /users
DELETE /users/5
```

## HTTP status codes — use them correctly

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request (client error) |
| 401 | Unauthorized |
| 404 | Not Found |
| 422 | Validation error |
| 500 | Server error |

## Version your API

```
/api/v1/users
/api/v2/users
```

Versioning lets you evolve the API without breaking existing clients.

## Consistent error responses

```json
{
    "error": "validation_failed",
    "message": "Email is required",
    "field": "email"
}
```

Every error should have the same shape. Clients should never have to guess.

## Pagination

```
GET /posts?page=2&per_page=20
```

Never return unbounded lists. Always paginate.
