<?php
/**
 * Library Scanner Class
 *
 * Scans the Divi Library and finds where items are used
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
 * Adarok Divi Janitor Library Scanner Class
 *
 * Handles scanning of Divi Library items and tracking their usage across the site.
 *
 * @since 1.0.0
 */
class Adarok_Divi_Janitor_Library_Scanner {

	/**
	 * Get all Divi Library items
	 *
	 * @return array Array of library items with their details
	 */
	public static function get_library_items() {
		$library_items = array();

		// Get all et_pb_layout posts (Divi Library items).
		$args = array(
			'post_type'      => 'et_pb_layout',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$library_items[ $post_id ] = array(
					'id'            => $post_id,
					'title'         => get_the_title(),
					'type'          => self::get_layout_type( $post_id ),
					'modified_date' => get_the_modified_date( 'Y-m-d H:i:s' ),
					'is_global'     => self::is_global( $post_id ),
					'usage'         => array(),
				);
			}
			wp_reset_postdata();
		}

		return $library_items;
	}

	/**
	 * Get the layout type (layout, section, row, module)
	 *
	 * @param int $post_id The post ID.
	 * @return string The layout type
	 */
	private static function get_layout_type( $post_id ) {
		global $wpdb;

		$layout_type = '';

		// Check if taxonomy is registered before trying to use it.
		if ( taxonomy_exists( 'layout_type' ) ) {
			// First check taxonomy (Divi uses layout_type taxonomy).
			$terms = wp_get_post_terms( $post_id, 'layout_type', array( 'fields' => 'slugs' ) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$layout_type = $terms[0];
			}
		} else {
			// Fallback: Query the database directly if taxonomy not yet registered.
			$term = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT t.slug FROM {$wpdb->terms} t
					INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
					INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
					WHERE tr.object_id = %d AND tt.taxonomy = 'layout_type'
					LIMIT 1",
					$post_id
				)
			);

			if ( ! empty( $term ) ) {
				$layout_type = $term;
			}
		}

		// Fallback to meta key (older versions or custom implementations).
		if ( empty( $layout_type ) ) {
			$layout_type = get_post_meta( $post_id, '_et_pb_layout_type', true );
		}

		if ( empty( $layout_type ) ) {
			return __( 'Unknown', 'adarok-divi-janitor' );
		}

		$types = array(
			'layout'  => __( 'Layout', 'adarok-divi-janitor' ),
			'section' => __( 'Section', 'adarok-divi-janitor' ),
			'row'     => __( 'Row', 'adarok-divi-janitor' ),
			'module'  => __( 'Module', 'adarok-divi-janitor' ),
		);

		return isset( $types[ $layout_type ] ) ? $types[ $layout_type ] : ucfirst( $layout_type );
	}

	/**
	 * Check if a library item is marked as global
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if global, false otherwise
	 */
	private static function is_global( $post_id ) {
		global $wpdb;

		// Try both possible taxonomy names (Divi uses different names in different contexts).
		$taxonomy_names = array( 'et_pb_layout_scope', 'scope' );

		foreach ( $taxonomy_names as $taxonomy_name ) {
			// Check if taxonomy is registered before trying to use it.
			if ( taxonomy_exists( $taxonomy_name ) ) {
				// Check if the post has a 'global' term in the scope taxonomy.
				$terms = wp_get_post_terms( $post_id, $taxonomy_name, array( 'fields' => 'names' ) );

				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					return in_array( 'global', $terms, true );
				}
			}
		}

		// Fallback: Query the database directly if taxonomy not yet registered.
		// Check both possible taxonomy names.
		$has_global = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->terms} t
				INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tr.object_id = %d AND tt.taxonomy IN ('et_pb_layout_scope', 'scope') AND t.name = 'global'",
				$post_id
			)
		);

		return $has_global > 0;
	}

	/**
	 * Find where library items are used
	 *
	 * @param array $library_items Array of library items.
	 * @return array Updated array with usage information
	 */
	public static function find_usage( $library_items ) {
		if ( empty( $library_items ) ) {
			return array();
		}

		// Get all post types that support Divi.
		$post_types = self::get_supported_post_types();

		foreach ( $library_items as $lib_id => $lib_item ) {
			$library_items[ $lib_id ]['usage'] = self::find_item_usage( $lib_id, $post_types );
		}

		return $library_items;
	}

	/**
	 * Find where a specific library item is used
	 *
	 * @param int   $lib_id     The library item ID.
	 * @param array $post_types Array of post types to search.
	 * @return array Array of posts where the item is used
	 */
	private static function find_item_usage( $lib_id, $post_types ) {
		global $wpdb;

		$usage = array();

		// Get the library item content for comparison.
		$library_post = get_post( $lib_id );
		if ( ! $library_post ) {
			return array();
		}

		// Search for the library ID in post content.
		// Three types of usage:
		// 1. Global reference: global_module="123", template_id="123" (Divi 4).
		// 2. Global reference: "globalModule":"123" (Divi 5 block format).
		// 3. Instantiated content: copied content that matches the library item.

		// Global reference patterns.
		$global_patterns = array(
			// Divi 4 patterns.
			'global_module="' . $lib_id . '"',
			'global_module=\"' . $lib_id . '\"',
			'template_id="' . $lib_id . '"',
			'template_id=\"' . $lib_id . '\"',
			'saved_tabs="' . $lib_id . '"',
			'saved_tabs=\"' . $lib_id . '\"',
			// Divi 5 patterns (block-based editor).
			'"globalModule":"' . $lib_id . '"',
			'\\"globalModule\\":\\"' . $lib_id . '\\"',
		);

		foreach ( $post_types as $post_type ) {
			// First, search for global references.
			foreach ( $global_patterns as $pattern ) {
				$query = $wpdb->prepare(
					"SELECT ID, post_title, post_type, post_status, post_content
                    FROM {$wpdb->posts}
                    WHERE post_type = %s
                    AND post_status IN ('publish', 'draft', 'pending', 'private')
                    AND post_content LIKE %s",
					$post_type,
					'%' . $wpdb->esc_like( $pattern ) . '%'
				);

				$results = $wpdb->get_results( $query );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

				foreach ( $results as $result ) {
					if ( ! isset( $usage[ $result->ID ] ) ) {
						$usage[ $result->ID ] = array(
							'id'          => $result->ID,
							'title'       => $result->post_title,
							'post_type'   => $result->post_type,
							'post_status' => $result->post_status,
							'edit_url'    => get_edit_post_link( $result->ID ),
							'view_url'    => get_permalink( $result->ID ),
							'usage_type'  => 'global',
						);
					}
				}
			}

			// Second, search for instantiated content (copied library items).
			// We'll look for content that contains significant portions of the library item.
			$usage = self::find_instantiated_usage( $lib_id, $library_post, $post_type, $usage );
		}

		return array_values( $usage );
	}

	/**
	 * Find instantiated (copied) usage of a library item
	 *
	 * @param int     $lib_id       The library item ID.
	 * @param WP_Post $library_post The library post object.
	 * @param string  $post_type    The post type to search.
	 * @param array   $usage        Existing usage array to add to.
	 * @return array Updated usage array
	 */
	private static function find_instantiated_usage( $lib_id, $library_post, $post_type, $usage ) {
		global $wpdb;

		// Extract a unique signature from the library content.
		// Look for module_id patterns or unique shortcode combinations.
		$library_content = $library_post->post_content;

		// Extract meaningful patterns from the library content.
		$signatures = self::extract_content_signatures( $library_content, $lib_id );

		if ( empty( $signatures ) ) {
			return $usage;
		}

		// Search for these signatures in posts.
		foreach ( $signatures as $signature ) {
			$query = $wpdb->prepare(
				"SELECT ID, post_title, post_type, post_status, post_content
                FROM {$wpdb->posts}
                WHERE post_type = %s
                AND post_status IN ('publish', 'draft', 'pending', 'private')
                AND post_content LIKE %s",
				$post_type,
				'%' . $wpdb->esc_like( $signature ) . '%'
			);

			$results = $wpdb->get_results( $query );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $results as $result ) {
				// Skip if already found as global reference.
				if ( isset( $usage[ $result->ID ] ) && 'global' === $usage[ $result->ID ]['usage_type'] ) {
					continue;
				}

				// Verify it's actually an instantiated copy by checking similarity.
				if ( self::is_instantiated_content( $library_content, $result->post_content, $lib_id ) ) {
					if ( ! isset( $usage[ $result->ID ] ) ) {
						$usage[ $result->ID ] = array(
							'id'          => $result->ID,
							'title'       => $result->post_title,
							'post_type'   => $result->post_type,
							'post_status' => $result->post_status,
							'edit_url'    => get_edit_post_link( $result->ID ),
							'view_url'    => get_permalink( $result->ID ),
							'usage_type'  => 'copy',
						);
					}
				}
			}
		}

		return $usage;
	}

	/**
	 * Extract unique signatures from library content
	 *
	 * @param string $content Library content.
	 * @param int    $lib_id  Library item ID.
	 * @return array Array of signature strings
	 */
	private static function extract_content_signatures( $content, $lib_id ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$signatures = array();

		// Extract module_id values (these are unique identifiers for modules).
		preg_match_all( '/module_id="([^"]+)"/', $content, $module_ids );
		if ( ! empty( $module_ids[1] ) ) {
			// Use first few module IDs as signatures.
			$signatures = array_merge( $signatures, array_slice( $module_ids[1], 0, 3 ) );
		}

		// Extract unique CSS classes.
		preg_match_all( '/module_class="([^"]+)"/', $content, $module_classes );
		if ( ! empty( $module_classes[1] ) ) {
			foreach ( $module_classes[1] as $class ) {
				if ( strlen( $class ) > 10 ) { // Only use meaningful classes.
					$signatures[] = $class;
				}
			}
		}

		// Extract admin_label values (custom labels users set).
		preg_match_all( '/admin_label="([^"]+)"/', $content, $admin_labels );
		if ( ! empty( $admin_labels[1] ) ) {
			$signatures = array_merge( $signatures, array_slice( $admin_labels[1], 0, 2 ) );
		}

		// Extract unique section/row IDs.
		preg_match_all( '/_id="([^"]+)"/', $content, $ids );
		if ( ! empty( $ids[1] ) ) {
			$signatures = array_merge( $signatures, array_slice( $ids[1], 0, 3 ) );
		}

		// Remove duplicates and return.
		return array_unique( array_filter( $signatures ) );
	}

	/**
	 * Check if content is an instantiated copy of library item
	 *
	 * @param string $library_content The library item content.
	 * @param string $post_content    The post content to check.
	 * @param int    $lib_id          The library ID.
	 * @return bool True if it's an instantiated copy
	 */
	private static function is_instantiated_content( $library_content, $post_content, $lib_id ) {
		// If it has a global reference to this ID, it's not instantiated.
		$global_patterns = array(
			'global_module="' . $lib_id . '"',
			'template_id="' . $lib_id . '"',
		);

		foreach ( $global_patterns as $pattern ) {
			if ( strpos( $post_content, $pattern ) !== false ) {
				return false;
			}
		}

		// Extract signatures from both contents.
		$library_sigs = self::extract_content_signatures( $library_content, $lib_id );

		if ( empty( $library_sigs ) ) {
			return false;
		}

		// Count how many signatures match.
		$matches = 0;
		foreach ( $library_sigs as $sig ) {
			if ( strpos( $post_content, $sig ) !== false ) {
				++$matches;
			}
		}

		// If at least 2 signatures match (or more than 50%), it's likely instantiated.
		$sig_count = count( $library_sigs );
		return ( $matches >= 2 || ( $matches / $sig_count ) > 0.5 );
	}

	/**
	 * Get post types that support Divi Builder
	 *
	 * @return array Array of post types
	 */
	private static function get_supported_post_types() {
		$post_types = array( 'page', 'post' );

		// Get additional post types that support Divi.
		$et_builder_post_types = et_builder_get_builder_post_types();

		if ( ! empty( $et_builder_post_types ) && is_array( $et_builder_post_types ) ) {
			$post_types = array_merge( $post_types, $et_builder_post_types );
		}

		// Allow filtering.
		$post_types = apply_filters( 'adarok_divi_janitor_post_types', $post_types );

		// Remove duplicates and et_pb_layout.
		$post_types = array_unique( $post_types );
		$post_types = array_diff( $post_types, array( 'et_pb_layout' ) );

		return $post_types;
	}

	/**
	 * Check if item has only copy usage (no global references)
	 *
	 * @param array $usage Array of usage items.
	 * @return bool True if only copies, false if has global refs or no usage
	 */
	public static function has_only_copy_usage( $usage ) {
		if ( empty( $usage ) ) {
			return false; // No usage at all - not "only copies".
		}

		$has_copy = false;
		foreach ( $usage as $use ) {
			if ( isset( $use['usage_type'] ) && 'global' === $use['usage_type'] ) {
				return false; // Has at least one global reference.
			}
			if ( isset( $use['usage_type'] ) && 'copy' === $use['usage_type'] ) {
				$has_copy = true;
			}
		}

		return $has_copy; // Only copies (must have at least one copy).
	}

	/**
	 * Check if item has any global references
	 *
	 * @param array $usage Array of usage items.
	 * @return bool True if has at least one global reference, false otherwise
	 */
	public static function has_global_usage( $usage ) {
		if ( empty( $usage ) ) {
			return false;
		}

		foreach ( $usage as $use ) {
			if ( isset( $use['usage_type'] ) && 'global' === $use['usage_type'] ) {
				return true; // Has at least one global reference.
			}
		}

		return false;
	}

	/**
	 * Delete a library item
	 *
	 * @param int  $post_id      The post ID to delete.
	 * @param bool $force_delete Allow deletion even if copies exist (default: false).
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function delete_library_item( $post_id, $force_delete = false ) {
		// Verify it's a library item.
		$post = get_post( $post_id );

		if ( ! $post || 'et_pb_layout' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post_type',
				__( 'Invalid library item.', 'adarok-divi-janitor' )
			);
		}

		// Check if it's being used.
		$post_types = self::get_supported_post_types();
		$usage      = self::find_item_usage( $post_id, $post_types );

		if ( ! empty( $usage ) ) {
			// Check if it has global references.
			$has_only_copies = self::has_only_copy_usage( $usage );

			if ( ! $has_only_copies ) {
				// Has global references - cannot delete.
				return new WP_Error(
					'item_has_global_references',
					/* translators: Error message when trying to delete a library item that has active global references. */
					__( 'This library item has active global references and cannot be deleted. Only items with instantiated copies or no usage can be deleted.', 'adarok-divi-janitor' )
				);
			}           // Has only copies - can delete if force_delete is true.
			if ( ! $force_delete ) {
				return new WP_Error(
					'item_has_copies',
					sprintf(
											/* translators: %d is the number of instantiated copies of the library item. */
						__( 'This library item has %d instantiated copy/copies. The library item can be deleted safely as these copies are independent, but please confirm this action.', 'adarok-divi-janitor' ),
						count( $usage )
					)
				);
			}
		}

		// Delete the post permanently.
		$result = wp_delete_post( $post_id, true );

		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete the library item.', 'adarok-divi-janitor' )
			);
		}

		return true;
	}

	/**
	 * Get statistics about library usage
	 *
	 * @param array $library_items Array of library items.
	 * @return array Statistics
	 */
	public static function get_statistics( $library_items ) {
		$stats = array(
			'total'          => count( $library_items ),
			'used'           => 0,
			'unused'         => 0,
			'copies_only'    => 0,
			'safe_to_delete' => 0,
			'global_refs'    => 0,
			'copies'         => 0,
			'by_type'        => array(),
		);

		foreach ( $library_items as $item ) {
			// Count usage.
			if ( ! empty( $item['usage'] ) ) {
				++$stats['used'];

				// Check if has only copies (safe to delete).
				$has_only_copies = self::has_only_copy_usage( $item['usage'] );
				if ( $has_only_copies ) {
					++$stats['copies_only'];
				}

				// Count usage types.
				foreach ( $item['usage'] as $usage ) {
					if ( isset( $usage['usage_type'] ) ) {
						if ( 'global' === $usage['usage_type'] ) {
							++$stats['global_refs'];
						} elseif ( 'copy' === $usage['usage_type'] ) {
							++$stats['copies'];
						}
					}
				}
			} else {
				++$stats['unused'];
			}

			// Safe to delete = unused OR only copies.
			if ( empty( $item['usage'] ) || self::has_only_copy_usage( $item['usage'] ) ) {
				++$stats['safe_to_delete'];
			}

			// Count by type.
			$type = $item['type'];
			if ( ! isset( $stats['by_type'][ $type ] ) ) {
				$stats['by_type'][ $type ] = 0;
			}
			++$stats['by_type'][ $type ];
		}

		return $stats;
	}
}
