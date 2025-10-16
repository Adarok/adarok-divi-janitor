<?php
/**
 * Library Scanner Class
 *
 * Scans the Divi Library and finds where items are used
 *
 * @package Adarok_Divi_Janitor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Adarok_Divi_Janitor_Library_Scanner {

    /**
     * Get all Divi Library items
     *
     * @return array Array of library items with their details
     */
    public static function get_library_items() {
        $library_items = array();

        // Get all et_pb_layout posts (Divi Library items)
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
     * @param int $post_id The post ID
     * @return string The layout type
     */
    private static function get_layout_type( $post_id ) {
        $layout_type = get_post_meta( $post_id, '_et_pb_layout_type', true );

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
     * Find where library items are used
     *
     * @param array $library_items Array of library items
     * @return array Updated array with usage information
     */
    public static function find_usage( $library_items ) {
        if ( empty( $library_items ) ) {
            return array();
        }

        // Get all post types that support Divi
        $post_types = self::get_supported_post_types();

        foreach ( $library_items as $lib_id => $lib_item ) {
            $library_items[ $lib_id ]['usage'] = self::find_item_usage( $lib_id, $post_types );
        }

        return $library_items;
    }

    /**
     * Find where a specific library item is used
     *
     * @param int   $lib_id     The library item ID
     * @param array $post_types Array of post types to search
     * @return array Array of posts where the item is used
     */
    private static function find_item_usage( $lib_id, $post_types ) {
        global $wpdb;

        $usage = array();

        // Search for the library ID in post content
        // Divi stores library items as shortcodes like [et_pb_section global_module="123"]
        // or in the content as module_id="123" or global_module="123"

        $search_patterns = array(
            'global_module="' . $lib_id . '"',
            'global_module=\"' . $lib_id . '\"',
            'template_id="' . $lib_id . '"',
            'template_id=\"' . $lib_id . '\"',
            'saved_tabs="' . $lib_id . '"',
            'saved_tabs=\"' . $lib_id . '\"',
        );

        foreach ( $post_types as $post_type ) {
            foreach ( $search_patterns as $pattern ) {
                // Use direct database query for performance
                $query = $wpdb->prepare(
                    "SELECT ID, post_title, post_type, post_status
                    FROM {$wpdb->posts}
                    WHERE post_type = %s
                    AND post_status IN ('publish', 'draft', 'pending', 'private')
                    AND post_content LIKE %s",
                    $post_type,
                    '%' . $wpdb->esc_like( $pattern ) . '%'
                );

                $results = $wpdb->get_results( $query );

                foreach ( $results as $result ) {
                    // Avoid duplicates
                    if ( ! isset( $usage[ $result->ID ] ) ) {
                        $usage[ $result->ID ] = array(
                            'id'         => $result->ID,
                            'title'      => $result->post_title,
                            'post_type'  => $result->post_type,
                            'post_status'=> $result->post_status,
                            'edit_url'   => get_edit_post_link( $result->ID ),
                            'view_url'   => get_permalink( $result->ID ),
                        );
                    }
                }
            }
        }

        return array_values( $usage );
    }

    /**
     * Get post types that support Divi Builder
     *
     * @return array Array of post types
     */
    private static function get_supported_post_types() {
        $post_types = array( 'page', 'post' );

        // Get additional post types that support Divi
        $et_builder_post_types = et_builder_get_builder_post_types();

        if ( ! empty( $et_builder_post_types ) && is_array( $et_builder_post_types ) ) {
            $post_types = array_merge( $post_types, $et_builder_post_types );
        }

        // Allow filtering
        $post_types = apply_filters( 'adarok_divi_janitor_post_types', $post_types );

        // Remove duplicates and et_pb_layout
        $post_types = array_unique( $post_types );
        $post_types = array_diff( $post_types, array( 'et_pb_layout' ) );

        return $post_types;
    }

    /**
     * Delete a library item
     *
     * @param int $post_id The post ID to delete
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function delete_library_item( $post_id ) {
        // Verify it's a library item
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'et_pb_layout' ) {
            return new WP_Error(
                'invalid_post_type',
                __( 'Invalid library item.', 'adarok-divi-janitor' )
            );
        }

        // Check if it's being used
        $post_types = self::get_supported_post_types();
        $usage = self::find_item_usage( $post_id, $post_types );

        if ( ! empty( $usage ) ) {
            return new WP_Error(
                'item_in_use',
                __( 'This library item is currently in use and cannot be deleted.', 'adarok-divi-janitor' )
            );
        }

        // Delete the post permanently
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
     * @param array $library_items Array of library items
     * @return array Statistics
     */
    public static function get_statistics( $library_items ) {
        $stats = array(
            'total'         => count( $library_items ),
            'used'          => 0,
            'unused'        => 0,
            'by_type'       => array(),
        );

        foreach ( $library_items as $item ) {
            // Count usage
            if ( ! empty( $item['usage'] ) ) {
                $stats['used']++;
            } else {
                $stats['unused']++;
            }

            // Count by type
            $type = $item['type'];
            if ( ! isset( $stats['by_type'][ $type ] ) ) {
                $stats['by_type'][ $type ] = 0;
            }
            $stats['by_type'][ $type ]++;
        }

        return $stats;
    }
}
