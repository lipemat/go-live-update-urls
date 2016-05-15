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
		<?php _e( 'This will replace all occurrences "in the entire database" of the Old URL with the New URL.', 'go-live-update-urls' ); ?>
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

		if( apply_filters( 'gluu-use-default_checkboxes', true ) ){
			?>
			<h2>
				<?php _e( 'WordPress Core Tables', 'go-live-update-urls' ); ?>
			</h2>
			<p class="description">
				<?php _e( 'These tables are probably safe to update with the basic version of this plugin.', 'go-live-update-urls' ); ?>
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
		}

		if( apply_filters( 'gluu-use-default_checkboxes', true ) ){
			?>
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
					data-un-checked="<?php _e( 'check all', 'go-live-update-urls' ); ?>" />
			</p>
			<?php
			echo $gluu->makeCheckBoxes( $gluu->get_custom_plugin_tables(), "custom-plugins", false );

		}

		?>
		<hr />
		<p>
			<strong>
			<?php
			echo apply_filters( 'gluu-uncheck-message', __(  'Un-check any above tables that you would not like to update.', 'go-live-update-urls' ) );
			?>
			</strong>
		</p>
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
		<?php submit_button( __( 'Make it Happen', 'go-live-update-urls' ), 'primary', 'gluu-submit' ); ?>
	</form>
</div>