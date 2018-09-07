<?php

/**
 * Go_Live_Update_Urls__Updaters__Url_Encoded
 *
 * @author Mat Lipe
 * @since  5/17/2018
 *
 */
class Go_Live_Update_Urls__Updaters__Url_Encoded extends Go_Live_Update_Urls__Updaters__Abstract {

	public function apply_rule_to_url( $url ) {
		return rawurlencode( $url );
	}


	public function update_data() {
		$old = $this->apply_rule_to_url( $this->old );
		if ( $old === $this->old ) {
			return false;
		}

		$this->update_column( $old, $this->apply_rule_to_url( $this->new ) );

		return true;
	}


}
