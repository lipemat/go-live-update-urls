<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Tools page in WordPress admin.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Admin {
	use Singleton;

	const NAME = 'go-live-update-urls-settings';

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
		if ( ! isset( $_POST[ static::NONCE ] ) || ! wp_verify_nonce( $_POST[ static::NONCE ], static::NONCE ) ) {
			wp_die( esc_html__( 'Ouch! That hurt! You should not be here!', 'go-live-update-urls' ) );
		}

		if ( empty( $_POST[ static::OLD_URL ] ) || empty( $_POST[ static::NEW_URL ] ) ) {
			$this->failure_message();
			return;
		}

		$old_url = Core::instance()->sanitize_field( $_POST[ self::OLD_URL ] ); //phpcs:ignore
		$new_url = Core::instance()->sanitize_field( $_POST[ self::NEW_URL ] ); //phpcs:ignore
		if ( empty( $old_url ) || empty( $new_url ) || empty( $_POST[ static::TABLE_INPUT_NAME ] ) ) {
			$this->failure_message();
			return;
		}

		$tables = \array_filter( \array_map( [
			Core::instance(),
			'sanitize_field',
		], (array) $_POST[ self::TABLE_INPUT_NAME ] ) ); //phpcs:ignore

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
					<?php echo esc_html( apply_filters( 'go-live-update-urls/admin/success', __( 'The urls in the checked tables have been updated.', 'go-live-update-urls' ) ) ); ?>
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
		add_action( 'admin_notices', function() {
			?>
			<div id="message" class="error fade">
				<p>
					<strong>
						<?php esc_html_e( 'You must select tables and fill out both the Old URL and New URL to update urls!', 'go-live-update-urls' ); ?>
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
		add_management_page( 'Go Live Update Urls', 'Go Live', 'manage_options', static::NAME, [
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
		wp_enqueue_script( 'go-live-update-urls/admin/admin-page/js', GO_LIVE_UPDATE_URLS_URL . 'resources/go-live-update-urls.js', [ 'jquery' ], GO_LIVE_UPDATE_URLS_VERSION, true );
		wp_enqueue_style( 'go-live-update-urls/admin/admin-page/css', GO_LIVE_UPDATE_URLS_URL . 'resources/go-live-update-urls.css', [], GO_LIVE_UPDATE_URLS_VERSION );

		?>
		<div id="go-live-update-urls/admin-page">
			<div class="go-live-header-wrap">
				<div>
					<h1 class="dashicons-before dashicons-update">
						<a
							href="https://wordpress.org/plugins/go-live-update-urls/"
							target="_blank"
							rel="noopener noreferrer">
							<?php esc_html_e( 'Go Live Update Urls', 'go-live-update-urls' ); ?>
						</a>
					</h1>
				</div>
				<div class="go-live-header-message">
					<?php esc_html_e( 'Replaces all occurrences in the entire database of the Old URL with a New URL.', 'go-live-update-urls' ); ?>
				</div>
			</div>
			<form
				method="post"
				class="go-live-checkbox-form">
				<?php
				wp_nonce_field( static::NONCE, static::NONCE );

				do_action( 'go-live-update-urls-pro/admin/before-checkboxes', Database::instance() );

				if ( apply_filters( 'go-live-update-urls-pro/admin/use-default-checkboxes', true ) ) {
					?>
					<h3>
						<?php esc_html_e( 'WordPress core tables', 'go-live-update-urls' ); ?>
					</h3>
					<div class="go-live-section">
						<p class="description" style="color:green">
							<strong>
								<?php esc_attr_e( 'These tables are safe to update.', 'go-live-update-urls' ); ?>
							</strong>
						</p>
						<p>
							<input
								type="checkbox"
								data-list="wp-core"
								data-js="go-live-update-urls/checkboxes/check-all"
								checked
							/>
							<span class="go-live-only-checked"><?php esc_html_e( 'Only the checked tables will be updated.', 'go-live-update-urls' ); ?></span>
						</p>
						<hr />

						<?php
						$this->render_check_boxes( Database::instance()->get_core_tables(), 'wp-core' );
						?>
					</div>
					<?php

					$custom_tables = Database::instance()->get_custom_plugin_tables();
					if ( ! empty( $custom_tables ) ) {
						?>
						<h3>
							<?php esc_html_e( 'Tables created by plugins', 'go-live-update-urls' ); ?>
						</h3>
						<div class="go-live-section">
							<p class="description" style="color:red">
								<strong>
									<?php
									/* translators: <br /> <a> </a> */
									printf( esc_html_x( 'These tables are not safe to update with the basic version of this plugin! %1$sTo update tables created by plugins, use the %2$sPRO version%3$s.', '{<br />}{<a>}{</a>}', 'go-live-update-urls' ), '<br />', '<a href="https://onpointplugins.com/product/go-live-update-urls-pro/?utm_source=plugin-tables&utm_campaign=gopro&utm_medium=wp-dash" target="_blank">', '</a>' );
									?>
								</strong>
							</p>
							<p>
								<input
									type="checkbox"
									data-list="custom-plugins"
									data-js="go-live-update-urls/checkboxes/check-all" />
								<span class="go-live-only-checked"><?php esc_html_e( 'Only the checked tables will be updated.', 'go-live-update-urls' ); ?></span>
							</p>
							<hr />

							<?php
							$this->render_check_boxes( $custom_tables, 'custom-plugins', false );
							?>
						</div>
						<?php
					}
				}

				do_action( 'go-live-update-urls-pro/admin/after-checkboxes', Database::instance() );

				if ( apply_filters( 'go-live-update-urls-pro/admin/use-default-inputs', true ) ) {
					?>
					<table class="form-table go-live-inputs">
						<tr class="go-live-inputs-old-url">
							<th scope="row">
								<label for="old_url">
									<?php esc_html_e( 'Old URL', 'go-live-update-urls' ); ?>
								</label>
							</th>
							<td>
								<input
									name="<?php echo esc_attr( static::OLD_URL ); ?>"
									type="text"
									id="old_url"
									value=""
									class="regular-text"
									title="<?php esc_attr_e( 'Old URL', 'go-live-update-urls' ); ?>" />
							</td>
						</tr>
						<tr class="go-live-inputs-new-url">
							<th scope="row">
								<label for="new_url">
									<?php esc_attr_e( 'New URL', 'go-live-update-urls' ); ?>
								</label>
							</th>
							<td>
								<input
									name="<?php echo esc_attr( static::NEW_URL ); ?>"
									type="text"
									id="new_url"
									value=""
									class="regular-text"
									title="<?php esc_attr_e( 'New URL', 'go-live-update-urls' ); ?>" />
							</td>
						</tr>
					</table>


					<?php
				}
				if ( apply_filters( 'go-live-update-urls-pro/admin/use-default-checkboxes', true ) ) {
					?>
					<p class="description">
						<strong>

							<?php
							/* translators: <a></a> */
							printf( esc_html_x( 'Use the %1$sPRO version%2$s to test URL updates before making them.', '{<a>}{</a>}', 'go-live-update-urls' ), '<a href="https://onpointplugins.com/product/go-live-update-urls-pro/?utm_source=url-test&utm_campaign=gopro&utm_medium=wp-dash" target="_blank">', '</a>' );
							?>

						</strong>
					</p>
					<?php
				}
				?>
				<?php submit_button( __( 'Update Urls', 'go-live-update-urls' ), 'primary', static::SUBMIT ); ?>
			</form>
		</div>
		<?php
	}


	/**
	 * Creates a list of checkboxes for each table
	 *
	 * @param array  $tables  - List of all tables.
	 * @param string $list    - Used by js to separate lists.
	 * @param bool   $checked - Should all checkboxes be checked.
	 *
	 * @since  5.0.0
	 *
	 * @return void
	 */
	public function render_check_boxes( $tables, $list, $checked = true ) {
		?>
		<ul
			data-list="<?php echo esc_attr( $list ); ?>">
			<?php
			foreach ( $tables as $_table ) {
				?>
				<li>
					<?php
					printf( '<input name="%s[]" type="checkbox" value="%s" %s/> %s', esc_attr( static::TABLE_INPUT_NAME ), esc_attr( $_table ), checked( $checked, true, false ), esc_html( $_table ) );
					?>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}


	/**
	 * Get the URL of the tools page.
	 *
	 * @return string
	 */
	public function get_url() {
		return admin_url( 'tools.php?page=' . static::NAME );
	}
}
