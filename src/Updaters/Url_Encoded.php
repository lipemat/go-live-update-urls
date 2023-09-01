<?php

namespace Go_Live_Update_Urls\Updaters;

/**
 * Url encoded Urls have special characters in place of typically entered
 * characters. This replaces standard characters with their encoded versions
 * during updating.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Url_Encoded extends Updaters_Abstract {
	/**
	 * Run this updater first.
	 *
	 * @return int
	 */
	public static function get_priority(): int {
		return 1;
	}


	/**
	 * Encode the URL for search and replace.
	 *
	 * @param string $url - Provided URL.
	 *
	 * @return string
	 */
	public static function apply_rule_to_url( $url ) {
		return rawurlencode( $url );
	}
}
