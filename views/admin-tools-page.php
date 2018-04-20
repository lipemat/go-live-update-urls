<?php
/**
 * Main Admin screen view
 *
 * @since  5.0.0
 *
 * @uses   may be overridden in your theme by putting a copy of this file inside a go-live-update-urls folder
 */

$db    = Go_Live_Update_Urls_Database::instance();
$admin = Go_Live_Update_Urls_Admin_Page::instance();

?>
<div id="go-live-update-urls/admin-page" class="wrap">
	<h2>
		<?php esc_html_e( 'Go Live Update Urls', 'go-live-update-urls' ); ?>
	</h2>

	<p class="description">
		<?php
		/* translators: <strong></strong> */
		printf( esc_html_x( 'This will replace all occurrences %1$sin the entire database%2$s of the Old URL with the New URL.', '{<strong>} {</strong>}', 'go-live-update-urls' ), '<strong>', '</strong>' ); ?>
	</p>

	<strong>
		<em style="color:red">
			<?php esc_html_e( 'Like any other database updating tool, you should always perform a backup before running.', 'go-live-update-urls' ); ?>
		</em>
	</strong>
	<hr/>

	<form method="post" id="go-live-update-urls/checkbox-form">
		<?php
		wp_nonce_field( Go_Live_Update_Urls_Admin_Page::NONCE, Go_Live_Update_Urls_Admin_Page::NONCE );

		do_action( 'gluu_before_checkboxes', $db );

		if ( apply_filters( 'gluu-use-default_checkboxes', true ) ) {
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
				<hr />
			</p>
			<?php $admin->render_check_boxes( $db->get_core_tables(), 'wp-core' );

			$custom_tables = $db->get_custom_plugin_tables();
			if ( ! empty( $custom_tables ) ) {
				?>
				<hr/>

				<h2>
					<?php esc_html_e( 'Tables Created By Plugins', 'go-live-update-urls' ); ?>
				</h2>
				<p class="description" style="color:red">
					<strong>
						<?php
						/* translators: <br /> <a> </a> */
						printf( esc_html_x( 'These tables are probably NOT SAFE to update with the basic version of this plugin. %1$sTo support tables created by plugins use the %2$sPro Version%3$s.', '{<br />}{<a>}{</a>}', 'go-live-update-urls' ), '<br />', '<a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">', '</a>' ); ?></strong>
				</p>
				<p>
					<input
						type="checkbox"
						class="go-live-update-urls/checkboxes/check-all"
						data-list="custom-plugins"
						data-js="go-live-update-urls/checkboxes/check-all"/>
				<hr />
				</p>
				<?php $admin->render_check_boxes( $custom_tables, 'custom-plugins', false );
			}
		}

		do_action( 'gluu_after_checkboxes', $db );

		?>
		<hr/>
		<table class="form-table" id="go-live-update-urls/url-fields">
			<tr>
				<th scope="row" style="width:150px;">
					<label for="old_url">
						<?php esc_html_e( 'Old URL', 'go-live-update-urls' ); ?>
					</label>
				</th>
				<td>
					<input
						name="<?php echo esc_attr( Go_Live_Update_Urls_Admin_Page::OLD_URL ); ?>"
						type="text"
						id="old_url"
						value=""
						style="width:300px;"
						title="<?php esc_attr_e( 'Old URL', 'go-live-update-urls' ); ?>"/>
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
						name="<?php echo esc_attr( Go_Live_Update_Urls_Admin_Page::NEW_URL ); ?>"
						type="text"
						id="new_url"
						value=""
						style="width:300px;"
						title="<?php esc_attr_e( 'New URL', 'go-live-update-urls' ); ?>"/>
				</td>
			</tr>
		</table>
		<p class="description">
			<strong>
				<?php
				echo esc_html( apply_filters( 'gluu-uncheck-message', __( 'Only the checked tables will be updated.', 'go-live-update-urls' ) ) );
				?>
			</strong>
		</p>
		<?php
		if ( ! defined( 'GO_LIVE_UPDATE_URLS_PRO_VERSION' ) ) {
			?>
			<p class="description" style="color:#23282d">
				<strong>
					<?php
					/* translators: <a></a> */
					printf( esc_html_x( 'To test this change before running it, use %1$sPro Version 2.0.0+%2$s.', '{<a>}{</a>}', 'go-live-update-urls' ), '<a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">', '</a>' ); ?></strong>
			</p>
			<?php
		}
		?>
		<?php submit_button( __( 'Update URLs', 'go-live-update-urls' ), 'primary', Go_Live_Update_Urls_Admin_Page::SUBMIT ); ?>
	</form>
</div>
