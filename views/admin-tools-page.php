<?php

/**
 * Main Admin screen view
 *
 * @author Mat Lipe
 *
 * @uses   may be overridden in your theme by putting a copy of this file inside a go-live-update-urls folder
 */
?>

<div id="gluu" class="wrap">
	<h2>Go Live Update Urls</h2>

	<h4><?php _e( 'This will replace all occurrences "in the entire database" of the old URL with the New URL.', 'go-live-update-urls' ); ?></h4>

	<div class="error fade">
		<p>
			<?php
			$message = sprintf( __( "Please un-check any tables which may contain serialized data. The only tables which are currently serialized data safe when using this plugin are %s", 'go-live-update-urls' ), "(" . implode( ', ', array_keys( $this->getSerializedTables() ) ) . ")" );

			echo apply_filters( 'gluu-top-message', $message, $this->getSerializedTables() );
			?>
		</p>
	</div>

	<strong>
		<em style="color:red">
			<?php _e( "Like any other database updating tool, you should always perform a backup before running.", 'go-live-update-urls' ); ?>
		</em>
	</strong>


	<h4>
		<?php
		echo apply_filters( 'gluu-uncheck-message', __(  'Un-check any tables that you would not like to update.', 'go-live-update-urls' ) );
		?>
	</h4>


	<p>
		<input type="button" class="button secondary" value="uncheck all" id="uncheck-button"/>
	</p>
	<form method="post" id="gluu-checkbox-form">
		<?php //Make the boxes to select tables
		if( apply_filters( 'gluu-use-default_checkboxes', true ) ){
			echo $this->makeCheckBoxes();
		}
		?>
		<table class="form-table">
			<tr>
				<th scope="row" style="width:150px;"><b>Old URL</b></th>
				<td>
					<input name="oldurl" type="text" id="oldurl" value="" style="width:300px;"/>
				</td>
			</tr>
			<tr>
				<th scope="row" style="width:150px;"><b>New URL</b></th>
				<td>
					<input name="newurl" type="text" id="newurl" value="" style="width:300px;"/>
				</td>
			</tr>
		</table>
		<p class="submit">
			<?php submit_button( 'Make it Happen', 'primary', 'gluu-submit' ); ?>
		</p>
		<?php
		echo $nonce;
		?>

	</form>
</div>
<script type="text/javascript">
	jQuery( '#uncheck-button' ).click( function(){
		if( jQuery( this ).val() == 'uncheck all' ){
			jQuery( '#gluu-checkbox-form input[type="checkbox"]' ).attr( 'checked', false );
			jQuery( this ).val( 'check all' );
		} else {
			jQuery( '#gluu-checkbox-form input[type="checkbox"]' ).attr( 'checked', true );
			jQuery( this ).val( 'uncheck all' );
		}
	} );
</script>