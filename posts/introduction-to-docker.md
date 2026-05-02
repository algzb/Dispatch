---
title: Introduction to Docker for Web Developers
date: 2025-01-10
categories: Development, DevOps
tags: docker, containers, devops, deployment
slug: introduction-to-docker
image: https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: Docker solves "works on my machine" for good. Here is how to get started as a web developer.
---

Docker packages your application and its dependencies into a container that runs the same way everywhere — on your laptop, your teammate's laptop, and your production server.

## Key concepts

- **Image** — a blueprint for a container (like a class in OOP)
- **Container** — a running instance of an image (like an object)
- **Dockerfile** — instructions for building an image
- **docker-compose** — tool for running multi-container apps

## A minimal PHP Dockerfile

```dockerfile
FROM php:8.2-apache
COPY . /var/www/html/
RUN docker-php-ext-install pdo pdo_mysql
```

## docker-compose for a PHP + MySQL project

```yaml
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html

  db:
    image: mysql:8
    environment:
      MYSQL_DATABASE: myapp
      MYSQL_ROOT_PASSWORD: secret
```

Run it with `docker compose up` and your entire stack starts in seconds.

## Why bother?

- Onboarding a new developer takes minutes instead of hours
- No more version conflicts between projects
- Production environment matches development exactly

Once you start using Docker it is hard to go back.
