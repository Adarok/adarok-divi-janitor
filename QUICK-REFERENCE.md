# Quick Reference Guide

## 🚀 Quick Start

1. **Activate**: WordPress Admin > Plugins > Activate "Adarok Divi Janitor"
2. **Access**: Divi > Divi Janitor
3. **Use**: Review library, delete unused items

## 📁 File Overview

| File | Purpose | Lines |
|------|---------|-------|
| `adarok-divi-janitor.php` | Main plugin file | ~100 |
| `includes/class-library-scanner.php` | Core scanning logic | ~250 |
| `includes/class-admin-page.php` | Admin interface | ~300 |
| `includes/class-ajax-handler.php` | AJAX handler | ~80 |
| `assets/css/admin.css` | Styles | ~350 |
| `assets/js/admin.js` | JavaScript | ~210 |
| `uninstall.php` | Cleanup | ~25 |

## 🔒 Security Features

- ✅ File access protection
- ✅ Capability checks (admin only)
- ✅ Nonce verification
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Prepared SQL statements
- ✅ Post type verification
- ✅ Usage verification

## 🎯 Main Functions

### Get Library Items
```php
$items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
```

### Find Usage
```php
$items = Adarok_Divi_Janitor_Library_Scanner::find_usage($items);
```

### Delete Item (Safe)
```php
$result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item($post_id);
```

### Get Statistics
```php
$stats = Adarok_Divi_Janitor_Library_Scanner::get_statistics($items);
```

## 🔧 WordPress Hooks

### Actions
- `plugins_loaded` - Load translations
- `admin_menu` - Add admin page
- `admin_enqueue_scripts` - Load assets
- `wp_ajax_adarok_delete_library_item` - Handle deletion

### Filters
- `adarok_divi_janitor_post_types` - Modify searchable post types

## 📊 Statistics

| Metric | Value |
|--------|-------|
| Total Files | 19 |
| PHP Classes | 4 |
| Lines of Code | ~1,315 |
| Security Layers | 8 |
| Documentation Files | 7 |

## 🎨 UI Components

1. **Statistics Cards** - Total, In Use, Not Used
2. **Tab Navigation** - All Items, In Use, Not Used
3. **Data Table** - Sortable list with actions
4. **Usage Details** - Expandable usage information
5. **Delete Buttons** - Only on unused items

## 🔍 Search Patterns

The plugin searches for these patterns in post content:
- `global_module="[id]"`
- `template_id="[id]"`
- `saved_tabs="[id]"`

## ⚙️ Requirements

- WordPress 5.0+
- PHP 7.2+
- Divi Theme or Divi Builder plugin
- Administrator access

## 📝 Customization

### Add Custom Post Types
```php
add_filter('adarok_divi_janitor_post_types', function($post_types) {
    $post_types[] = 'my_custom_type';
    return $post_types;
});
```

## 🐛 Common Issues

| Issue | Solution |
|-------|----------|
| Menu not visible | Check Divi is installed |
| No items shown | Verify library has published items |
| Can't delete | Ensure item is not in use |
| JavaScript error | Check jQuery is loaded |

## 📖 Documentation Files

- `README.md` - User guide
- `INSTALLATION.md` - Setup instructions
- `SECURITY.md` - Security details
- `ARCHITECTURE.md` - Technical architecture
- `SUMMARY.md` - Complete overview
- `QUICK-REFERENCE.md` - This file

## 🎓 Learning Resources

### Understanding the Code
1. Start with `adarok-divi-janitor.php` (main file)
2. Review `class-library-scanner.php` (core logic)
3. Check `class-admin-page.php` (UI)
4. Study `class-ajax-handler.php` (AJAX)
5. Review `admin.js` (JavaScript)
6. Check `admin.css` (styling)

### Key Concepts
- **Singleton Pattern** - Used for main classes
- **Static Methods** - Scanner class uses static methods
- **AJAX Handling** - wp_ajax hooks with nonces
- **WordPress Query** - WP_Query and $wpdb
- **Security Layers** - Multiple validation levels

## 💡 Tips

1. **Testing**: Test on staging first
2. **Backup**: Backup before bulk deletions
3. **Review**: Always check usage before deleting
4. **Performance**: Plugin is fast, no caching needed
5. **Updates**: Keep WordPress and Divi updated

## 🚨 Important Notes

- Only administrators can access the plugin
- Only unused items can be deleted
- Deletion is permanent (uses wp_delete_post with force_delete=true)
- No background processes run
- No data stored in database options

## 📞 Support

**Email**: support@adarok.com
**Website**: https://adarok.com
**Documentation**: See included MD files

## 📜 License

GPL v2 or later

## ✅ Status

**Version**: 1.0.0
**Status**: Complete and Production-Ready
**Last Updated**: October 2025

---

**Quick Access Command**
```bash
cd /Users/sami/Local/vikes/app/public/wp-content/plugins/adarok-divi-janitor
```
