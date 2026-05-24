# Adarok Divi Janitor

[![Code Quality](https://github.com/adarok/adarok-divi-janitor/actions/workflows/code-quality.yml/badge.svg)](https://github.com/adarok/adarok-divi-janitor/actions/workflows/code-quality.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](LICENSE)

A WordPress plugin that helps you manage your Divi Library by showing where each library item is used throughout your site, with the ability to safely delete unused items.

**Version**: 1.2.0 | **License**: GPL v2+ | **Author**: [Adarok](https://adarok.fi)

---

## 🎯 Features

- **Complete Library Overview** - View all Divi Library items (layouts, sections, rows, modules) in one place
- **Deep Usage Tracking** - Detects both global references and instantiated copies across all content
- **Usage Type Indicators** - Visual icons show whether content is linked (●) or copied (○)
- **Smart Filtering** - 5 tabs: All Items, In Use, Safe to Delete, Not Used, Only Copies
- **Safe Copy Deletion** - Delete library items with only instantiated copies (copies remain intact)
- **Flexible Bulk Operations** - Three bulk delete options for different cleanup scenarios
- **Intelligent Safety Checks** - Prevents deletion of items with active global references
- **Statistics Dashboard** - Real-time overview including safe-to-delete count
- **Security First** - 8 layers of security following WordPress best practices

---

## 📋 Requirements

- WordPress 6.0+
- PHP 8.1+
- Divi Theme or Divi Builder plugin
- Administrator access

---

## 🚀 Installation

1. Upload the `adarok-divi-janitor` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Divi > Divi Janitor** in your admin menu

---

## 📖 Usage Guide

### Viewing Library Items

1. Go to **Divi > Divi Janitor** in WordPress admin
2. View statistics: total items, items in use, safe to delete, unused items
3. Use tabs to filter:
   - **All Items** - Complete library overview
   - **In Use** - Items with any usage (global or copies)
   - **Safe to Delete** - Items with no usage OR only copies
   - **Not Used** - Items with zero usage anywhere
   - **Only Copies** - Items with instantiated copies only

### Understanding Usage Types

Each location where a library item is used displays a colored icon:

| Icon | Type | Description | Can Delete? |
|------|------|-------------|-------------|
| **● Green** | Global Reference | Linked to library - updates automatically when library item changes | ❌ No |
| **○ Red** | Instantiated Copy | Independent copy - won't update with library changes | ✅ Yes (safe) |

### Checking Where Items Are Used

1. Find a library item in the table
2. Click the usage button (e.g., "5 locations")
3. View expandable list showing all pages/posts using the item
4. Each usage shows the page title, post type, and usage type icon
5. Click links to edit the content directly

### Safe Deletion Rules

**What can be deleted:**
- ✅ Items with **no usage** anywhere
- ✅ Items with **only instantiated copies** (○) - copies remain intact
- ❌ Items with **global references** (●) - cannot be deleted

**Why copies are safe to delete:**
Instantiated copies are independent content that was copied from the library item. Deleting the library item removes it from your library but **does not affect the copied content** in your pages.

### Deleting Library Items

**Individual Deletion:**
1. Navigate to **Safe to Delete**, **Not Used**, or **Only Copies** tab
2. Click **Delete** next to any item
3. Confirm the deletion (different messages for items with copies)
4. Item is permanently removed and statistics update automatically

**Bulk Deletion Options:**

1. **Delete All Safe Items** (Safe to Delete tab)
   - Deletes items with no usage + items with only copies
   - Most comprehensive safe cleanup option

2. **Delete All Unused Items** (Not Used tab)
   - Deletes only items with zero usage
   - Most conservative option

3. **Delete All Copy-Only Items** (Only Copies tab)
   - Deletes only items that have instantiated copies
   - Copies in your content remain intact

> **Important**: Items with global references (●) are **never** deleted in bulk operations. They must be manually unlinked first.

---

## 🔍 How It Works

### Detection Methods

Divi 4 and Divi 5 store library usage very differently, so the scanner keeps separate playbooks for each engine while presenting a unified result set in the dashboard.

1. **Global Reference Detection**
   - **Divi 4 (Classic Builder / shortcodes):** scans shortcode attributes such as `global_module="[id]"`, `template_id="[id]"`, and `saved_tabs="[id]"`, and checks the legacy `et_pb_layout_scope` taxonomy for the `global` term.
   - **Divi 5 (Block Builder / JSON):** searches the JSON payload for keys like `"globalModule":"[id]"` and inspects the modern `scope` taxonomy to find globally scoped layouts.
   - Any match is treated as a live link: editing the library item automatically changes every location flagged as “Global Reference”.

2. **Instantiated Content Detection**
   - **Divi 4:** analyses shortcode markup to capture module IDs, CSS classes, and admin labels that tend to remain unique when a layout is copied into a page.
   - **Divi 5:** pulls distinctive block identifiers from the JSON (for example `_id` values and deeply nested module signatures) and looks for those fragments inside block content.
   - A lightweight similarity check verifies that the target content still resembles the library item, ensuring we only flag true instantiated copies.
   - Matches appear as “Instantiated Copy” usage, meaning the content is independent and safe to delete from the library.

### Content Scanning

The plugin scans:
- All published, draft, pending, and private content
- Pages, posts, and any custom post types that support Divi Builder
- Post content using Divi's sections, rows, modules, and layouts

---

## 🔒 Security

This plugin implements comprehensive security measures:

- ✅ **Direct File Access Protection** - Blocks direct PHP file access
- ✅ **Capability Checks** - Only administrators can access (manage_options)
- ✅ **Nonce Verification** - CSRF protection on all AJAX requests
- ✅ **Input Sanitization** - All user input is cleaned (absint, validation)
- ✅ **Output Escaping** - XSS prevention (esc_html, esc_attr, esc_url)
- ✅ **Prepared SQL Statements** - SQL injection prevention ($wpdb->prepare)
- ✅ **Post Type Verification** - Only operates on et_pb_layout posts
- ✅ **Usage Verification** - Blocks deletion of items in use

---

## 🛠️ Developer Information

For architecture, conventions, and domain knowledge see [`AGENTS.md`](AGENTS.md).

### Quick Start

```bash
composer install   # One-time setup
make check         # Run PHPCS + PHPStan (must pass before commit)
make lint-fix      # Auto-fix style issues
```

GitHub Actions CI runs PHPCS, PHPStan, and PHP Compatibility (8.1–8.4) on every push/PR.

### WordPress Hooks

**Actions:**
- `plugins_loaded` - Load textdomain
- `admin_menu` - Register admin page
- `admin_enqueue_scripts` - Load CSS/JS assets
- `wp_ajax_adarok_delete_library_item` - Handle single deletion
- `wp_ajax_adarok_bulk_delete_unused` - Handle bulk deletion

**Filters:**
- `adarok_divi_janitor_post_types` - Customize searchable post types

### Custom Post Types Filter

```php
add_filter( 'adarok_divi_janitor_post_types', function( $post_types ) {
    $post_types[] = 'my_custom_post_type';
    return $post_types;
} );
```

---

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Menu not visible** | Ensure Divi Theme or Builder is installed and activated |
| **No library items shown** | Check that you have published items in Divi Library |
| **Can't delete items** | Verify item is not in use; check administrator permissions |
| **Usage not detected** | Re-save pages; plugin detects standard Divi implementations |
| **JavaScript errors** | Check jQuery is loaded; clear browser cache; disable conflicting plugins |

### Performance Notes

- Scans happen on-demand when viewing the admin page
- No background processes or scheduled tasks
- No database tables created (uses native WordPress tables)
- Efficient prepared queries with minimal overhead
- For large sites (1000+ posts), initial scan may take a few seconds

---

## 🗑️ Uninstallation

To completely remove the plugin:

1. Deactivate via **Plugins > Installed Plugins**
2. Click **Delete**
3. Plugin files and transients are automatically cleaned up
4. **Your Divi Library items are NOT deleted** (they remain safe)

---

## 📞 Support

- **Website**: [https://adarok.com](https://adarok.com)
- **Email**: support@adarok.com
- **Issues**: Report bugs or request features via support

---

## 📜 License

This plugin is licensed under the **GNU General Public License v2 or later**.

See [LICENSE](LICENSE) file for full text.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.

---

## 📝 Changelog

### Version 1.2.0 - May 2026
- ✅ Full Divi 5 (released) compatibility: detect `wp:divi/global-layout` block references
- ✅ Divi 5 block signature extraction for instantiated copy detection (`cssId`, `cssClasses`, `adminLabel`, `_id`)
- ✅ Scan Theme Builder post types (`et_header_layout`, `et_body_layout`, `et_footer_layout`)
- ✅ Catch Divi 5 namespaced callbacks (`ET\Builder\...`) during bulk delete hook removal
- ✅ Prevent false "safe to delete" by checking Divi 5 global pattern in copy detection
- ✅ Consolidate LIKE clauses into single query per post type to reduce DB load
- ✅ Prioritise `scope` taxonomy over legacy `et_pb_layout_scope`

### Version 1.1.1 - October 2025
- ✅ Hardened admin notices against XSS by ensuring messages render as plain text
- ✅ Disabled only the specific Divi dynamic asset callbacks during bulk deletes to preserve security hooks

### Version 1.1.0 - October 2025
- ✅ Added Divi 5 `globalModule` detection to global usage scanner
- ✅ Added support for detecting global scope taxonomy flags
- ✅ Display green globe indicator for globally scoped items in the admin table
- ✅ General refinements to make global references more visible at a glance

### Version 1.0.0 - October 2025
- ✅ Initial release
- ✅ Library item listing with statistics
- ✅ Deep usage tracking (global + instantiated)
- ✅ Usage type indicators (visual icons)
- ✅ Individual item deletion
- ✅ Bulk deletion of unused items
- ✅ Tabbed filtering interface
- ✅ Usage breakdown statistics
- ✅ Comprehensive security implementation
- ✅ Responsive design
- ✅ Full documentation

---

## 🎓 Credits

**Developed by**: Adarok
**Copyright**: © 2025 Adarok
**Text Domain**: adarok-divi-janitor

Built with ❤️ for the Divi community.
