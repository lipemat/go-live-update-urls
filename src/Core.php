<?php

namespace Go_Live_Update_Urls;

use Go_Live_Update_Urls\Traits\Singleton;

/**
 * Core functionality for the Go Live Update Urls plugin.
 *
 * @author OnPoint Plugins
 * @since  6.0.0
 */
class Core {
	use Singleton;

	const MEMORY_LIMIT = '256M';
	const PLUGIN_FILE  = 'go-live-update-urls/go-live-update-urls.php';


	/**
	 * Actions and filters.
	 */
	protected function hook() {
		add_action( 'go-live-update-urls/database/before-update', [ $this, 'raise_resource_limits' ], 0, 0 );
		add_action( 'go-live-update-urls/database/after-update', [ $this, 'flush_caches' ] );
		add_filter( 'go-live-update-urls/database/memory-limit_memory_limit', [ $this, 'raise_memory_limit' ], 0, 0 );
		add_filter( 'plugin_action_links_' . static::PLUGIN_FILE, [ $this, 'plugin_action_link' ] );
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
	 * Flush any known caches, which are affected by updating the database.
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
		// Special flushing of CSS cache for Elementor #7751.
		$method = get_option( 'elementor_css_print_method' );
		//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
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
		return static::MEMORY_LIMIT;
	}


	/**
	 * Like `sanitize_text_field` except we don't remove
	 * URL encoded characters and HTML tags.
	 *
	 * @param string $value - User provided value to sanitize.
	 *
	 * @since 6.3.4
	 *
	 * @return string
	 */
	public function sanitize_field( $value ) {
		$filtered = wp_unslash( (string) $value );
		$filtered = wp_check_invalid_utf8( $filtered );
		$filtered = \preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
		return \trim( $filtered );
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


	/**
	 * Display custom action links in plugins list.
	 *
	 * 1. Settings.
	 * 2. Go PRO.
	 *
	 * @param array $actions - Array of actions and their link.
	 *
	 * @return array
	 */
	public function plugin_action_link( array $actions ) {
		$actions['settings'] = sprintf( '<a href="%1$s">%2$s</a>', Admin::instance()->get_url(), __( 'Settings', 'go-live-update-urls' ) );
		if ( ! \defined( 'GO_LIVE_UPDATE_URLS_PRO_VERSION' ) ) {
			$actions['go-pro'] = sprintf( '<a href="%1$s" target="_blank" style="color:#3db634;font-weight:700;">%2$s</a>', 'https://onpointplugins.com/product/go-live-update-urls-pro/?utm_source=wp-plugins&utm_campaign=gopro&utm_medium=wp-dash', __( 'Go PRO', 'go-live-update-urls' ) );
		}
		return $actions;
	}
}
