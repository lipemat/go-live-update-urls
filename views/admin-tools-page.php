<?php
/**
 * Main Admin screen view
 *
 * @author Mat Lipe
 *
 * @uses   may be overridden in your theme by putting a copy of this file inside a go-live-update-urls folder
 */

$gluu = GoLiveUpdateUrls::get_instance();

?>
<div id="gluu" class="wrap">
	<h2>Go Live Update Urls</h2>

	<p class="description">
		<?php printf( _x( 'This will replace all occurrences %sin the entire database%s of the Old URL with the New URL.', '{<strong>}', '{/strong}', 'go-live-update-urls' ), '<strong>', '</strong>' ); ?>
	</p>

	<strong>
		<em style="color:red">
			<?php _e( "Like any other database updating tool, you should always perform a backup before running.", 'go-live-update-urls' ); ?>
		</em>
	</strong>
	<hr />

	<form method="post" id="gluu-checkbox-form">
		<?php
		wp_nonce_field( GoLiveUpdateUrls::NONCE, GoLiveUpdateUrls::NONCE );

		do_action( 'gluu_before_checkboxes', $gluu );

		if( apply_filters( 'gluu-use-default_checkboxes', true ) ){
			?>
			<h2>
				<?php _e( 'WordPress Core Tables', 'go-live-update-urls' ); ?>
			</h2>
			<p class="description">
				<?php _e( 'These tables are safe to update with the basic version of this plugin (the version you are currently using).', 'go-live-update-urls' ); ?>
			</p>
			<p>
				<input
					type="button"
					class="button-secondary checked gluu-tables-button"
					data-list="wp-core"
					value="<?php _e( 'un-check all', 'go-live-update-urls' ); ?>"
					data-checked="<?php _e( 'un-check all', 'go-live-update-urls' ); ?>"
					data-un-checked="<?php _e( 'check all', 'go-live-update-urls' ); ?>" />
			</p>
			<?php
			echo $gluu->makeCheckBoxes( $gluu->get_core_tables(), "wp-core" );

			$custom_tables = $gluu->get_custom_plugin_tables();
			if( !empty( $custom_tables ) ){
				?>
				<hr/>

				<h2>
					<?php _e( 'Tables Created By Plugins', 'go-live-update-urls' ); ?>
				</h2>
				<p class="description" style="color:red">
					<strong><?php printf( _x( 'These tables are probably NOT SAFE to update with the basic version of this plugin. %sTo support tables created by plugins use the %sPro Version%s.', '{<br />}{<a>}{</a>}', 'go-live-update-urls' ), '<br />', '<a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">', '</a>' ); ?></strong>
				</p>
				<p>
					<input
						type="button"
						class="button-secondary gluu-tables-button"
						data-list="custom-plugins"
						value="<?php _e( 'check all', 'go-live-update-urls' ); ?>"
						data-checked="<?php _e( 'un-check all', 'go-live-update-urls' ); ?>"
						data-un-checked="<?php _e( 'check all', 'go-live-update-urls' ); ?>"/>
				</p>
				<?php
				echo $gluu->makeCheckBoxes( $custom_tables, "custom-plugins", false );
			}
		}

		do_action( 'gluu_after_checkboxes', $gluu );

		?>
		<hr />
		<table class="form-table">
			<tr>
				<th scope="row" style="width:150px;">
					<b><?php _e( 'Old URL', 'go-live-update-urls' ); ?></b>
				</th>
				<td>
					<input name="oldurl" type="text" id="oldurl" value="" style="width:300px;" title="<?php _e( 'Old URL', 'go-live-update-urls' ); ?>"/>
				</td>
			</tr>
			<tr>
				<th scope="row" style="width:150px;">
					<b><?php _e( 'New URL', 'go-live-update-urls' ); ?></b>
				</th>
				<td>
					<input name="newurl" type="text" id="newurl" value="" style="width:300px;" title="<?php _e( 'New URL', 'go-live-update-urls' ); ?>"/>
				</td>
			</tr>
		</table>
		<p class="description">
			<strong>
				<?php
				echo apply_filters( 'gluu-uncheck-message', __(  'Only the checked tables will be updated.', 'go-live-update-urls' ) );
				?>
			</strong>
		</p>
		<?php submit_button( __( 'Make It Happen', 'go-live-update-urls' ), 'primary', 'gluu-submit' ); ?>
	</form>
</div>