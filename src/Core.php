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
		add_action( 'go-live-update-urls/database/after-update', [ $this, 'flush_caches' ] );
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
	 * Flush any known caches which are affected by updating the database.
	 *
	 * 1. WP core object cache.
	 * 2. Elementor CSS cache.
	 *
	 * @ticket #7751
	 *
	 * @see   \Elementor\Settings::update_css_print_method
	 *
	 * @since 6.2.1
	 */
	public function flush_caches() {
		// Special flush CSS cache for Elementor #7751.
		$method = get_option( 'elementor_css_print_method' );
		do_action( 'update_option_elementor_css_print_method', $method, $method, 'elementor_css_print_method' );

		wp_cache_flush();
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
	 * @return int[]
	 */
	public function update( $old_url, $new_url ) {
		$db = Database::instance();
		$tables = $db->get_all_table_names();

		do_action( 'go-live-update-urls/core/before-update', $old_url, $new_url, $tables );

		return $db->update_the_database( $old_url, $new_url, $tables );
	}
}
