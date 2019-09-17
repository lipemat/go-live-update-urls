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
 * @author  OnPoint Plugins
 * @since   5.0.0
 *
 * @package Gluu\Updates
 */
class Go_Live_Update_Urls__Updaters__JSON extends Go_Live_Update_Urls__Updaters__Abstract {
	public function apply_rule_to_url( $url ) {
		return substr( wp_json_encode( $url ), 1, - 1 );
	}


	public function update_data() {
		if ( ! strpos( $this->new, '/' ) && ! strpos( $this->old, '/' ) ) {
			return false;
		}

		$this->update_column( $this->apply_rule_to_url( $this->old ), $this->apply_rule_to_url( $this->new ) );

		return true;
	}

}
