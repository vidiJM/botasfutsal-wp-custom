# FS Shortcode Suite

Frontend shortcode and rendering layer for the FS Importer ecosystem.

---

## Overview

**FS Shortcode Suite** is responsible for all frontend interaction in the FS Importer system.

It renders shortcodes, prepares request data and displays results, while delegating all
business logic and execution to other plugins.

---

## Responsibilities

This plugin is responsible for:

- Registering and rendering shortcodes
- Parsing and validating shortcode attributes
- Building immutable request DTOs
- Calling the FS Importer Core facade
- Rendering results and states safely

It explicitly does **not**:

- Execute imports
- Call external APIs
- Access the database
- Contain business rules

---

## Architectural Role

FS Shortcode Suite is designed to keep frontend requests:

- Fast
- Predictable
- Cache-friendly
- Safe for high traffic

Any heavy work is delegated to async workers.

---

## Dependencies

This plugin must be used together with:

- FS Importer Core (domain layer)
- FS Importer Sprinter (async execution)

It is not intended to be used standalone.

---

## Activation Order

1. FS Importer Core
2. FS Importer Sprinter
3. FS Shortcode Suite

---

## License

GPL-2.0-or-later
