# Agent Instructions — Adarok Divi Janitor

## Project Overview

WordPress plugin that scans the Divi Library (`et_pb_layout` post type), detects global references and instantiated copies across site content, and lets admins safely delete unused items.

## Tech Stack

- PHP 8.1+ · WordPress 6.0+ · Divi Theme/Builder
- jQuery (admin JS) · Dashicons (UI icons)
- No custom DB tables — reads native WP posts & taxonomies

## Quality Gates

Run before every commit:

```bash
make check   # = composer lint + composer analyze
```

- **PHPCS** — WordPress coding standard via `phpcs.xml`
- **PHPStan Level 5** — config in `phpstan.neon`, bootstrap in `phpstan-bootstrap.php`
- **CI** — GitHub Actions runs PHPCS, PHPStan, and PHP Compatibility (8.1–8.4)

## Architecture

| File | Role |
|------|------|
| `adarok-divi-janitor.php` | Bootstrap, constants, singleton loader |
| `includes/class-library-scanner.php` | Static methods: query library items, detect usage, delete |
| `includes/class-admin-page.php` | Admin menu registration, asset enqueue, HTML rendering |
| `includes/class-ajax-handler.php` | AJAX endpoints for single & bulk delete operations |
| `assets/js/admin.js` | Tab switching, AJAX delete flows, DOM updates |
| `assets/css/admin.css` | Admin page styling |
| `uninstall.php` | Cleanup on plugin deletion |

All PHP classes use the singleton pattern. Scanner methods are static.

## Conventions

- **Naming**: `Adarok_Divi_Janitor_*` class prefix, `adarok_divi_janitor_*` function/hook prefix
- **Text domain**: `adarok-divi-janitor`
- **Indentation**: Tabs (WordPress standard)
- **Security pattern**: Every AJAX handler does nonce check → capability check → input validation → action → response
- **Output**: All user-facing strings escaped with `esc_html`, `esc_attr`, `esc_url`; JS uses `.text()` not `.html()` for dynamic content
- **DB queries**: Always use `$wpdb->prepare()` with `esc_like()` for LIKE patterns

## Key Domain Knowledge

### Divi 4 vs Divi 5

| Aspect | Divi 4 | Divi 5 |
|--------|--------|--------|
| Content format | Shortcodes | JSON blocks |
| Global ref marker | `global_module="ID"`, `template_id="ID"` | `"globalModule":"ID"` |
| Scope taxonomy | `et_pb_layout_scope` | `scope` |
| Copy signatures | `module_id`, `module_class`, `admin_label` attributes | `_id` values, nested module keys |

### Bulk Delete Safety

During bulk deletes the plugin temporarily removes **only** `et_`/`ET_`-prefixed callbacks from `before_delete_post`, `wp_trash_post`, and `the_content` to avoid Divi null-reference errors, then restores them after the loop. Never use `remove_all_actions()`/`remove_all_filters()`.

## Common Pitfalls

- `ini_set('memory_limit', ...)` is disallowed by PHPCS — use `wp_raise_memory_limit('admin')` instead
- Inline comments must end with punctuation (`.`, `!`, `?`)
- PHPStan flags `is_null()` on typed properties — use strict `=== null` comparison
- The `callable` keyword is reserved for parameter names — use `$candidate` or similar
- `phpcbf` exits code 1 even on success (when fixes applied) — that's expected
