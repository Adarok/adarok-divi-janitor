# Adarok Divi Janitor

[![Code Quality](https://github.com/adarok/adarok-divi-janitor/actions/workflows/code-quality.yml/badge.svg)](https://github.com/adarok/adarok-divi-janitor/actions/workflows/code-quality.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](LICENSE)

A WordPress plugin that helps you manage your Divi Library by showing where each library item is used throughout your site, with the ability to safely delete unused items.

**Version**: 1.1.0 | **License**: GPL v2+ | **Author**: [Adarok](https://adarok.fi)

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

The plugin uses two sophisticated detection methods:

1. **Global Reference Detection**
   - Searches for `global_module="[id]"`, `template_id="[id]"`, and `saved_tabs="[id]"` attributes
   - Identifies content that is dynamically linked to library items
   - Changes to library item automatically update all global references

2. **Instantiated Content Detection**
   - Extracts unique signatures from library content (module IDs, CSS classes, admin labels)
   - Searches for matching patterns across all content
   - Uses similarity matching to verify copied content
   - Identifies library items that were copied but are now independent

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

### File Structure

```
adarok-divi-janitor/
├── adarok-divi-janitor.php          # Main plugin file (~100 lines)
├── uninstall.php                     # Cleanup script (~25 lines)
├── LICENSE                           # GPL v2 license
├── composer.json                     # PHP dependencies & scripts
├── phpcs.xml                         # Coding standards config
├── phpstan.neon                      # Static analysis config
├── phpstan-bootstrap.php             # WordPress constants for PHPStan
├── Makefile                          # Developer commands
├── .github/
│   └── workflows/
│       └── code-quality.yml          # CI/CD automation
├── includes/
│   ├── class-library-scanner.php    # Core scanning logic (~400 lines)
│   ├── class-admin-page.php         # Admin UI (~350 lines)
│   └── class-ajax-handler.php       # AJAX handler (~200 lines)
└── assets/
    ├── css/admin.css                 # Styles (~450 lines)
    └── js/admin.js                   # JavaScript (~280 lines)
```

### Code Quality & Development Tools

This plugin uses professional-grade static analysis and coding standards tools:

#### Quick Start

```bash
# Install dependencies
composer install

# Run all checks (recommended before commits)
make check

# Individual checks
make lint              # Check coding standards
make lint-fix          # Auto-fix style issues
make analyze           # Run static analysis
```

#### Tool Stack

**PHP_CodeSniffer (PHPCS)** - v3.13.4
- Enforces WordPress Coding Standards
- Security checks (nonce, escaping, sanitization)
- PHP 8.1+ compatibility verification
- Text domain and prefixing validation
- Auto-fix available for most style issues

**PHPStan** - v1.12.32 (Level 5)
- Static type analysis
- Detects potential bugs before runtime
- WordPress-specific rules via szepeviktor/phpstan-wordpress
- IDE integration available

**WordPress Coding Standards (WPCS)** - v3.2.0
- WordPress-Core, WordPress-Docs, WordPress-Extra
- Best practices for WordPress plugin development
- Security-focused rules

**PHPCompatibility** - v2.1.7
- Ensures PHP 8.1+ compatibility
- Tests against multiple PHP versions (8.1, 8.2, 8.3, 8.4)

#### Development Workflow

1. **Before Starting Work**
   ```bash
   composer install  # One-time setup
   ```

2. **During Development**
   - Write code following WordPress coding standards
   - Use `make lint` periodically to check standards
   - Fix issues with `make lint-fix` (auto-fixes style)
   - Run `make analyze` to catch logical errors

3. **Before Committing**
   ```bash
   make check  # Runs both lint and analyze
   ```
   - Fix any reported issues
   - Commit only when all checks pass

4. **Continuous Integration**
   - GitHub Actions runs automatically on push/PR
   - 3 parallel jobs: PHPCS, PHPStan, PHP Compatibility
   - All checks must pass before merging

#### Makefile Commands

| Command | Description |
|---------|-------------|
| `make install` | Install Composer dependencies |
| `make lint` | Check coding standards (PHPCS) |
| `make lint-fix` | Auto-fix coding standards issues |
| `make analyze` | Run static analysis (PHPStan) |
| `make check` | Run all quality checks |

#### Composer Scripts

```bash
composer run-script lint       # Same as make lint
composer run-script lint:fix   # Same as make lint-fix
composer run-script analyze    # Same as make analyze
composer run-script check      # Same as make check
```

#### IDE Integration

**VS Code:**
1. Install extensions:
   - "PHP Sniffer & Beautifier" by ValeryanM
   - "PHPStan" by SanderRonde
2. Configure paths in settings.json:
   ```json
   {
     "phpSniffer.executablesFolder": "./vendor/bin/",
     "phpSniffer.standard": "./phpcs.xml",
     "phpstan.path": "./vendor/bin/phpstan"
   }
   ```

**PHPStorm:**
1. Settings → PHP → Quality Tools → PHP_CodeSniffer
   - Configuration: `./vendor/bin/phpcs`
   - Coding Standard: Custom → `./phpcs.xml`
2. Settings → PHP → Quality Tools → PHPStan
   - Configuration: `./vendor/bin/phpstan`
   - Configuration file: `./phpstan.neon`

#### GitHub Actions CI/CD

The `.github/workflows/code-quality.yml` file runs automatically on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`

**Jobs:**
1. **PHPCS** (PHP 8.1) - Coding standards check
2. **PHPStan** (PHP 8.2) - Static analysis
3. **PHP Compatibility** (PHP 8.1, 8.2, 8.3, 8.4) - Multi-version testing

All jobs run in parallel for fast feedback.

#### Troubleshooting Development Tools

**Composer Install Fails**
```bash
# Clear cache and retry
composer clear-cache
composer install
```

**PHPCS/PHPStan Not Found**
```bash
# Ensure vendor/bin is in PATH or use full path
./vendor/bin/phpcs --version
./vendor/bin/phpstan --version
```

**Too Many Errors**
```bash
# Start with auto-fix
make lint-fix

# Then manually fix remaining issues
make lint
```

**PHPStan Memory Issues**
```bash
# Increase memory limit
php -d memory_limit=512M vendor/bin/phpstan analyze
```

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

Add custom post types to the scanner:

```php
add_filter( 'adarok_divi_janitor_post_types', function( $post_types ) {
    $post_types[] = 'my_custom_post_type';
    return $post_types;
} );
```

### Main Classes

- `Adarok_Divi_Janitor` - Main plugin class (Singleton)
- `Adarok_Divi_Janitor_Library_Scanner` - Core scanning logic (Static methods)
- `Adarok_Divi_Janitor_Admin_Page` - Admin interface (Singleton)
- `Adarok_Divi_Janitor_Ajax_Handler` - AJAX request handler (Singleton)

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
