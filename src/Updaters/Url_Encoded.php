<?php

/**
 * Go_Live_Update_Urls__Updaters__Url_Encoded
 *
 * @author Mat Lipe
 * @since  5/17/2018
 *
 */
class Go_Live_Update_Urls__Updaters__Url_Encoded extends Go_Live_Update_Urls__Updaters__Abstract {
	public function update_data() {
		$old = rawurlencode( $this->old );
		if ( $old === $this->old ) {
			return false;
		}

		$new = rawurlencode( $this->new );
		$this->update_column( $old, $new );

		return true;
	}


}
