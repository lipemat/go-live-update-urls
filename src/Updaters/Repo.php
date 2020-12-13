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
	 * Get all registered updaters by classname
	 * This list will grow over time as things are converted over
	 *
	 * @return array<Updaters_Abstract::class>
	 */
	public function get_updaters() {
		$updaters = apply_filters( 'go-live-update-urls/updaters/repo/updaters', [
			'url-encoded' => Url_Encoded::class,
		] );
		if ( ! is_array( $updaters ) ) {
			return [];
		}

		return $updaters;
	}
}
