# Adarok Divi Janitor

A WordPress plugin that helps you manage your Divi Library by showing where each library item is used throughout your site, and allowing you to safely delete unused items.

## Features

- **Complete Library Overview**: View all Divi Library items in one place
- **Usage Tracking**: See exactly where each library item is used across your site
- **Smart Filtering**: Filter items by all, used, or unused
- **Safe Deletion**: Only delete items that aren't being used anywhere
- **Statistics Dashboard**: Quick overview of your library status
- **Security First**: Built with WordPress security best practices
  - Capability checks
  - Nonce verification
  - Input sanitization and validation
  - Output escaping
  - Prepared SQL statements

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Divi Theme or Divi Builder plugin

## Installation

1. Upload the `adarok-divi-janitor` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Divi > Divi Janitor** (if Divi is active) or **Divi Janitor** in your WordPress admin menu

## Usage

### Viewing Library Items

1. Navigate to **Divi > Divi Janitor** (if Divi theme is active) or find **Divi Janitor** in the main admin menu
2. You'll see statistics showing total items, items in use, and unused items
3. Use the tabs to filter between all items, used items, or unused items

### Checking Usage

- Click on any "X locations" button to see where a library item is used
- The plugin will show you all posts, pages, and custom post types where the item appears

### Deleting Unused Items

1. Navigate to the "Not Used" tab
2. Click the "Delete" button next to any unused item
3. Confirm the deletion
4. The item will be permanently removed from your library

**Note**: You can only delete items that are not currently in use. This prevents accidental deletion of items that are being used on your site.

## Security Features

This plugin follows WordPress security best practices:

- **Capability Checks**: Only administrators can access the plugin
- **Nonce Verification**: All AJAX requests are verified with nonces
- **Input Sanitization**: All user input is sanitized and validated
- **Output Escaping**: All output is properly escaped
- **Prepared Statements**: Database queries use prepared statements
- **Permission Verification**: Double-checks before deletion

## How It Works

The plugin scans your Divi Library and searches for library item references in:

- Pages
- Posts
- Custom post types that support Divi Builder
- Any content using Divi's global modules, sections, rows, or layouts

It looks for Divi shortcodes and module IDs in the post content to determine usage.

## File Structure

```
adarok-divi-janitor/
├── adarok-divi-janitor.php      # Main plugin file
├── README.md                     # This file
├── includes/
│   ├── class-library-scanner.php # Scans library and finds usage
│   ├── class-admin-page.php      # Admin interface
│   └── class-ajax-handler.php    # AJAX request handler
└── assets/
    ├── css/
    │   └── admin.css             # Admin styles
    └── js/
        └── admin.js              # Admin JavaScript
```

## Developer Information

### Filters

**adarok_divi_janitor_post_types**

Filter the post types that are scanned for library item usage.

```php
add_filter( 'adarok_divi_janitor_post_types', function( $post_types ) {
    $post_types[] = 'my_custom_post_type';
    return $post_types;
} );
```

### Actions

The plugin uses standard WordPress hooks and doesn't introduce custom actions at this time.

## Troubleshooting

### Library items not showing

- Make sure you have Divi Theme or Divi Builder plugin installed and activated
- Check that you have library items in Divi Library (Divi > Divi Library)

### Can't delete items

- Ensure the item is not being used anywhere
- Check that you have administrator permissions
- Clear your browser cache and try again

### Usage not detected

- The plugin looks for standard Divi shortcodes and module references
- Custom implementations may not be detected
- Contact support if you believe usage detection is inaccurate

## Support

For support, feature requests, or bug reports, please visit:
https://adarok.com

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Library item listing
- Usage tracking
- Safe deletion of unused items
- Statistics dashboard
- Tabbed filtering interface
