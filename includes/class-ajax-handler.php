<?php
/**
 * AJAX Handler Class
 *
 * Handles AJAX requests for the Divi Janitor
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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Handles AJAX requests for Divi Janitor operations.
 */
class Adarok_Divi_Janitor_Ajax_Handler {

	/**
	 * The single instance of the class
	 *
	 * @var Adarok_Divi_Janitor_Ajax_Handler
	 */
	protected static $instance = null;

	/**
	 * Main Instance
	 *
	 * @return Adarok_Divi_Janitor_Ajax_Handler - Main instance
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
		add_action( 'wp_ajax_adarok_delete_library_item', array( $this, 'delete_library_item' ) );
		add_action( 'wp_ajax_adarok_bulk_delete_unused', array( $this, 'bulk_delete_unused' ) );
		add_action( 'wp_ajax_adarok_bulk_delete_safe', array( $this, 'bulk_delete_safe' ) );
		add_action( 'wp_ajax_adarok_bulk_delete_copies', array( $this, 'bulk_delete_copies' ) );
	}

	/**
	 * Handle AJAX request to delete a library item
	 */
	public function delete_library_item() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Get and validate post ID.
		$post_id      = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$force_delete = isset( $_POST['force_delete'] ) && $_POST['force_delete'] === 'true';

		if ( ! $post_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid library item ID.', 'adarok-divi-janitor' ),
				),
				400
			);
		}

		// Attempt to delete the library item.
		$result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $post_id, $force_delete );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		// Success.
		wp_send_json_success(
			array(
				'message' => __( 'Library item deleted successfully.', 'adarok-divi-janitor' ),
				'post_id' => $post_id,
			)
		);
	}

	/**
	 * Handle AJAX request to bulk delete all safe-to-delete library items
	 */
	public function bulk_delete_safe() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Increase limits for bulk operations.
		set_time_limit( 300 ); // 5 minutes.
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		// Temporarily disable Divi dynamic asset callbacks to prevent null reference errors.
		$disabled_hooks = $this->disable_divi_dynamic_asset_hooks();

		// Get all library items.
		$library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
		$library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );

		$deleted_count = 0;
		$failed_count  = 0;
		$deleted_ids   = array();
		$errors        = array();

		// Find and delete safe-to-delete items (unused OR only copies).
		foreach ( $library_items as $item ) {
			$is_safe = empty( $item['usage'] ) || Adarok_Divi_Janitor_Library_Scanner::has_only_copy_usage( $item['usage'] );

			if ( $is_safe ) {
				$force_delete = ! empty( $item['usage'] ); // Force if has copies.
				$result       = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $item['id'], $force_delete );

				if ( is_wp_error( $result ) ) {
					++$failed_count;
					$errors[] = sprintf(
						__( '%1$s: %2$s', 'adarok-divi-janitor' ),
						$item['title'],
						$result->get_error_message()
					);
				} else {
					++$deleted_count;
					$deleted_ids[] = $item['id'];
				}
			}
		}

		// Build response message.
		$response = array(
			'type'   => 'error',
			'data'   => array(
				'message' => __( 'No safe-to-delete items found.', 'adarok-divi-janitor' ),
			),
			'status' => 400,
		);

		if ( $deleted_count > 0 && 0 === $failed_count ) {
			$response = array(
				'type'   => 'success',
				'data'   => array(
					'message'       => sprintf(
						_n(
							'%d library item deleted successfully.',
							'%d library items deleted successfully.',
							$deleted_count,
							'adarok-divi-janitor'
						),
						$deleted_count
					),
					'deleted_count' => $deleted_count,
					'deleted_ids'   => $deleted_ids,
				),
				'status' => null,
			);
		} elseif ( $deleted_count > 0 && $failed_count > 0 ) {
			$response = array(
				'type'   => 'success',
				'data'   => array(
					'message'       => sprintf(
						__( '%1$d items deleted, %2$d failed. Errors: %3$s', 'adarok-divi-janitor' ),
						$deleted_count,
						$failed_count,
						implode( '; ', $errors )
					),
					'deleted_count' => $deleted_count,
					'failed_count'  => $failed_count,
					'deleted_ids'   => $deleted_ids,
					'errors'        => $errors,
				),
				'status' => null,
			);
		} elseif ( 0 === $deleted_count && $failed_count > 0 ) {
			$response = array(
				'type'   => 'error',
				'data'   => array(
					'message' => sprintf(
						__( 'Failed to delete items. Errors: %s', 'adarok-divi-janitor' ),
						implode( '; ', $errors )
					),
					'errors'  => $errors,
				),
				'status' => 400,
			);
		}

		$this->restore_divi_dynamic_asset_hooks( $disabled_hooks );

		if ( 'success' === $response['type'] ) {
			wp_send_json_success( $response['data'] );
		}

		wp_send_json_error( $response['data'], $response['status'] );
	}

	/**
	 * Handle AJAX request to bulk delete items with only copies
	 */
	public function bulk_delete_copies() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Increase limits for bulk operations.
		set_time_limit( 300 ); // 5 minutes.
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		// Temporarily disable Divi dynamic asset callbacks to prevent null reference errors.
		$disabled_hooks = $this->disable_divi_dynamic_asset_hooks();

		// Get all library items.
		$library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
		$library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );

		$deleted_count = 0;
		$failed_count  = 0;
		$deleted_ids   = array();
		$errors        = array();

		// Find and delete items with only copies.
		foreach ( $library_items as $item ) {
			if ( ! empty( $item['usage'] ) && Adarok_Divi_Janitor_Library_Scanner::has_only_copy_usage( $item['usage'] ) ) {
				$result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $item['id'], true );

				if ( is_wp_error( $result ) ) {
					++$failed_count;
					$errors[] = sprintf(
						__( '%1$s: %2$s', 'adarok-divi-janitor' ),
						$item['title'],
						$result->get_error_message()
					);
				} else {
					++$deleted_count;
					$deleted_ids[] = $item['id'];
				}
			}
		}

		// Build response message.
		$response = array(
			'type'   => 'error',
			'data'   => array(
				'message' => __( 'No items with only copies found.', 'adarok-divi-janitor' ),
			),
			'status' => 400,
		);

		if ( $deleted_count > 0 && 0 === $failed_count ) {
			$response = array(
				'type'   => 'success',
				'data'   => array(
					'message'       => sprintf(
						_n(
							'%d library item with copies deleted successfully.',
							'%d library items with copies deleted successfully.',
							$deleted_count,
							'adarok-divi-janitor'
						),
						$deleted_count
					),
					'deleted_count' => $deleted_count,
					'deleted_ids'   => $deleted_ids,
				),
				'status' => null,
			);
		} elseif ( $deleted_count > 0 && $failed_count > 0 ) {
			$response = array(
				'type'   => 'success',
				'data'   => array(
					'message'       => sprintf(
						__( '%1$d items deleted, %2$d failed. Errors: %3$s', 'adarok-divi-janitor' ),
						$deleted_count,
						$failed_count,
						implode( '; ', $errors )
					),
					'deleted_count' => $deleted_count,
					'failed_count'  => $failed_count,
					'deleted_ids'   => $deleted_ids,
					'errors'        => $errors,
				),
				'status' => null,
			);
		} elseif ( 0 === $deleted_count && $failed_count > 0 ) {
			$response = array(
				'type'   => 'error',
				'data'   => array(
					'message' => sprintf(
						__( 'Failed to delete items. Errors: %s', 'adarok-divi-janitor' ),
						implode( '; ', $errors )
					),
					'errors'  => $errors,
				),
				'status' => 400,
			);
		}

		$this->restore_divi_dynamic_asset_hooks( $disabled_hooks );

		if ( 'success' === $response['type'] ) {
			wp_send_json_success( $response['data'] );
		}

		wp_send_json_error( $response['data'], $response['status'] );
	}

	/**
	 * Handle AJAX request to bulk delete all unused library items
	 */
	public function bulk_delete_unused() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
				),
				403
			);
		}

		// Increase limits for bulk operations.
		set_time_limit( 300 ); // 5 minutes.
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		// Temporarily disable Divi dynamic asset callbacks to prevent null reference errors.
		$disabled_hooks = $this->disable_divi_dynamic_asset_hooks();

		// Get all library items.
		$library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
		$library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );

		$deleted_count = 0;
		$failed_count  = 0;
		$deleted_ids   = array();
		$errors        = array();

		// Find and delete unused items (no usage at all).
		foreach ( $library_items as $item ) {
			if ( empty( $item['usage'] ) ) {
				$result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $item['id'], false );

				if ( is_wp_error( $result ) ) {
					++$failed_count;
					$errors[] = sprintf(
						__( '%1$s: %2$s', 'adarok-divi-janitor' ),
						$item['title'],
						$result->get_error_message()
					);
				} else {
					++$deleted_count;
					$deleted_ids[] = $item['id'];
				}
			}
		}

		// Build response message.
		$response = array(
			'type'   => 'error',
			'data'   => array(
				'message' => __( 'No unused items found to delete.', 'adarok-divi-janitor' ),
			),
			'status' => 400,
		);

		if ( $deleted_count > 0 && 0 === $failed_count ) {
			$response = array(
				'type'   => 'success',
				'data'   => array(
					'message'       => sprintf(
						_n(
							'%d library item deleted successfully.',
							'%d library items deleted successfully.',
							$deleted_count,
							'adarok-divi-janitor'
						),
						$deleted_count
					),
					'deleted_count' => $deleted_count,
					'deleted_ids'   => $deleted_ids,
				),
				'status' => null,
			);
		} elseif ( $deleted_count > 0 && $failed_count > 0 ) {
			$response = array(
				'type'   => 'success',
				'data'   => array(
					'message'       => sprintf(
						__( '%1$d items deleted, %2$d failed. Errors: %3$s', 'adarok-divi-janitor' ),
						$deleted_count,
						$failed_count,
						implode( '; ', $errors )
					),
					'deleted_count' => $deleted_count,
					'failed_count'  => $failed_count,
					'deleted_ids'   => $deleted_ids,
					'errors'        => $errors,
				),
				'status' => null,
			);
		} elseif ( 0 === $deleted_count && $failed_count > 0 ) {
			$response = array(
				'type'   => 'error',
				'data'   => array(
					'message' => sprintf(
						__( 'Failed to delete items. Errors: %s', 'adarok-divi-janitor' ),
						implode( '; ', $errors )
					),
					'errors'  => $errors,
				),
				'status' => 400,
			);
		}

		$this->restore_divi_dynamic_asset_hooks( $disabled_hooks );

		if ( 'success' === $response['type'] ) {
			wp_send_json_success( $response['data'] );
		}

		wp_send_json_error( $response['data'], $response['status'] );
	}

	/**
	 * Temporarily disable Divi dynamic asset callbacks on key hooks.
	 *
	 * @return array Removed callbacks keyed by hook name.
	 */
	private function disable_divi_dynamic_asset_hooks() {
		$hooks   = array( 'before_delete_post', 'wp_trash_post', 'the_content' );
		$removed = array();

		foreach ( $hooks as $hook ) {
			$callbacks = $this->remove_prefixed_callbacks( $hook, array( 'et_', 'ET_' ) );
			if ( ! empty( $callbacks ) ) {
				$removed[ $hook ] = $callbacks;
			}
		}

		return $removed;
	}

	/**
	 * Restore previously disabled Divi dynamic asset callbacks.
	 *
	 * @param array $removed_hooks Removed callbacks keyed by hook name.
	 */
	private function restore_divi_dynamic_asset_hooks( array $removed_hooks ) {
		foreach ( $removed_hooks as $hook => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				add_filter( $hook, $callback['function'], $callback['priority'], $callback['accepted_args'] );
			}
		}
	}

	/**
	 * Remove callbacks from a hook when their callable names match specific prefixes.
	 *
	 * @param string $hook     Hook name.
	 * @param array  $prefixes List of prefixes to match.
	 * @return array Removed callbacks containing function, priority, and accepted args.
	 */
	private function remove_prefixed_callbacks( $hook, $prefixes ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook ] ) ) {
			return array();
		}

		$wp_hook = $wp_filter[ $hook ];

		if ( ! class_exists( 'WP_Hook' ) || ! $wp_hook instanceof WP_Hook ) {
			return array();
		}

		$removed_callbacks = array();

		foreach ( $wp_hook->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$callable_name = $this->normalize_callable_name( $callback['function'] );

				if ( '' === $callable_name ) {
					continue;
				}

				foreach ( $prefixes as $prefix ) {
					if ( 0 === strpos( $callable_name, $prefix ) ) {
						remove_filter( $hook, $callback['function'], $priority );
						$removed_callbacks[] = array(
							'function'      => $callback['function'],
							'priority'      => $priority,
							'accepted_args' => isset( $callback['accepted_args'] ) ? (int) $callback['accepted_args'] : 1,
						);
						break;
					}
				}
			}
		}

		return $removed_callbacks;
	}

	/**
	 * Derive a normalized string representation of a callable for comparison.
	 *
	 * @param callable $candidate Callable to normalize.
	 * @return string Normalized callable name, or empty string if unsupported.
	 */
	private function normalize_callable_name( $candidate ) {
		if ( is_string( $candidate ) ) {
			return $candidate;
		}

		if ( is_array( $candidate ) && isset( $candidate[0], $candidate[1] ) ) {
			if ( is_object( $candidate[0] ) ) {
				return get_class( $candidate[0] ) . '::' . $candidate[1];
			}

			return $candidate[0] . '::' . $candidate[1];
		}

		return '';
	}
}
