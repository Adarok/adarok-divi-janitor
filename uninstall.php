<?php
/**
 * Uninstall script for Adarok Divi Janitor
 *
 * This file is executed when the plugin is uninstalled via WordPress admin.
 *
 * @package Adarok_Divi_Janitor
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Check if user has permission to uninstall plugins
if ( ! current_user_can( 'activate_plugins' ) ) {
    exit;
}

// No database options or tables to clean up at this time
// The plugin does not store any persistent data

// Clear any transients (if we add any in the future)
delete_transient( 'adarok_divi_janitor_cache' );

// That's it! The plugin is now clean.
