<?php
/**
 * Admin Page Class
 *
 * Handles the admin interface for the Divi Janitor
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
 * Adarok Divi Janitor Admin Page Class
 *
 * Handles the admin interface for managing Divi Library items.
 *
 * @since 1.0.0
 */
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
		if ( null === self::$instance ) {
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
		// Check if someone is trying to access /wp-admin/divi-janitor.
		if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-admin/divi-janitor' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug ) );
			exit;
		}
	}

	/**
	 * Add admin menu page under Divi
	 */
	public function add_admin_menu() {
		// Check if Divi theme options page exists.
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

		// Also check if et_divi_options page will be registered.
		if ( ! $divi_exists && ( function_exists( 'et_divi_add_customizer_css' ) || wp_get_theme()->get( 'Name' ) === 'Divi' ) ) {
			$divi_exists = true;
		}

		if ( $divi_exists ) {
			// Add submenu under Divi.
			add_submenu_page(
				'et_divi_options',
				__( 'Divi Janitor', 'adarok-divi-janitor' ),
				__( 'Divi Janitor', 'adarok-divi-janitor' ),
				'manage_options',
				$this->page_slug,
				array( $this, 'render_page' )
			);
		} else {
			// Add as top-level menu if Divi is not available.
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
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our admin page (check both menu types).
		if ( 'divi_page_' . $this->page_slug !== $hook && 'toplevel_page_' . $this->page_slug !== $hook ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'adarok-divi-janitor-admin',
			ADAROK_DIVI_JANITOR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ADAROK_DIVI_JANITOR_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'adarok-divi-janitor-admin',
			ADAROK_DIVI_JANITOR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ADAROK_DIVI_JANITOR_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'adarok-divi-janitor-admin',
			'adarokDiviJanitor',
			array(
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'adarok_divi_janitor_nonce' ),
				'confirmDelete'           => __( 'Are you sure you want to delete this library item? This action cannot be undone.', 'adarok-divi-janitor' ),
				'confirmDeleteWithCopies' => __( 'This library item has instantiated copies in your content. These copies are independent and will NOT be affected.\n\nThe library item will be removed from your library, but the copied content will remain intact.\n\nDelete this library item?', 'adarok-divi-janitor' ),
				'confirmBulkDelete'       => __( 'Are you sure you want to delete ALL safe-to-delete library items?\n\nThis includes:\n- Items with no usage\n- Items with only instantiated copies (copies won\'t be affected)\n\nThis action cannot be undone.', 'adarok-divi-janitor' ),
				'deleteSuccess'           => __( 'Library item deleted successfully.', 'adarok-divi-janitor' ),
				'deleteError'             => __( 'Failed to delete library item.', 'adarok-divi-janitor' ),
				'scanningText'            => __( 'Scanning...', 'adarok-divi-janitor' ),
				'scanCompleteText'        => __( 'Scan Complete', 'adarok-divi-janitor' ),
			)
		);
	}

	/**
	 * Render the admin page
	 */
	public function render_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die() handles escaping
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'adarok-divi-janitor' ) );
		}

		// Get library items and their usage.
		$library_items = Adarok_Divi_Janitor_Library_Scanner::get_library_items();
		$library_items = Adarok_Divi_Janitor_Library_Scanner::find_usage( $library_items );
		$statistics    = Adarok_Divi_Janitor_Library_Scanner::get_statistics( $library_items );

		// Separate items by category.
		$used_items           = array();
		$unused_items         = array();
		$safe_to_delete_items = array();
		$copies_only_items    = array();
		$global_refs_items    = array();

		foreach ( $library_items as $item ) {
			if ( ! empty( $item['usage'] ) ) {
				$used_items[] = $item;

				// Check if has only copies (safe to delete).
				if ( Adarok_Divi_Janitor_Library_Scanner::has_only_copy_usage( $item['usage'] ) ) {
					$copies_only_items[] = $item;
				}

				// Check if has any global references.
				if ( Adarok_Divi_Janitor_Library_Scanner::has_global_usage( $item['usage'] ) ) {
					$global_refs_items[] = $item;
				}
			} else {
				$unused_items[] = $item;
			}

			// Safe to delete = unused OR only copies.
			if ( empty( $item['usage'] ) || Adarok_Divi_Janitor_Library_Scanner::has_only_copy_usage( $item['usage'] ) ) {
				$safe_to_delete_items[] = $item;
			}
		}

		?>
		<div class="wrap adarok-divi-janitor">
			<!-- Loading Overlay -->
			<div class="adarok-loading-overlay">
				<div class="adarok-loading-spinner">
					<div class="spinner"></div>
					<p><?php esc_html_e( 'Scanning Divi Library contents...', 'adarok-divi-janitor' ); ?></p>
				</div>
			</div>

			<div class="adarok-content-wrapper">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

				<div class="adarok-divi-janitor-intro">
				<p><?php esc_html_e( 'This tool helps you manage your Divi Library by showing where each library item is used throughout your site.', 'adarok-divi-janitor' ); ?></p>
				<p><strong><?php esc_html_e( 'Safe to Delete:', 'adarok-divi-janitor' ); ?></strong> <?php esc_html_e( 'Items with no usage or only instantiated copies can be safely deleted. Copies are independent and won\'t be affected.', 'adarok-divi-janitor' ); ?></p>
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
				<div class="stat-box stat-safe">
					<div class="stat-number"><?php echo esc_html( $statistics['safe_to_delete'] ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Safe to Delete', 'adarok-divi-janitor' ); ?></div>
				</div>
				<div class="stat-box stat-unused">
					<div class="stat-number"><?php echo esc_html( $statistics['unused'] ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Not Used', 'adarok-divi-janitor' ); ?></div>
				</div>
			</div>

			<?php if ( $statistics['global_refs'] > 0 || $statistics['copies'] > 0 ) : ?>
			<!-- Usage Type Statistics -->
			<div class="adarok-divi-janitor-usage-stats">
				<div class="usage-stats-header">
					<h3><?php esc_html_e( 'Usage Breakdown', 'adarok-divi-janitor' ); ?></h3>
					<div class="usage-legend">
						<span class="legend-item">
							<span class="usage-type-icon usage-type-icon-global">●</span>
							<?php esc_html_e( 'Global Reference', 'adarok-divi-janitor' ); ?>
						</span>
						<span class="legend-item">
							<span class="usage-type-icon usage-type-icon-copy">○</span>
							<?php esc_html_e( 'Instantiated Copy', 'adarok-divi-janitor' ); ?>
						</span>
					</div>
				</div>
				<div class="usage-stats-grid">
					<div class="usage-stat-box usage-stat-global">
						<div class="usage-stat-number"><?php echo esc_html( $statistics['global_refs'] ); ?></div>
						<div class="usage-stat-label"><?php esc_html_e( 'Global References', 'adarok-divi-janitor' ); ?></div>
						<div class="usage-stat-description"><?php esc_html_e( 'Linked to library (updates automatically)', 'adarok-divi-janitor' ); ?></div>
					</div>
					<div class="usage-stat-box usage-stat-copy">
						<div class="usage-stat-number"><?php echo esc_html( $statistics['copies'] ); ?></div>
						<div class="usage-stat-label"><?php esc_html_e( 'Instantiated Copies', 'adarok-divi-janitor' ); ?></div>
						<div class="usage-stat-description"><?php esc_html_e( 'Copied content (independent)', 'adarok-divi-janitor' ); ?></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

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
				<button class="tab-button tab-global" data-tab="global">
					<?php esc_html_e( 'Global References', 'adarok-divi-janitor' ); ?>
					<span class="count">(<?php echo esc_html( (string) count( $global_refs_items ) ); ?>)</span>
				</button>
				<button class="tab-button tab-safe" data-tab="safe">
					<?php esc_html_e( 'Safe to Delete', 'adarok-divi-janitor' ); ?>
					<span class="count">(<?php echo esc_html( $statistics['safe_to_delete'] ); ?>)</span>
				</button>
				<button class="tab-button" data-tab="unused">
					<?php esc_html_e( 'Not Used', 'adarok-divi-janitor' ); ?>
					<span class="count">(<?php echo esc_html( $statistics['unused'] ); ?>)</span>
				</button>
				<button class="tab-button" data-tab="copies">
					<?php esc_html_e( 'Only Copies', 'adarok-divi-janitor' ); ?>
					<span class="count">(<?php echo esc_html( $statistics['copies_only'] ); ?>)</span>
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

			<!-- Global References Tab -->
			<div class="tab-content" id="tab-global">
				<?php if ( ! empty( $global_refs_items ) ) : ?>
					<div class="bulk-actions-bar bulk-actions-info-only">
						<div class="bulk-actions-description">
							<p><strong><?php esc_html_e( 'ℹ️ About Global References', 'adarok-divi-janitor' ); ?></strong></p>
							<ul>
								<li><?php esc_html_e( 'These items have global references (●) - they are linked to your library', 'adarok-divi-janitor' ); ?></li>
								<li><?php esc_html_e( 'Changes to the library item automatically update everywhere it\'s used', 'adarok-divi-janitor' ); ?></li>
								<li><?php esc_html_e( 'Deleting will break the links - copied content will remain but won\'t update', 'adarok-divi-janitor' ); ?></li>
							</ul>
							<p class="warning"><strong><?php esc_html_e( '⚠️ Not recommended for deletion', 'adarok-divi-janitor' ); ?></strong> <?php esc_html_e( '- these items are actively linked to your content.', 'adarok-divi-janitor' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
				<?php $this->render_library_table( $global_refs_items ); ?>
			</div>

			<!-- Safe to Delete Tab -->
			<div class="tab-content" id="tab-safe">
				<?php if ( ! empty( $safe_to_delete_items ) ) : ?>
					<div class="bulk-actions-bar bulk-actions-safe">
						<button class="button button-primary bulk-delete-safe" type="button">
							<?php esc_html_e( 'Delete All Safe Items', 'adarok-divi-janitor' ); ?>
							<span class="count">(<?php echo count( $safe_to_delete_items ); ?>)</span>
						</button>
						<div class="bulk-actions-description">
							<p><strong><?php esc_html_e( 'Safe to delete includes:', 'adarok-divi-janitor' ); ?></strong></p>
							<ul>
								<li><?php esc_html_e( 'Items with no usage anywhere', 'adarok-divi-janitor' ); ?></li>
								<li><?php esc_html_e( 'Items with only instantiated copies (○) - copies remain intact', 'adarok-divi-janitor' ); ?></li>
							</ul>
							<p class="info-global"><?php esc_html_e( 'ℹ️ Items with global references (●) are excluded from this tab.', 'adarok-divi-janitor' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
				<?php $this->render_library_table( $safe_to_delete_items, true ); ?>
			</div>

			<!-- Unused Items Tab -->
			<div class="tab-content" id="tab-unused">
				<?php if ( ! empty( $unused_items ) ) : ?>
					<div class="bulk-actions-bar">
						<button class="button button-primary bulk-delete-unused" type="button">
							<?php esc_html_e( 'Delete All Unused Items', 'adarok-divi-janitor' ); ?>
							<span class="count">(<?php echo count( $unused_items ); ?>)</span>
						</button>
						<p class="bulk-actions-description">
							<?php esc_html_e( 'This will permanently delete all library items that have no usage anywhere on your site.', 'adarok-divi-janitor' ); ?>
						</p>
					</div>
				<?php endif; ?>
				<?php $this->render_library_table( $unused_items, true ); ?>
			</div>

			<!-- Only Copies Tab -->
			<div class="tab-content" id="tab-copies">
				<?php if ( ! empty( $copies_only_items ) ) : ?>
					<div class="bulk-actions-bar bulk-actions-info">
						<button class="button button-primary bulk-delete-copies" type="button">
							<?php esc_html_e( 'Delete All Copy-Only Items', 'adarok-divi-janitor' ); ?>
							<span class="count">(<?php echo count( $copies_only_items ); ?>)</span>
						</button>
						<div class="bulk-actions-description">
							<p><?php esc_html_e( 'These items only have instantiated copies (○). The copies are independent and will remain intact after deletion.', 'adarok-divi-janitor' ); ?></p>
							<p class="info"><?php esc_html_e( 'ℹ️ Deleting the library item only removes it from your library - all copied content stays in place.', 'adarok-divi-janitor' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
				<?php $this->render_library_table( $copies_only_items, true ); ?>
			</div>
			</div><!-- .adarok-content-wrapper -->
		</div>
		<?php
	}

	/**
	 * Render library items table
	 *
	 * @param array $items        Array of library items.
	 * @param bool  $show_delete  Whether to show delete buttons.
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
										<span class="usage-link-wrapper">
											<?php if ( isset( $usage['usage_type'] ) ) : ?>
												<span class="usage-type-icon usage-type-icon-<?php echo esc_attr( $usage['usage_type'] ); ?>"
														title="<?php echo $usage['usage_type'] === 'global' ? esc_attr__( 'Global Reference', 'adarok-divi-janitor' ) : esc_attr__( 'Instantiated Copy', 'adarok-divi-janitor' ); ?>">
													<?php echo $usage['usage_type'] === 'global' ? '●' : '○'; ?>
												</span>
											<?php endif; ?>
											<a href="<?php echo esc_url( $usage['view_url'] ); ?>" target="_blank">
												<?php echo esc_html( $usage['title'] ); ?>
											</a>
										</span>
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
