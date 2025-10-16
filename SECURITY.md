# Security Implementation Guide

## Overview

The Adarok Divi Janitor plugin implements comprehensive security measures following WordPress best practices and OWASP guidelines.

## Security Features Implemented

### 1. File Access Protection

**Implementation**: Direct file access prevention
```php
if ( ! defined( 'WPINC' ) ) {
    die;
}
```

**Location**: All PHP files
**Purpose**: Prevents direct access to PHP files outside WordPress context

### 2. Capability Checks

**Implementation**: User permission verification
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient permissions...', 'adarok-divi-janitor' ) );
}
```

**Location**:
- `class-admin-page.php` (render_page method)
- `class-ajax-handler.php` (delete_library_item method)

**Purpose**: Ensures only administrators can access plugin functionality

### 3. Nonce Verification

**Implementation**: CSRF protection
```php
// Creating nonce
wp_create_nonce( 'adarok_divi_janitor_nonce' )

// Verifying nonce
check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false )
```

**Location**:
- Created in: `class-admin-page.php` (enqueue_assets method)
- Verified in: `class-ajax-handler.php` (delete_library_item method)
- Used in: `assets/js/admin.js` (AJAX requests)

**Purpose**: Prevents Cross-Site Request Forgery (CSRF) attacks

### 4. Input Sanitization & Validation

**Implementation**: Data cleaning and validation
```php
$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

if ( ! $post_id ) {
    wp_send_json_error( array( 'message' => __( 'Invalid library item ID.' ) ), 400 );
}
```

**Location**: `class-ajax-handler.php`

**Functions Used**:
- `absint()`: Converts to absolute integer
- Validation checks before processing

**Purpose**: Ensures user input is clean and valid

### 5. Output Escaping

**Implementation**: XSS prevention
```php
echo esc_html( $item['title'] );
echo esc_url( get_edit_post_link( $item['id'] ) );
echo esc_attr( $item['id'] );
```

**Location**: `class-admin-page.php` (render_page and render_library_table methods)

**Functions Used**:
- `esc_html()`: Escapes HTML content
- `esc_attr()`: Escapes HTML attributes
- `esc_url()`: Escapes URLs
- `esc_js()`: Escapes JavaScript (when needed)

**Purpose**: Prevents Cross-Site Scripting (XSS) attacks

### 6. Prepared SQL Statements

**Implementation**: SQL injection prevention
```php
$query = $wpdb->prepare(
    "SELECT ID, post_title, post_type, post_status
    FROM {$wpdb->posts}
    WHERE post_type = %s
    AND post_status IN ('publish', 'draft', 'pending', 'private')
    AND post_content LIKE %s",
    $post_type,
    '%' . $wpdb->esc_like( $pattern ) . '%'
);
```

**Location**: `class-library-scanner.php` (find_item_usage method)

**Functions Used**:
- `$wpdb->prepare()`: Prepares SQL query
- `$wpdb->esc_like()`: Escapes LIKE wildcards

**Purpose**: Prevents SQL injection attacks

### 7. Post Type Verification

**Implementation**: Ensures operations only on valid post types
```php
$post = get_post( $post_id );

if ( ! $post || $post->post_type !== 'et_pb_layout' ) {
    return new WP_Error( 'invalid_post_type', __( 'Invalid library item.' ) );
}
```

**Location**: `class-library-scanner.php` (delete_library_item method)

**Purpose**: Prevents deletion of non-library items

### 8. Usage Verification Before Deletion

**Implementation**: Prevents deletion of items in use
```php
$usage = self::find_item_usage( $post_id, $post_types );

if ( ! empty( $usage ) ) {
    return new WP_Error( 'item_in_use', __( 'This library item is currently in use...' ) );
}
```

**Location**: `class-library-scanner.php` (delete_library_item method)

**Purpose**: Prevents accidental deletion of active library items

### 9. Error Handling

**Implementation**: Proper error responses
```php
if ( is_wp_error( $result ) ) {
    wp_send_json_error(
        array( 'message' => $result->get_error_message() ),
        400
    );
}
```

**Location**: `class-ajax-handler.php`

**Purpose**: Secure error handling without exposing sensitive information

### 10. Directory Index Protection

**Implementation**: Empty index.php files
```php
<?php
// Silence is golden.
```

**Location**: All directories
- `/`
- `/includes/`
- `/assets/`
- `/assets/css/`
- `/assets/js/`

**Purpose**: Prevents directory listing

## Security Checklist

- [x] Direct file access protection
- [x] Capability checks (manage_options)
- [x] Nonce verification for AJAX
- [x] Input sanitization (absint, etc.)
- [x] Input validation (checks)
- [x] Output escaping (esc_html, esc_attr, esc_url)
- [x] Prepared SQL statements
- [x] SQL LIKE escaping
- [x] Post type verification
- [x] Usage verification before deletion
- [x] Error handling with WP_Error
- [x] Directory index protection
- [x] Textdomain for translations
- [x] Proper WordPress coding standards

## Testing Security

### Manual Security Tests

1. **Direct File Access**
   - Try accessing PHP files directly via URL
   - Expected: Empty response or 403 error

2. **Capability Check**
   - Login as non-admin user
   - Try accessing admin page
   - Expected: Access denied

3. **Nonce Verification**
   - Modify nonce in AJAX request
   - Expected: 403 error

4. **SQL Injection**
   - Try injecting SQL in search patterns
   - Expected: Escaped and safe

5. **XSS Attempts**
   - Create library item with `<script>` in title
   - View in admin page
   - Expected: Properly escaped

6. **CSRF Protection**
   - Submit AJAX request without nonce
   - Expected: 403 error

### Automated Security Scanning

Recommended tools:
- WPScan
- Sucuri SiteCheck
- Wordfence Security Scanner
- Plugin Check (WordPress.org)

## Security Updates

When updating the plugin:
1. Always test on staging first
2. Review all user input handling
3. Check for new WordPress security functions
4. Update nonces if structure changes
5. Test AJAX endpoints thoroughly

## Reporting Security Issues

If you discover a security vulnerability, please email:
security@adarok.com

Do not disclose security issues publicly until they have been addressed.

## References

- [WordPress Plugin Security Handbook](https://developer.wordpress.org/plugins/security/)
- [OWASP Top Ten](https://owasp.org/www-project-top-ten/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Data Validation](https://developer.wordpress.org/apis/security/data-validation/)
- [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/)
- [Escaping Output](https://developer.wordpress.org/apis/security/escaping/)
