<?php
/**
 * Admin Page Class
 *
 * Handles the admin interface for the Divi Janitor
 *
 * @package Adarok_Divi_Janitor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Adarok_Divi_Janitor_Admin_Page {

    /**
     * The single instance of the class
     *
     * @var Adarok_Divi_Janitor_Admin_Page
     */
    protected static $instance = null;

    /**
     * Page slug
     *
     * @var string
     */
    private $page_slug = 'divi-janitor';

    /**
     * Main Instance
     *
     * @return Adarok_Divi_Janitor_Admin_Page - Main instance
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'handle_direct_access' ) );
    }

    /**
     * Handle direct URL access attempts
     */
    public function handle_direct_access() {
        // Check if someone is trying to access /wp-admin/divi-janitor
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-admin/divi-janitor' ) !== false ) {
            wp_redirect( admin_url( 'admin.php?page=' . $this->page_slug ) );
            exit;
        }
    }

    /**
     * Add admin menu page under Divi
     */
    public function add_admin_menu() {
        // Check if Divi theme options page exists
        global $menu;
        $divi_exists = false;

        if ( ! empty( $menu ) ) {
            foreach ( $menu as $item ) {
                if ( isset( $item[2] ) && $item[2] === 'et_divi_options' ) {
                    $divi_exists = true;
                    break;
                }
            }
        }

        // Also check if et_divi_options page will be registered
        if ( ! $divi_exists && ( function_exists( 'et_divi_add_customizer_css' ) || wp_get_theme()->get( 'Name' ) === 'Divi' ) ) {
            $divi_exists = true;
        }

        if ( $divi_exists ) {
            // Add submenu under Divi
            add_submenu_page(
                'et_divi_options',
                __( 'Divi Janitor', 'adarok-divi-janitor' ),
                __( 'Divi Janitor', 'adarok-divi-janitor' ),
                'manage_options',
                $this->page_slug,
                array( $this, 'render_page' )
            );
        } else {
            // Add as top-level menu if Divi is not available
            add_menu_page(
                __( 'Divi Janitor', 'adarok-divi-janitor' ),
                __( 'Divi Janitor', 'adarok-divi-janitor' ),
                'manage_options',
                $this->page_slug,
                array( $this, 'render_page' ),
                'dashicons-admin-tools',
                81
            );
        }
    }

    /**
     * Enqueue CSS and JavaScript
     *
     * @param string $hook The current admin page hook
     */
    public function enqueue_assets( $hook ) {
        // Only load on our admin page
        if ( 'divi_page_' . $this->page_slug !== $hook ) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'adarok-divi-janitor-admin',
            ADAROK_DIVI_JANITOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ADAROK_DIVI_JANITOR_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'adarok-divi-janitor-admin',
            ADAROK_DIVI_JANITOR_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            ADAROK_DIVI_JANITOR_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script(
            'adarok-divi-janitor-admin',
            'adarokDiviJanitor',
            array(
                'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
                'nonce'              => wp_create_nonce( 'adarok_divi_janitor_nonce' ),
                'confirmDelete'      => __( 'Are you sure you want to delete this library item? This action cannot be undone.', 'adarok-divi-janitor' ),
                'deleteSuccess'      => __( 'Library item deleted successfully.', 'adarok-divi-janitor' ),
                'deleteError'        => __( 'Failed to delete library item.', 'adarok-divi-janitor' ),
                'scanningText'       => __( 'Scanning...', 'adarok-divi-janitor' ),
                'scanCompleteText'   => __( 'Scan Complete', 'adarok-divi-janitor' ),
            )
        );
    }

    /**
     * Render the admin page
     */
    public function render_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'adarok-divi-janitor' ) );
        }

        // Get library items and their usage
        $library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
        $library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );
        $statistics = Adarok_Divi_Janitor_Library_Scanner::get_statistics( $library_items );

        // Separate used and unused items
        $used_items = array();
        $unused_items = array();

        foreach ( $library_items as $item ) {
            if ( ! empty( $item['usage'] ) ) {
                $used_items[] = $item;
            } else {
                $unused_items[] = $item;
            }
        }

        ?>
        <div class="wrap adarok-divi-janitor">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="adarok-divi-janitor-intro">
                <p><?php esc_html_e( 'This tool helps you manage your Divi Library by showing where each library item is used throughout your site. You can safely delete unused library items to keep your library organized.', 'adarok-divi-janitor' ); ?></p>
            </div>

            <!-- Statistics -->
            <div class="adarok-divi-janitor-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html( $statistics['total'] ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Total Library Items', 'adarok-divi-janitor' ); ?></div>
                </div>
                <div class="stat-box stat-used">
                    <div class="stat-number"><?php echo esc_html( $statistics['used'] ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'In Use', 'adarok-divi-janitor' ); ?></div>
                </div>
                <div class="stat-box stat-unused">
                    <div class="stat-number"><?php echo esc_html( $statistics['unused'] ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Not Used', 'adarok-divi-janitor' ); ?></div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="adarok-divi-janitor-tabs">
                <button class="tab-button active" data-tab="all">
                    <?php esc_html_e( 'All Items', 'adarok-divi-janitor' ); ?>
                    <span class="count">(<?php echo esc_html( $statistics['total'] ); ?>)</span>
                </button>
                <button class="tab-button" data-tab="used">
                    <?php esc_html_e( 'In Use', 'adarok-divi-janitor' ); ?>
                    <span class="count">(<?php echo esc_html( $statistics['used'] ); ?>)</span>
                </button>
                <button class="tab-button" data-tab="unused">
                    <?php esc_html_e( 'Not Used', 'adarok-divi-janitor' ); ?>
                    <span class="count">(<?php echo esc_html( $statistics['unused'] ); ?>)</span>
                </button>
            </div>

            <!-- All Items Tab -->
            <div class="tab-content active" id="tab-all">
                <?php $this->render_library_table( $library_items ); ?>
            </div>

            <!-- Used Items Tab -->
            <div class="tab-content" id="tab-used">
                <?php $this->render_library_table( $used_items ); ?>
            </div>

            <!-- Unused Items Tab -->
            <div class="tab-content" id="tab-unused">
                <?php $this->render_library_table( $unused_items, true ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render library items table
     *
     * @param array $items        Array of library items
     * @param bool  $show_delete  Whether to show delete buttons
     */
    private function render_library_table( $items, $show_delete = false ) {
        if ( empty( $items ) ) {
            echo '<div class="adarok-no-items">';
            echo '<p>' . esc_html__( 'No library items found.', 'adarok-divi-janitor' ) . '</p>';
            echo '</div>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped adarok-divi-janitor-table">
            <thead>
                <tr>
                    <th class="column-title"><?php esc_html_e( 'Library Item', 'adarok-divi-janitor' ); ?></th>
                    <th class="column-type"><?php esc_html_e( 'Type', 'adarok-divi-janitor' ); ?></th>
                    <th class="column-modified"><?php esc_html_e( 'Last Modified', 'adarok-divi-janitor' ); ?></th>
                    <th class="column-usage"><?php esc_html_e( 'Used In', 'adarok-divi-janitor' ); ?></th>
                    <?php if ( $show_delete ) : ?>
                    <th class="column-actions"><?php esc_html_e( 'Actions', 'adarok-divi-janitor' ); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ) : ?>
                <tr data-item-id="<?php echo esc_attr( $item['id'] ); ?>">
                    <td class="column-title">
                        <strong>
                            <a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>" target="_blank">
                                <?php echo esc_html( $item['title'] ); ?>
                            </a>
                        </strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>" target="_blank">
                                    <?php esc_html_e( 'Edit', 'adarok-divi-janitor' ); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td class="column-type">
                        <span class="type-badge type-<?php echo esc_attr( sanitize_title( $item['type'] ) ); ?>">
                            <?php echo esc_html( $item['type'] ); ?>
                        </span>
                    </td>
                    <td class="column-modified">
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item['modified_date'] ) ) ); ?>
                    </td>
                    <td class="column-usage">
                        <?php if ( ! empty( $item['usage'] ) ) : ?>
                            <button class="button button-small toggle-usage" type="button">
                                <?php
                                printf(
                                    esc_html( _n( '%d location', '%d locations', count( $item['usage'] ), 'adarok-divi-janitor' ) ),
                                    count( $item['usage'] )
                                );
                                ?>
                            </button>
                            <div class="usage-details" style="display: none;">
                                <ul>
                                    <?php foreach ( $item['usage'] as $usage ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( $usage['edit_url'] ); ?>" target="_blank">
                                            <?php echo esc_html( $usage['title'] ); ?>
                                        </a>
                                        <span class="usage-meta">
                                            (<?php echo esc_html( get_post_type_object( $usage['post_type'] )->labels->singular_name ); ?>)
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else : ?>
                            <span class="not-used"><?php esc_html_e( 'Not used', 'adarok-divi-janitor' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <?php if ( $show_delete ) : ?>
                    <td class="column-actions">
                        <button
                            class="button button-small delete-library-item"
                            type="button"
                            data-item-id="<?php echo esc_attr( $item['id'] ); ?>"
                            data-item-title="<?php echo esc_attr( $item['title'] ); ?>">
                            <?php esc_html_e( 'Delete', 'adarok-divi-janitor' ); ?>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
