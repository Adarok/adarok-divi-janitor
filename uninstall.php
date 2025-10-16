<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Adarok_Divi_Janitor
 * @author  Adarok
 * @license GPL-2.0+
 * @link    https://adarok.com
 * @copyright 2025 Adarok
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user has permission to uninstall plugins.
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

// No database options or tables to clean up at this time.
// The plugin does not store any persistent data.

// Clear any transients (if we add any in the future).
delete_transient( 'adarok_divi_janitor_cache' );

// That's it! The plugin is now clean.
