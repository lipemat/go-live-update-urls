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
	 * Encode the URL for search and replace.
	 *
	 * @param string $url - Provided URL.
	 *
	 * @return string
	 */
	public static function apply_rule_to_url( $url ) {
		return rawurlencode( $url );
	}


	/**
	 * Get the old and new URL with the extra escaping applied.
	 *
	 * @since 9.10.0
	 *
	 * @param string $old - Old URL.
	 * @param string $new - New URL.
	 *
	 * @return array{new: string, old: string}
	 */
	public static function get_formatted( string $old, string $new ) : array {
		if ( static::is_appending_update( $old, $new ) ) {
			$prefix = static::apply_rule_to_url( '/' );
			return [
				'old' => $prefix . $new,
				'new' => $prefix . static::apply_rule_to_url( $new ),
			];
		}

		return [
			'old' => static::apply_rule_to_url( $old ),
			'new' => static::apply_rule_to_url( $new ),
		];
	}


	/**
	 * Was the data previously updated and counted?
	 *
	 * @param string $old - Old URL.
	 * @param string $new - New URL.
	 *
	 * @return bool
	 */
	public static function is_appending_update( string $old, string $new ) : bool {
		return static::apply_rule_to_url( $old ) === $old && static::apply_rule_to_url( $new ) !== $new;
	}


	/**
	 * Update the old encoded URL with the new encoded URL if the entered
	 * old URL is different from the encoded version.
	 *
	 * @return int
	 */
	public function update_data() {
		if ( static::apply_rule_to_url( $this->old ) === $this->old && static::apply_rule_to_url( $this->new ) === $this->new ) {
			return 0;
		}
		$formatted = static::get_formatted( $this->old, $this->new );

		$count = $this->update_column( $formatted['old'], $formatted['new'] );
		if ( static::is_appending_update( $this->old, $this->new ) ) {
			return 0;
		}
		return $count;
	}

}
