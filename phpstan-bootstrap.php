<?php
/**
 * PHPStan Bootstrap File
 *
 * Defines WordPress constants and functions for static analysis
 *
 * @package Adarok_Divi_Janitor
 */

// Define WordPress constants if not already defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// Divi constants.
if ( ! defined( 'ET_BUILDER_PLUGIN_ACTIVE' ) ) {
	define( 'ET_BUILDER_PLUGIN_ACTIVE', true );
}

// Plugin constants.
if ( ! defined( 'ADAROK_DIVI_JANITOR_VERSION' ) ) {
	define( 'ADAROK_DIVI_JANITOR_VERSION', '1.0.0' );
}

if ( ! defined( 'ADAROK_DIVI_JANITOR_PLUGIN_DIR' ) ) {
	define( 'ADAROK_DIVI_JANITOR_PLUGIN_DIR', WP_PLUGIN_DIR . '/adarok-divi-janitor/' );
}

if ( ! defined( 'ADAROK_DIVI_JANITOR_PLUGIN_URL' ) ) {
	define( 'ADAROK_DIVI_JANITOR_PLUGIN_URL', 'https://example.com/wp-content/plugins/adarok-divi-janitor/' );
}

if ( ! defined( 'ADAROK_DIVI_JANITOR_PLUGIN_BASENAME' ) ) {
	define( 'ADAROK_DIVI_JANITOR_PLUGIN_BASENAME', 'adarok-divi-janitor/adarok-divi-janitor.php' );
}

// Divi functions stub.
if ( ! function_exists( 'et_builder_get_builder_post_types' ) ) {
	/**
	 * Stub for Divi's et_builder_get_builder_post_types function.
	 *
	 * @return array<string>
	 */
	function et_builder_get_builder_post_types() {
		return array( 'page', 'post', 'project' );
	}
}
