<?php

namespace Go_Live_Update_Urls\Updaters;

/**
 * URL Encoded
 *
 * Url encoded URLs have special characters in place of typically entered
 * characters. This replaces standard characters with their encoded versions
 * during updating.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Url_Encoded extends Updaters_Abstract {
	/**
	 * Encode the URL for search and replace.
	 *
	 * @param string $url - Provided URL.
	 *
	 * @return string
	 */
	public function apply_rule_to_url( $url ) {
		return rawurlencode( $url );
	}


	/**
	 * Update the old encoded URL with the new encoded URL if the entered
	 * old URL is different than the encoded version.
	 *
	 * @return bool
	 */
	public function update_data() {
		$old = $this->apply_rule_to_url( $this->old );
		if ( $old === $this->old ) {
			return false;
		}

		$this->update_column( $old, $this->apply_rule_to_url( $this->new ) );

		return true;
	}

}
