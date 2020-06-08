<?php

namespace Go_Live_Update_Urls\Updaters;

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
 * @author  OnPoint Plugins
 * @since   6.0.0
 */
class JSON extends Updaters_Abstract {
	/**
	 * JSON encode the URL for search and replace.
	 *
	 * @param string $url - Provided URL.
	 *
	 * @return string
	 */
	public function apply_rule_to_url( $url ) {
		return substr( wp_json_encode( $url ), 1, - 1 );
	}

	/**
	 * Update the old JSON encoded URL with the new encoded URL if the entered
	 * old URL or new URL has a "/" in it.
	 * If no URL has a "/" in it, we don't need to run this.
	 *
	 * @return bool
	 */
	public function update_data() {
		if ( ! strpos( $this->new, '/' ) && ! strpos( $this->old, '/' ) ) {
			return false;
		}

		$this->update_column( $this->apply_rule_to_url( $this->old ), $this->apply_rule_to_url( $this->new ) );

		return true;
	}

}
