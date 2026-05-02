---
title: Git Workflow for Small Teams
date: 2024-12-15
categories: Development, DevOps
tags: git, workflow, collaboration, devops
slug: git-workflow-for-teams
image: https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1
excerpt: A practical branching strategy that keeps your main branch clean and your team moving fast.
---

Git is powerful, but without a clear workflow it becomes a source of friction. Here is the strategy that works well for teams of 2–10 developers.

## The rules

1. `main` is always deployable
2. Work happens on feature branches
3. Merge via pull request — no direct pushes to main
4. Delete branches after merging

## Branching convention

```
main
├── feature/user-auth
├── fix/login-redirect
└── chore/update-deps
```

Use prefixes: `feature/`, `fix/`, `chore/`, `docs/`.

## A typical day

```bash
git checkout main
git pull
git checkout -b feature/new-dashboard

# ... make changes ...

git add -p                          # stage selectively
git commit -m "Add dashboard layout"
git push -u origin feature/new-dashboard
# open a pull request
```

## Commit messages that help

Good commit messages explain the **why**, not the what:

```
# bad
fix bug

# good
Redirect to login when session expires instead of showing blank page
```

A readable git history is documentation that never goes stale.
