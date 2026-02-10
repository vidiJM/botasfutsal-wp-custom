# FS Importer

> Modular, async-first WordPress importer architecture designed for scalability, performance, and clean separation of concerns.

---

## 🚀 Overview

**FS Importer** is a modular WordPress importer ecosystem built for **high-traffic environments** and **complex data integrations**.

Instead of a monolithic plugin, FS Importer is composed of multiple plugins, each with a **single, well-defined responsibility**:

- Frontend orchestration (UI / shortcodes)
- Core domain logic (validation, rules, state)
- Asynchronous execution (workers, cron, APIs)

This architecture allows the system to scale safely while remaining maintainable over time.

---

## 🧩 Plugin Ecosystem

This repository is a **monorepo** containing all plugins that form the FS Importer system.

