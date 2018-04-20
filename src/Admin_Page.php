<?php

/**
 * Go_Live_Update_Urls_Admin_Page
 *
 * @author Mat Lipe
 * @since  5.0.0
 *
 */
class Go_Live_Update_Urls_Admin_Page {
	const NONCE = 'go-live-update-urls/nonce/update-tables';
	const TABLE_INPUT_NAME = 'go-live-update-urls/input/database-table';
	const SUBMIT = 'go-live-update-urls/input/submit';

	//@todo change these to snake-case after 6/1/18
	const OLD_URL = 'oldurl';
	const NEW_URL = 'newurl';


	protected function hook() {
		if ( ! empty( $_POST[ self::SUBMIT ] ) ) {
			add_action( 'init', array( $this, 'validate_update_submission' ) );
		}

		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
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
		if ( empty( $old_url ) || empty( $new_url ) ) {
			add_action( 'admin_notices', array( $this, 'epic_fail' ) );
			return;
		}

		$tables = array_map( 'sanitize_text_field', $_POST[ self::TABLE_INPUT_NAME ] );

		if ( has_action( 'gluu-before-make-update' ) ) {
			_deprecated_hook( 'gluu-before-make-update', '5.0.0', 'go-live-update-urls/admin-page/before-update' );
			$this->tables = $tables;
			do_action( 'gluu-before-make-update', $this );
		}

		do_action( 'go-live-update-urls/admin-page/before-update', $old_url, $new_url, $tables );

		if ( Go_Live_Update_Urls_Database::instance()->update_the_database( $old_url, $new_url, $tables ) ) {
			add_action( 'admin_notices', array( $this, 'success' ) );
		}
	}


	public function success() {
		$message = apply_filters( 'go-live-update-urls-success-message', __( 'The URLS in the checked tables have been updated.', 'go-live-update-urls' ) );
		?>
		<div id="message" class="updated fade">
			<p>
				<strong>
					<?php echo esc_html( $message ); ?>
				</strong>
			</p>
		</div>
		<?php
	}


	public function epic_fail() {
		?>
		<div id="message" class="error fade">
			<p>
				<strong><?php esc_html_e( 'You must fill out both URLS to make the update!.', 'go-live-update-urls' ); ?></strong>
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
		add_management_page( 'go-live-update-urls-setting', 'Go Live', 'manage_options', 'go-live-update-urls-settings', array(
			$this,
			'admin_page',
		) );
	}


	/**
	 * Output the Admin Page for using this plugin
	 *
	 * @since 5.0.0
	 *
	 */
	public function admin_page() {
		wp_enqueue_script( 'go-live-update-urls-admin-page', Go_Live_Update_Urls_Core::plugin_url( 'resources/js/admin-page.js' ), array( 'jquery' ), GO_LIVE_UPDATE_URLS_VERSION );

		require Go_Live_Update_Urls_Core::instance()->get_view_file( 'admin-tools-page.php' );
	}


	/**
	 * Creates a list of checkboxes for each table
	 *
	 * @since  5.0.0
	 *
	 * @param array  $tables
	 * @param string $list - uses by js to separate lists
	 * @param bool   $checked
	 *
	 * @return void
	 *
	 */
	public function render_check_boxes( $tables, $list, $checked = true ) {
		$serialized_tables = Go_Live_Update_Urls_Database::instance()->get_serialized_tables();
		?>
		<ul class="go-live-update-urls/checkboxes go-live-update-urls/checkboxes/<?php echo esc_attr( $list ); ?>"
		    data-list="<?php echo esc_attr( $list ); ?>">
			<?php

			foreach ( $tables as $_table ) {
				?>
				<li>
					<?php
					printf( '<input name="%s[]" type="checkbox" value="%s" %s /> %s', esc_attr( self::TABLE_INPUT_NAME ), esc_attr( $_table ), checked( $checked, true, false ), esc_html( $_table ) );
					if ( isset( $serialized_tables[ $_table ] ) ) {
						?>
						- <strong>
							<em>
								<?php esc_html_e( 'Serialized', 'go-live-update-urls' ); ?>
							</em>
						</strong>
						<?php
					}
					?>
				</li>
				<?php
			}

			?>
		</ul>
		<?php


	}

	//********** SINGLETON **********/


	/**
	 * Instance of this class for use as singleton
	 *
	 * @var self
	 */
	protected static $instance;


	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::instance()->hook();
	}


	/**
	 * Get (and instantiate, if necessary) the instance of the
	 * class
	 *
	 * @static
	 * @return self
	 */
	public static function instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
