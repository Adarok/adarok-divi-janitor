<?php
/**
 * Plugin Name: Adarok Divi Janitor
 * Plugin URI: https://adarok.com
 * Description: Manage your Divi Library by viewing where items are used and safely deleting unused items.
 * Version: 1.0.0
 * Author: Adarok
 * Author URI: https://adarok.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: adarok-divi-janitor
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
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
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
define( 'ADAROK_DIVI_JANITOR_VERSION', '1.0.0' );
define( 'ADAROK_DIVI_JANITOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADAROK_DIVI_JANITOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADAROK_DIVI_JANITOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The core plugin class
 */
class Adarok_Divi_Janitor {

	/**
	 * The single instance of the class
	 *
	 * @var Adarok_Divi_Janitor
	 */
	protected static $instance = null;

	/**
	 * Main Adarok_Divi_Janitor Instance
	 *
	 * Ensures only one instance of Adarok_Divi_Janitor is loaded or can be loaded.
	 *
	 * @return Adarok_Divi_Janitor - Main instance
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once ADAROK_DIVI_JANITOR_PLUGIN_DIR . 'includes/class-library-scanner.php';
		require_once ADAROK_DIVI_JANITOR_PLUGIN_DIR . 'includes/class-admin-page.php';
		require_once ADAROK_DIVI_JANITOR_PLUGIN_DIR . 'includes/class-ajax-handler.php';
	}

	/**
	 * Hook into actions and filters
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );

		// Initialize admin page.
		if ( is_admin() ) {
			Adarok_Divi_Janitor_Admin_Page::instance();
			Adarok_Divi_Janitor_Ajax_Handler::instance();
		}
	}

	/**
	 * Add custom rewrite rules for clean URLs
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^wp-admin/divi-janitor/?$',
			'index.php?adarok_divi_janitor=1',
			'top'
		);

		add_rewrite_tag( '%adarok_divi_janitor%', '([^&]+)' );

		// Handle the custom query var.
		add_action( 'template_redirect', array( $this, 'handle_custom_url' ) );
	}

	/**
	 * Handle custom URL redirect
	 */
	public function handle_custom_url() {
		if ( get_query_var( 'adarok_divi_janitor' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=divi-janitor' ) );
			exit;
		}
	}

	/**
	 * Load plugin textdomain for translations
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'adarok-divi-janitor',
			false,
			dirname( ADAROK_DIVI_JANITOR_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * Initialize the plugin
 */
function adarok_divi_janitor() {  // phpcs:ignore Squiz.Files.FileDeclaration.MultipleClasses
	return Adarok_Divi_Janitor::instance();
}

/**
 * Activation hook - flush rewrite rules
 */
function adarok_divi_janitor_activate() {
	// Initialize the plugin to register rewrite rules.
	adarok_divi_janitor();

	// Flush rewrite rules.
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'adarok_divi_janitor_activate' );

// Start the plugin.
adarok_divi_janitor();
