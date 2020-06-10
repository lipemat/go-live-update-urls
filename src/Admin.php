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

	const OLD_URL          = 'old_url';
	const NEW_URL          = 'new_url';
	const NONCE            = 'go-live-update-urls/nonce/update-tables';
	const TABLE_INPUT_NAME = 'go-live-update-urls/input/database-table';
	const SUBMIT           = 'go-live-update-urls/input/submit';


	/**
	 * Add actions.
	 */
	protected function hook() {
		if ( ! empty( $_POST[ self::SUBMIT ] ) ) { //phpcs:ignore
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
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( $_POST[ self::NONCE ], self::NONCE ) ) {
			wp_die( esc_html__( 'Ouch! That hurt! You should not be here!', 'go-live-update-urls' ) );
		}

		if ( empty( $_POST[ self::OLD_URL ] ) || empty( $_POST[ self::NEW_URL ] ) ) {
			$this->failure_message();
			return;
		}

		$old_url = trim( sanitize_text_field( \wp_unslash( $_POST[ self::OLD_URL ] ) ) );
		$new_url = trim( sanitize_text_field( \wp_unslash( $_POST[ self::NEW_URL ] ) ) );
		if ( empty( $old_url ) || empty( $new_url ) || empty( $_POST[ self::TABLE_INPUT_NAME ] ) ) {
			$this->failure_message();
			return;
		}

		$tables = array_map( 'sanitize_text_field', \wp_unslash( $_POST[ self::TABLE_INPUT_NAME ] ) );

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
	public function failure_message() {
		add_action( 'admin_notices', function () {
			?>
			<div id="message" class="error fade">
				<p>
					<strong>
						<?php esc_html_e( 'You must fill out both URLs and select tables to update URLs!', 'go-live-update-urls' ); ?>
					</strong>
				</p>
			</div>
			<?php
		} );
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
	 */
	public function admin_page() {
		wp_enqueue_script( 'go-live-update-urls-admin-page', GO_LIVE_UPDATE_URLS_URL . 'resources/js/admin-page.js', [ 'jquery' ], GO_LIVE_UPDATE_URLS_VERSION, true );

		?>
		<div id="go-live-update-urls/admin-page" class="wrap">
			<h2>
				<?php esc_html_e( 'Go Live Update Urls', 'go-live-update-urls' ); ?>
			</h2>

			<?php
			if ( ! apply_filters( 'go-live-update-urls/views/admin-tools-page/disable-description', false ) ) {
				?>

				<p class="description">
					<?php
					/* translators: <strong></strong> */
					printf( esc_html_x( 'This will replace all occurrences %1$sin the entire database%2$s of the Old URL with the New URL.', '{<strong>} {</strong>}', 'go-live-update-urls' ), '<strong>', '</strong>' );
					?>
				</p>

				<strong>
					<em style="color:red">
						<?php esc_html_e( 'Like any other database updating tool, you should always perform a backup before running.', 'go-live-update-urls' ); ?>
					</em>
				</strong>
				<br /><br />
				<hr />

				<?php
			}
			?>

			<form method="post" class="go-live-update-urls/admin/checkbox-form">
				<?php
				wp_nonce_field( self::NONCE, self::NONCE );

				do_action( 'go-live-update-urls-pro/admin/before-checkboxes', Database::instance() );

				if ( apply_filters( 'go-live-update-urls-pro/admin/use-default-checkboxes', true ) ) {
					?>
					<h2>
						<?php esc_html_e( 'WordPress Core Tables', 'go-live-update-urls' ); ?>
					</h2>
					<p class="description" style="color:green">
						<strong>
							<?php esc_attr_e( 'These tables are safe to update with the basic version of this plugin.', 'go-live-update-urls' ); ?>
						</strong>
					</p>
					<p>
						<input
							type="checkbox"
							class="go-live-update-urls/checkboxes/check-all"
							data-list="wp-core"
							data-js="go-live-update-urls/checkboxes/check-all"
							checked
						/>
					</p>
					<hr />
					<?php
					$this->render_check_boxes( Database::instance()->get_core_tables(), 'wp-core' );
					$custom_tables = Database::instance()->get_custom_plugin_tables();
					if ( ! empty( $custom_tables ) ) {
						?>
						<hr />

						<h2>
							<?php esc_html_e( 'Tables Created By Plugins', 'go-live-update-urls' ); ?>
						</h2>
						<p class="description" style="color:red">
							<strong>
								<?php
								/* translators: <br /> <a> </a> */
								printf( esc_html_x( 'These tables are probably NOT SAFE to update with the basic version of this plugin. %1$sTo support tables created by plugins use the %2$sPro Version%3$s.', '{<br />}{<a>}{</a>}', 'go-live-update-urls' ), '<br />', '<a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">', '</a>' );
								?>
							</strong>
						</p>
						<p>
							<input
								type="checkbox"
								class="go-live-update-urls/checkboxes/check-all"
								data-list="custom-plugins"
								data-js="go-live-update-urls/checkboxes/check-all" />
						</p>
						<hr />
						<?php
						$this->render_check_boxes( $custom_tables, 'custom-plugins', false );
					}
				}

				do_action( 'go-live-update-urls-pro/admin/after-checkboxes', Database::instance() );

				?>
				<hr />
				<table class="form-table" data-js="go-live-update-urls/admin/url-fields">
					<tr>
						<th scope="row" style="width:150px;">
							<label for="old_url">
								<?php esc_html_e( 'Old URL', 'go-live-update-urls' ); ?>
							</label>
						</th>
						<td>
							<input
								name="<?php echo esc_attr( self::OLD_URL ); ?>"
								type="text"
								id="old_url"
								value=""
								style="width:300px;"
								title="<?php esc_attr_e( 'Old URL', 'go-live-update-urls' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row" style="width:150px;">
							<label for="new_url">
								<?php esc_attr_e( 'New URL', 'go-live-update-urls' ); ?>
							</label>
						</th>
						<td>
							<input
								name="<?php echo esc_attr( self::NEW_URL ); ?>"
								type="text"
								id="new_url"
								value=""
								style="width:300px;"
								title="<?php esc_attr_e( 'New URL', 'go-live-update-urls' ); ?>" />
						</td>
					</tr>
				</table>
				<p class="description" data-js="go-live-update-urls/admin/only-checked-tables">
					<strong>
						<?php esc_html_e( 'Only the checked tables will be updated.', 'go-live-update-urls' ); ?>
					</strong>
				</p>
				<?php
				if ( apply_filters( 'go-live-update-urls-pro/admin/use-default-checkboxes', true ) ) {
					?>
					<p class="description" style="color:#23282d">
						<strong>
							<?php
							/* translators: <a></a> */
							printf( esc_html_x( 'Use the %1$sPRO version%2$s to test the updates before making them.', '{<a>}{</a>}', 'go-live-update-urls' ), '<a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">', '</a>' );
							?>
						</strong>
					</p>
					<?php
				}
				?>
				<?php submit_button( __( 'Update URLs', 'go-live-update-urls' ), 'primary', self::SUBMIT ); ?>
			</form>
		</div>
		<?php
	}


	/**
	 * Creates a list of checkboxes for each table
	 *
	 * @param array  $tables - List of all tables.
	 * @param string $list - Used by js to separate lists.
	 * @param bool   $checked - Should all checkboxes be checked.
	 *
	 * @since  5.0.0
	 *
	 * @return void
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
					printf( '<input name="%s[]" type="checkbox" value="%s" %s/> %s', esc_attr( self::TABLE_INPUT_NAME ), esc_attr( $_table ), checked( $checked, true, false ), esc_html( $_table ) );
					?>
				</li>
				<?php
			}

			?>
		</ul>
		<?php
	}
}
