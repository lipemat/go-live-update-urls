<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Core functionality for the plugin.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Core {
	use Singleton;

	const MEMORY_LIMIT = '256M';


	/**
	 * Actions and filters.
	 */
	protected function hook() {
		add_action( 'go-live-update-urls/database/before-update', [ $this, 'raise_resource_limits' ], 0, 0 );
		add_filter( 'go-live-update-urls/database/memory-limit_memory_limit', [
			$this,
			'raise_memory_limit',
		], 0, 0 );
	}


	/**
	 * 1. Set time limit to unlimited
	 * 2. Set input time to unlimited
	 * 3. Set memory limit to context which will use our filter
	 *
	 * @see Core::raise_memory_limit();
	 *
	 * @return void
	 */
	public function raise_resource_limits() {
		@set_time_limit( 0 ); //phpcs:ignore
		@ini_set( 'max_input_time', '-1' ); //phpcs:ignore

		wp_raise_memory_limit( 'go-live-update-urls/database/memory-limit' );
	}


	/**
	 * Raise the memory limit while the Database runs.
	 * If the memory limit is higher than self::MEMORY_LIMIT
	 * this will do nothing.
	 *
	 * @uses wp_raise_memory_limit();
	 *
	 * @return string
	 */
	public function raise_memory_limit() {
		return self::MEMORY_LIMIT;
	}


	/**
	 * Quick and dirty update of entire blog
	 *
	 * Mostly used for unit testing and future WP-CLI command
	 *
	 * @param string $old_url - The old URL.
	 * @param string $new_url - The new URL.
	 *
	 * @since 5.0.1
	 *
	 * @return bool
	 */
	public function update( $old_url, $new_url ) {
		$db = Database::instance();
		$tables = $db->get_all_table_names();

		do_action( 'go-live-update-urls/core/before-update', $old_url, $new_url, $tables );

		return $db->update_the_database( $old_url, $new_url, $tables );
	}
}
