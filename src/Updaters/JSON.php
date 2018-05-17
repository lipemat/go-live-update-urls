<?php

/**
 * JSON
 *
 * When a url is entered into the db using json_encode it
 * get's extra \/ at each slash
 *
 * This updater simulates the same slashes and replaces matches
 *
 * If the url either new or old has a slash we run this
 *
 * Because there is no real good way to detect this which is any lighter
 * than just doing the update we run it on each column
 *
 *
 * @author  Mat Lipe
 * @since   5.0.0
 *
 * @package Gluu\Updates
 */
class Go_Live_Update_Urls__Updaters__JSON extends Go_Live_Update_Urls__Updaters__Abstract {

	public function update_data() {
		if ( ! strpos( $this->new, '/' ) && ! strpos( $this->old, '/' ) ) {
			return false;
		}

		$old = substr( wp_json_encode( $this->old ), 1, - 1 );
		$new = substr( wp_json_encode( $this->new ), 1, - 1 );
		$this->update_column( $old, $new );

		return true;
	}

}
