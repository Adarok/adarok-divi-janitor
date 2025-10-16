# Plugin Architecture Diagram

## Component Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    Adarok Divi Janitor Plugin                   │
│                         Version 1.0.0                            │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
        ┌────────────────────────────────────────────┐
        │     Main Plugin File                       │
        │  (adarok-divi-janitor.php)                 │
        │  - Initialize plugin                       │
        │  - Define constants                        │
        │  - Load dependencies                       │
        └────────────────────────────────────────────┘
                                 │
                 ┌───────────────┼───────────────┐
                 │               │               │
                 ▼               ▼               ▼
    ┌────────────────┐ ┌─────────────────┐ ┌──────────────────┐
    │ Library Scanner│ │   Admin Page    │ │  AJAX Handler    │
    │    (Class)     │ │    (Class)      │ │    (Class)       │
    └────────────────┘ └─────────────────┘ └──────────────────┘
```

## Data Flow

### Viewing Library Items

```
WordPress Admin Dashboard
         │
         ▼
  [Divi > Divi Janitor]
         │
         ▼
  Admin Page Renders
         │
         ▼
  Library Scanner::get_library_items()
         │
         ▼
  WP_Query fetches et_pb_layout posts
         │
         ▼
  Library Scanner::find_usage()
         │
         ▼
  Database query for usage patterns
         │
         ▼
  Display in table with statistics
```

### Deleting Library Item

```
User clicks [Delete] button
         │
         ▼
  JavaScript confirmation dialog
         │
         ▼
  AJAX request with nonce
         │
         ▼
  AJAX Handler::delete_library_item()
         │
         ├─────► Verify nonce
         ├─────► Check capabilities
         ├─────► Validate post ID
         │
         ▼
  Library Scanner::delete_library_item()
         │
         ├─────► Verify post type
         ├─────► Check usage
         ├─────► Delete if safe
         │
         ▼
  Return success/error
         │
         ▼
  Update UI (remove row, update stats)
```

## Class Relationships

```
┌───────────────────────────────────────────────────────────────┐
│                  Adarok_Divi_Janitor                          │
│                    (Main Class)                               │
│                                                               │
│  - instance() : Adarok_Divi_Janitor                          │
│  - includes() : void                                          │
│  - init_hooks() : void                                        │
│  - load_textdomain() : void                                   │
└───────────────────────────────────────────────────────────────┘
                           │
                           │ creates
                           │
           ┌───────────────┼───────────────┐
           │               │               │
           ▼               ▼               ▼
┌─────────────────┐ ┌──────────────────┐ ┌─────────────────────┐
│ Library_Scanner │ │   Admin_Page     │ │   AJAX_Handler      │
│   (Static)      │ │   (Singleton)    │ │   (Singleton)       │
├─────────────────┤ ├──────────────────┤ ├─────────────────────┤
│ + get_library_  │ │ + instance()     │ │ + instance()        │
│   items()       │ │ + add_admin_menu()│ │ + delete_library_   │
│ + find_usage()  │ │ + enqueue_assets()│ │   item()            │
│ + delete_       │ │ + render_page()  │ └─────────────────────┘
│   library_item()│ │ + render_library_│
│ + get_          │ │   table()        │
│   statistics()  │ └──────────────────┘
└─────────────────┘
```

## Security Layers

```
User Request
     │
     ▼
┌─────────────────────────────────┐
│  Layer 1: File Access Check     │
│  if (!defined('WPINC')) die;    │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 2: Capability Check      │
│  current_user_can('manage_      │
│  options')                      │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 3: Nonce Verification    │
│  check_ajax_referer()           │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 4: Input Sanitization    │
│  absint(), validation checks    │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 5: Post Type Check       │
│  Verify et_pb_layout type       │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 6: Usage Verification    │
│  Check if item is in use        │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 7: Prepared SQL          │
│  $wpdb->prepare()               │
└─────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────┐
│  Layer 8: Output Escaping       │
│  esc_html(), esc_attr(),        │
│  esc_url()                      │
└─────────────────────────────────┘
     │
     ▼
  Secure Operation Complete
```

## Database Interaction

```
WordPress Database (wp_posts)
         │
         ▼
┌─────────────────────────────────┐
│  Query et_pb_layout posts       │
│  (Divi Library Items)           │
└─────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│  Search post_content for:       │
│  - global_module="[id]"         │
│  - template_id="[id]"           │
│  - saved_tabs="[id]"            │
└─────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│  Return matching posts with:    │
│  - ID, title, type, status      │
│  - Edit URL, view URL           │
└─────────────────────────────────┘
```

## File Organization

```
Plugin Root
│
├── Core Files
│   ├── adarok-divi-janitor.php  (Main plugin file - 100 lines)
│   ├── uninstall.php            (Cleanup - 25 lines)
│   └── index.php                (Security)
│
├── PHP Classes (includes/)
│   ├── class-library-scanner.php  (Core logic - 250 lines)
│   ├── class-admin-page.php       (UI rendering - 300 lines)
│   ├── class-ajax-handler.php     (AJAX handling - 80 lines)
│   └── index.php                  (Security)
│
├── Frontend Assets (assets/)
│   ├── css/
│   │   ├── admin.css             (Styles - 350 lines)
│   │   └── index.php             (Security)
│   ├── js/
│   │   ├── admin.js              (Interactions - 210 lines)
│   │   └── index.php             (Security)
│   └── index.php                 (Security)
│
├── Documentation
│   ├── README.md                 (User guide)
│   ├── INSTALLATION.md           (Setup guide)
│   ├── SECURITY.md               (Security docs)
│   ├── SUMMARY.md                (Complete overview)
│   └── ARCHITECTURE.md           (This file)
│
└── Localization (languages/)
    └── README.md                 (Translation guide)

Total: ~1,315 lines of code
```

## WordPress Hooks Used

### Actions
```
plugins_loaded
    └─> load_textdomain()

admin_menu
    └─> add_admin_menu()

admin_enqueue_scripts
    └─> enqueue_assets()

wp_ajax_adarok_delete_library_item
    └─> delete_library_item()
```

### Filters
```
adarok_divi_janitor_post_types
    └─> Modify searchable post types
```

## User Interface Structure

```
┌─────────────────────────────────────────────────────────────┐
│  Divi Janitor Page                                          │
├─────────────────────────────────────────────────────────────┤
│  Introduction Text                                          │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐          │
│  │   Total     │ │   In Use    │ │  Not Used   │          │
│  │     50      │ │     35      │ │     15      │          │
│  │   Items     │ │   Items     │ │   Items     │          │
│  └─────────────┘ └─────────────┘ └─────────────┘          │
├─────────────────────────────────────────────────────────────┤
│  [All Items (50)] [In Use (35)] [Not Used (15)]            │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Library Item  │ Type │ Modified │ Used In │ Actions │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ Header Layout │ Layout│ Oct 1   │ 5 locations│     │   │
│  │ CTA Section   │ Section│ Oct 2  │ 3 locations│     │   │
│  │ Pricing Row   │ Row   │ Oct 5   │ Not used  │Delete│   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Performance Considerations

### Optimization Strategies
```
1. Lazy Loading
   - Usage details loaded on demand
   - Expandable sections

2. Efficient Queries
   - Single query for all library items
   - Prepared statements for usage search
   - No N+1 query problems

3. Client-Side Processing
   - Tab switching via JavaScript
   - No page reloads for filtering
   - Smooth animations

4. Minimal Database Impact
   - No custom tables
   - No stored options
   - No caching overhead
```

## Execution Timeline

```
Plugin Activation
    ↓
Initialize Main Class (Singleton)
    ↓
Load Dependencies
    ├─> Library Scanner
    ├─> Admin Page
    └─> AJAX Handler
    ↓
Register Hooks
    ├─> admin_menu
    ├─> admin_enqueue_scripts
    └─> wp_ajax_*
    ↓
Ready for Use

User Visits Page
    ↓
Render Admin Page
    ↓
Fetch Library Items (WP_Query)
    ↓
Find Usage (Database Search)
    ↓
Calculate Statistics
    ↓
Display Interface
    ↓
User Interacts
    ├─> Click Tab (JavaScript)
    ├─> View Usage (Expand/Collapse)
    └─> Delete Item (AJAX)
```

## Error Handling Flow

```
Operation Attempt
    │
    ▼
Try: Execute Operation
    │
    ├─> Success
    │   └─> Return success message
    │
    └─> Failure
        │
        ▼
    Is WP_Error?
        │
        ├─> Yes: Get error message
        │   └─> Return to user
        │
        └─> No: Generic error
            └─> Log & return safe message
```

## Deployment Checklist

- [x] All security layers implemented
- [x] All files have proper headers
- [x] Directory protection in place
- [x] WordPress coding standards followed
- [x] Translations ready (text domain)
- [x] Uninstall script included
- [x] Documentation complete
- [x] No hardcoded values
- [x] Proper error handling
- [x] User feedback implemented
- [x] Responsive design
- [x] Browser compatibility tested

## Statistics

- **Total Files**: 18
- **PHP Files**: 9
- **CSS Files**: 1
- **JS Files**: 1
- **Documentation Files**: 7
- **Total Lines of Code**: ~1,315
- **Security Layers**: 8
- **Classes**: 4
- **Security Checks**: Multiple per operation
- **WordPress Hooks**: 4 actions, 1 filter

---

**Architecture Status**: ✅ Complete and Production-Ready
