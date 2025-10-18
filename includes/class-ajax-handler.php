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
        if ( is_null( self::$instance ) ) {
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
        // Verify nonce
        if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Get and validate post ID
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $force_delete = isset( $_POST['force_delete'] ) && $_POST['force_delete'] === 'true';

        if ( ! $post_id ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Invalid library item ID.', 'adarok-divi-janitor' ),
                ),
                400
            );
        }

        // Attempt to delete the library item
        $result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $post_id, $force_delete );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                ),
                400
            );
        }

        // Success
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
        // Verify nonce
        if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Increase limits for bulk operations
        set_time_limit( 300 ); // 5 minutes
        ini_set( 'memory_limit', '512M' );

        // Temporarily disable Divi dynamic assets to prevent null reference errors
        remove_all_actions( 'before_delete_post' );
        remove_all_actions( 'wp_trash_post' );
        remove_all_filters( 'the_content' );

        // Get all library items
        $library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
        $library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );

        $deleted_count = 0;
        $failed_count = 0;
        $deleted_ids = array();
        $errors = array();

        // Find and delete safe-to-delete items (unused OR only copies)
        foreach ( $library_items as $item ) {
            $is_safe = empty( $item['usage'] ) || Adarok_Divi_Janitor_Library_Scanner::has_only_copy_usage( $item['usage'] );

            if ( $is_safe ) {
                $force_delete = ! empty( $item['usage'] ); // Force if has copies
                $result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $item['id'], $force_delete );

                if ( is_wp_error( $result ) ) {
                    $failed_count++;
                    $errors[] = sprintf(
                        __( '%s: %s', 'adarok-divi-janitor' ),
                        $item['title'],
                        $result->get_error_message()
                    );
                } else {
                    $deleted_count++;
                    $deleted_ids[] = $item['id'];
                }
            }
        }

        // Build response message
        if ( $deleted_count > 0 && $failed_count === 0 ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
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
                )
            );
        } elseif ( $deleted_count > 0 && $failed_count > 0 ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
                        __( '%1$d items deleted, %2$d failed. Errors: %3$s', 'adarok-divi-janitor' ),
                        $deleted_count,
                        $failed_count,
                        implode( '; ', $errors )
                    ),
                    'deleted_count' => $deleted_count,
                    'failed_count'  => $failed_count,
                    'deleted_ids'   => $deleted_ids,
                    'errors'        => $errors,
                )
            );
        } elseif ( $deleted_count === 0 && $failed_count > 0 ) {
            wp_send_json_error(
                array(
                    'message' => sprintf(
                        __( 'Failed to delete items. Errors: %s', 'adarok-divi-janitor' ),
                        implode( '; ', $errors )
                    ),
                    'errors' => $errors,
                ),
                400
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => __( 'No safe-to-delete items found.', 'adarok-divi-janitor' ),
                ),
                400
            );
        }
    }

    /**
     * Handle AJAX request to bulk delete items with only copies
     */
    public function bulk_delete_copies() {
        // Verify nonce
        if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Increase limits for bulk operations
        set_time_limit( 300 ); // 5 minutes
        ini_set( 'memory_limit', '512M' );

        // Temporarily disable Divi dynamic assets to prevent null reference errors
        remove_all_actions( 'before_delete_post' );
        remove_all_actions( 'wp_trash_post' );
        remove_all_filters( 'the_content' );

        // Get all library items
        $library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
        $library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );

        $deleted_count = 0;
        $failed_count = 0;
        $deleted_ids = array();
        $errors = array();

        // Find and delete items with only copies
        foreach ( $library_items as $item ) {
            if ( ! empty( $item['usage'] ) && Adarok_Divi_Janitor_Library_Scanner::has_only_copy_usage( $item['usage'] ) ) {
                $result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $item['id'], true );

                if ( is_wp_error( $result ) ) {
                    $failed_count++;
                    $errors[] = sprintf(
                        __( '%s: %s', 'adarok-divi-janitor' ),
                        $item['title'],
                        $result->get_error_message()
                    );
                } else {
                    $deleted_count++;
                    $deleted_ids[] = $item['id'];
                }
            }
        }

        // Build response message
        if ( $deleted_count > 0 && $failed_count === 0 ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
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
                )
            );
        } elseif ( $deleted_count > 0 && $failed_count > 0 ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
                        __( '%1$d items deleted, %2$d failed. Errors: %3$s', 'adarok-divi-janitor' ),
                        $deleted_count,
                        $failed_count,
                        implode( '; ', $errors )
                    ),
                    'deleted_count' => $deleted_count,
                    'failed_count'  => $failed_count,
                    'deleted_ids'   => $deleted_ids,
                    'errors'        => $errors,
                )
            );
        } elseif ( $deleted_count === 0 && $failed_count > 0 ) {
            wp_send_json_error(
                array(
                    'message' => sprintf(
                        __( 'Failed to delete items. Errors: %s', 'adarok-divi-janitor' ),
                        implode( '; ', $errors )
                    ),
                    'errors' => $errors,
                ),
                400
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => __( 'No items with only copies found.', 'adarok-divi-janitor' ),
                ),
                400
            );
        }
    }

    /**
     * Handle AJAX request to bulk delete all unused library items
     */
    public function bulk_delete_unused() {
        // Verify nonce
        if ( ! check_ajax_referer( 'adarok_divi_janitor_nonce', 'nonce', false ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed. Please refresh the page and try again.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to perform this action.', 'adarok-divi-janitor' ),
                ),
                403
            );
        }

        // Increase limits for bulk operations
        set_time_limit( 300 ); // 5 minutes
        ini_set( 'memory_limit', '512M' );

        // Temporarily disable Divi dynamic assets to prevent null reference errors
        remove_all_actions( 'before_delete_post' );
        remove_all_actions( 'wp_trash_post' );
        remove_all_filters( 'the_content' );

        // Get all library items
        $library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
        $library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );

        $deleted_count = 0;
        $failed_count = 0;
        $deleted_ids = array();
        $errors = array();

        // Find and delete unused items (no usage at all)
        foreach ( $library_items as $item ) {
            if ( empty( $item['usage'] ) ) {
                $result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $item['id'], false );

                if ( is_wp_error( $result ) ) {
                    $failed_count++;
                    $errors[] = sprintf(
                        __( '%s: %s', 'adarok-divi-janitor' ),
                        $item['title'],
                        $result->get_error_message()
                    );
                } else {
                    $deleted_count++;
                    $deleted_ids[] = $item['id'];
                }
            }
        }

        // Build response message
        if ( $deleted_count > 0 && $failed_count === 0 ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
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
                )
            );
        } elseif ( $deleted_count > 0 && $failed_count > 0 ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
                        __( '%1$d items deleted, %2$d failed. Errors: %3$s', 'adarok-divi-janitor' ),
                        $deleted_count,
                        $failed_count,
                        implode( '; ', $errors )
                    ),
                    'deleted_count' => $deleted_count,
                    'failed_count'  => $failed_count,
                    'deleted_ids'   => $deleted_ids,
                    'errors'        => $errors,
                )
            );
        } elseif ( $deleted_count === 0 && $failed_count > 0 ) {
            wp_send_json_error(
                array(
                    'message' => sprintf(
                        __( 'Failed to delete items. Errors: %s', 'adarok-divi-janitor' ),
                        implode( '; ', $errors )
                    ),
                    'errors' => $errors,
                ),
                400
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => __( 'No unused items found to delete.', 'adarok-divi-janitor' ),
                ),
                400
            );
        }
    }
}
