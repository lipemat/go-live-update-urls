<?php

namespace Go_Live_Update_Urls\Updaters;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Repository for the Updater classes.
 *
 * @author  OnPoint Plugins
 * @since   6.0.0
 */
class Repo {
	use Singleton;

	/**
	 * Get all registered updaters by classname.
	 *
	 * @return array<string, class-string<Updaters_Abstract>>
	 */
	public function get_updaters(): array {
		$updaters = apply_filters( 'go-live-update-urls/updaters/repo/updaters', [
			'url-encoded' => Url_Encoded::class,
		] );
		if ( ! \is_array( $updaters ) ) {
			return [];
		}

		\uasort( $updaters,
			/**
			 * Sort the updater classes by priority.
			 *
			 * @param class-string<Updaters_Abstract> $a
			 * @param class-string<Updaters_Abstract> $b
			 */
			function( $a, $b ) {
				return $a::get_priority() <=> $b::get_priority();
			}
		);

		return $updaters;
	}
}
