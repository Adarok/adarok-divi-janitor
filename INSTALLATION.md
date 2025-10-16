# Installation & Testing Guide

## Prerequisites

Before installing the Adarok Divi Janitor plugin, ensure your WordPress site meets these requirements:

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Divi Theme or Divi Builder plugin installed and activated
- Administrator access to WordPress

## Installation Steps

### Method 1: Manual Installation (Current Setup)

The plugin is already installed in your WordPress plugins directory. To activate it:

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Installed Plugins**
3. Find "Adarok Divi Janitor" in the list
4. Click **Activate**
5. You'll see a new menu item: **Divi > Divi Janitor**

### Method 2: ZIP Upload (For Distribution)

1. Create a ZIP file of the `adarok-divi-janitor` folder
2. Log in to WordPress admin
3. Go to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the ZIP file and click **Install Now**
6. Click **Activate Plugin**

## First-Time Setup

After activation:

1. Go to **Divi > Divi Janitor** in your WordPress admin menu
2. The plugin will automatically scan your Divi Library
3. Review the statistics dashboard showing:
   - Total library items
   - Items currently in use
   - Unused items

No additional configuration is required!

## Testing the Plugin

### Test 1: View Library Items

1. Navigate to **Divi > Divi Janitor**
2. Verify you can see all your Divi Library items
3. Check that items are categorized by type (Layout, Section, Row, Module)

**Expected Result**: All Divi Library items are displayed with correct information

### Test 2: Check Usage Detection

1. Create a test page using Divi Builder
2. Add a library item (section, row, or module) to the page
3. Save the page
4. Return to **Divi > Divi Janitor**
5. Find the library item you just used
6. Click the usage button (e.g., "1 location")

**Expected Result**: The test page appears in the usage list

### Test 3: Filter by Tabs

1. In **Divi > Divi Janitor**, click the "In Use" tab
2. Verify only items with usage are shown
3. Click the "Not Used" tab
4. Verify only items without usage are shown
5. Click the "All Items" tab
6. Verify all items are shown

**Expected Result**: Filtering works correctly and counts update

### Test 4: Delete Unused Item (Safe Test)

**WARNING**: Only test with items you're comfortable deleting!

1. Go to the "Not Used" tab
2. Find an item you don't need (or create a test library item first)
3. Click the **Delete** button
4. Confirm the deletion in the popup
5. Wait for the success message
6. Verify the item is removed from the list
7. Check statistics are updated

**Expected Result**: Item is deleted successfully and removed from view

### Test 5: Attempt to Delete Used Item (Security Test)

1. Manually try to delete a library item that IS being used
2. You can test this via browser console:

```javascript
jQuery.ajax({
    url: adarokDiviJanitor.ajaxUrl,
    type: 'POST',
    data: {
        action: 'adarok_delete_library_item',
        nonce: adarokDiviJanitor.nonce,
        post_id: [ID_OF_USED_ITEM]
    },
    success: function(response) { console.log(response); },
    error: function(xhr) { console.log(xhr); }
});
```

**Expected Result**: Deletion should fail with error message "This library item is currently in use"

### Test 6: Security Tests

#### Test 6a: Non-Admin Access
1. Log out of admin account
2. Log in as Editor or lower role
3. Try to access: `wp-admin/admin.php?page=divi-janitor`

**Expected Result**: Access denied or page not visible

#### Test 6b: Invalid Nonce
1. Open browser console
2. Run this code with a fake nonce:

```javascript
jQuery.ajax({
    url: adarokDiviJanitor.ajaxUrl,
    type: 'POST',
    data: {
        action: 'adarok_delete_library_item',
        nonce: 'invalid_nonce',
        post_id: 123
    },
    success: function(response) { console.log(response); },
    error: function(xhr) { console.log(xhr); }
});
```

**Expected Result**: 403 error with security check failure message

#### Test 6c: Direct File Access
1. Try accessing: `https://yoursite.com/wp-content/plugins/adarok-divi-janitor/includes/class-library-scanner.php`

**Expected Result**: Empty page or 403 error

## Troubleshooting

### Issue: Plugin doesn't appear in menu

**Solution**:
- Check that Divi Theme or Divi Builder is installed
- The menu appears under "Divi", not as a standalone item
- Clear browser cache

### Issue: No library items shown

**Solution**:
- Ensure you have items in Divi Library (Divi > Divi Library)
- Check that items are published, not in trash
- Verify database connectivity

### Issue: Usage not detected

**Solution**:
- Re-save pages that use the library items
- Check that you're using standard Divi global modules
- Custom implementations may not be detected

### Issue: Can't delete items

**Solution**:
- Ensure item is not being used (check "In Use" tab)
- Verify you're logged in as administrator
- Check browser console for JavaScript errors
- Clear browser cache

### Issue: JavaScript not working

**Solution**:
- Check browser console for errors
- Ensure jQuery is loaded
- Try disabling other plugins temporarily
- Clear browser and WordPress cache

## Performance Notes

- The plugin scans content on page load
- For large sites (1000+ posts), initial load may take a few seconds
- Subsequent loads should be faster
- No background processes or scheduled tasks
- All processing happens on-demand

## Uninstallation

To completely remove the plugin:

1. Go to **Plugins > Installed Plugins**
2. Deactivate "Adarok Divi Janitor"
3. Click **Delete**
4. Confirm deletion

The plugin will:
- Remove all its files
- Clean up any transients
- NOT delete any Divi Library items (they remain safe)

## Database Impact

The plugin:
- Does NOT create any database tables
- Does NOT store any options
- Uses only native WordPress queries
- Has zero database footprint

## Getting Help

If you encounter issues:

1. Check this troubleshooting guide
2. Review browser console for errors
3. Check PHP error logs
4. Contact: support@adarok.com

## Next Steps

After successful installation:

1. Review your library items regularly
2. Delete unused items to keep library organized
3. Monitor usage statistics
4. Keep plugin updated for security patches

## Development Testing

For developers testing modifications:

```bash
# Check PHP syntax
find . -name "*.php" -exec php -l {} \;

# Check WordPress coding standards (requires PHPCS)
phpcs --standard=WordPress .

# Run security scan (requires WP-CLI)
wp plugin verify-checksums adarok-divi-janitor
```

## Version Information

- Current Version: 1.0.0
- Tested with WordPress: 6.4
- Tested with Divi: 4.23+
- PHP Compatibility: 7.2 - 8.2

---

**Congratulations!** Your Adarok Divi Janitor plugin is ready to use.
