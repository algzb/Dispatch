---
title: Web Security Essentials Every Developer Should Know
date: 2025-03-20
categories: Development, Security
tags: security, xss, sql-injection, web
slug: web-security-essentials
image: https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: You don't need to be a security expert to avoid the most common vulnerabilities. Learn the essentials that every developer should know.
---

Most web application breaches come from a small set of well-understood vulnerabilities. Knowing them — and the simple defenses — puts you ahead of the majority of developers.

## SQL Injection

Never concatenate user input into SQL queries.

```php
// Vulnerable
$query = "SELECT * FROM users WHERE email = '$email'";

// Safe — use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

## Cross-Site Scripting (XSS)

Escape all output. Never render raw user input as HTML.

```php
// Vulnerable
echo $_GET['name'];

// Safe
echo htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');
```

## Cross-Site Request Forgery (CSRF)

Add a hidden token to every state-changing form:

```php
// Generate
$_SESSION['csrf'] = bin2hex(random_bytes(32));

// Verify on POST
if (!hash_equals($_SESSION['csrf'], $_POST['csrf_token'])) {
    die('Invalid token');
}
```

## Keep dependencies updated

Most breaches exploit known vulnerabilities in outdated libraries. Run `npm audit` or `composer audit` regularly.

## HTTPS everywhere

Use HTTPS in production. Let's Encrypt provides free certificates. There is no excuse not to.

Security is not a feature to add later — it's a practice to maintain from day one.
