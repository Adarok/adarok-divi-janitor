<?php
/**
 * AJAX Handler Class
 *
 * Handles AJAX requests for the Divi Janitor
 *
 * @package Adarok_Divi_Janitor
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

        if ( ! $post_id ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Invalid library item ID.', 'adarok-divi-janitor' ),
                ),
                400
            );
        }

        // Attempt to delete the library item
        $result = Adarok_Divi_Janitor_Library_Scanner::delete_library_item( $post_id );

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
}
