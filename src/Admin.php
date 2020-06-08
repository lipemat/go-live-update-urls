<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Tools Page in WordPress Admin.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Admin {
	use Singleton;

	const NONCE            = 'go-live-update-urls/nonce/update-tables';
	const TABLE_INPUT_NAME = 'go-live-update-urls/input/database-table';
	const SUBMIT           = 'go-live-update-urls/input/submit';

	// @todo change these to snake-case after 6/1/18.
	const OLD_URL = 'oldurl';
	const NEW_URL = 'newurl';


	protected function hook() {
		if ( ! empty( $_POST[ self::SUBMIT ] ) ) {
			add_action( 'init', [ $this, 'validate_update_submission' ] );
		}

		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
	}


	/**
	 * Validate and trigger an update submission
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function validate_update_submission() {
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			wp_die( esc_html__( 'Ouch! That hurt! You should not be here!', 'go-live-update-urls' ) );
		}

		$old_url = trim( sanitize_text_field( $_POST[ self::OLD_URL ] ) );
		$new_url = trim( sanitize_text_field( $_POST[ self::NEW_URL ] ) );
		if ( empty( $old_url ) || empty( $new_url ) || empty( $_POST[ self::TABLE_INPUT_NAME ] ) ) {
			add_action( 'admin_notices', [ $this, 'epic_fail' ] );
			return;
		}

		$tables = array_map( 'sanitize_text_field', $_POST[ self::TABLE_INPUT_NAME ] );

		$this->tables = $tables; // For backward compatibility. Kill when this deprecated call is removed.
		do_action_deprecated( 'gluu-before-make-update', [ $this ], '5.0.0', 'go-live-update-urls/admin-page/before-update' );

		do_action( 'go-live-update-urls/admin-page/before-update', $old_url, $new_url, $tables );

		if ( Database::instance()->update_the_database( $old_url, $new_url, $tables ) ) {
			add_action( 'admin_notices', [ $this, 'success' ] );
			add_filter( 'go-live-update-urls/views/admin-tools-page/disable-description', '__return_true' );
		}
	}


	/**
	 * Render a success message as admin banner.
	 */
	public function success() {
		?>
		<div id="message" class="updated fade">
			<p>
				<strong>
					<?php echo esc_html( apply_filters( 'go-live-update-urls/admin/success', __( 'The URLS in the checked tables have been updated.', 'go-live-update-urls' ) ) ); ?>
				</strong>
			</p>
		</div>
		<?php
	}


	/**
	 * Display a message if any fields were not filed out.
	 *
	 * @return void
	 */
	public function epic_fail() {
		?>
		<div id="message" class="error fade">
			<p>
				<strong><?php esc_html_e( 'You must fill out both URLs and select tables to update URLs!', 'go-live-update-urls' ); ?></strong>
			</p>
		</div>
		<?php
	}


	/**
	 * Menu Under Tools Menu
	 *
	 * @since 5.0.0
	 */
	public function register_admin_page() {
		add_management_page( 'go-live-update-urls-setting', 'Go Live', 'manage_options', 'go-live-update-urls-settings', [
			$this,
			'admin_page',
		] );
	}


	/**
	 * Output the Admin Page for using this plugin
	 *
	 * @since 5.0.0
	 *
	 */
	public function admin_page() {
		wp_enqueue_script( 'go-live-update-urls-admin-page', Core::plugin_url( 'resources/js/admin-page.js' ), [ 'jquery' ], GO_LIVE_UPDATE_URLS_VERSION );

		require Core::instance()->get_view_file( 'admin-tools-page.php' );
	}


	/**
	 * Creates a list of checkboxes for each table
	 *
	 * @param array  $tables
	 * @param string $list - uses by js to separate lists
	 * @param bool   $checked
	 *
	 * @since  5.0.0
	 *
	 * @return void
	 *
	 */
	public function render_check_boxes( $tables, $list, $checked = true ) {
		?>
		<ul
			class="go-live-update-urls/checkboxes go-live-update-urls/checkboxes/<?php echo esc_attr( $list ); ?>"
			data-list="<?php echo esc_attr( $list ); ?>">
			<?php

			foreach ( $tables as $_table ) {
				?>
				<li>
					<?php
					printf( '<input name="%s[]" type="checkbox" value="%s" %s /> %s', esc_attr( self::TABLE_INPUT_NAME ), esc_attr( $_table ), checked( $checked, true, false ), esc_html( $_table ) );
					?>
				</li>
				<?php
			}

			?>
		</ul>
		<?php
	}
}
