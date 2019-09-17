<?php

/**
 * Go_Live_Update_Urls_Core
 *
 * @author OnPoint Plugins
 * @since  5.0.0
 *
 */
class Go_Live_Update_Urls_Core {
	const MEMORY_LIMIT = '256M';


	protected function hook() {
		add_action( 'go-live-update-urls/database/before-update', array( $this, 'raise_resource_limits' ), 0, 0 );
		add_filter( 'go-live-update-urls/database/memory-limit_memory_limit', array(
			$this,
			'raise_memory_limit',
		), 0, 0 );
	}


	/**
	 * 1. Set time limit to unlimited
	 * 2. Set input time to unlimited
	 * 3. Set memory limit to context which will use our filter
	 *
	 * @see Go_Live_Update_Urls_Core::raise_memory_limit();
	 *
	 * @return void
	 */
	public function raise_resource_limits() {
		@set_time_limit( 0 );
		@ini_set( 'max_input_time', '-1' );

		wp_raise_memory_limit( 'go-live-update-urls/database/memory-limit' );
	}


	/**
	 * Raise the memory limit while the Database runs.
	 * If the memory limit is higher than self::MEMORY_LIMIT
	 * this will do nothing.
	 *
	 * @uses wp_raise_memory_limit();
	 *
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
	 * @param string $old_url
	 * @param string $new_url
	 *
	 * @since 5.0.1
	 *
	 * @return bool
	 */
	public function update( $old_url, $new_url ) {
		$db     = Go_Live_Update_Urls_Database::instance();
		$tables = $db->get_all_table_names();

		do_action( 'go-live-update-urls/core/before-update', $old_url, $new_url, $tables );

		return $db->update_the_database( $old_url, $new_url, $tables );
	}


	/**
	 * Get a view file from the theme first then this plugin
	 *
	 * @since 5.0.0
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function get_view_file( $file ) {
		$theme_file = locate_template( array( 'go-live-update-urls/' . $file ) );
		if ( empty( $theme_file ) ) {
			$theme_file = self::plugin_path( 'views/' . $file );
		}

		return $theme_file;

	}



	/**************** static ****************************/

	/**
	 * Used along with self::plugin_path() to return path to this plugins files
	 *
	 * @var string
	 */
	private static $plugin_path = false;

	/**
	 * To keep track of this plugins root dir
	 * Used along with self::plugin_url() to return url to plugin files
	 *
	 * @var string
	 */
	private static $plugin_url;


	/**
	 * Retrieve the path this plugins dir
	 *
	 * @param string [$append] - optional path file or name to add
	 *
	 * @return string
	 */
	public static function plugin_path( $append = '' ) {

		if ( ! self::$plugin_path ) {
			self::$plugin_path = trailingslashit( dirname( dirname( __FILE__ ) ) );
		}

		return self::$plugin_path . $append;
	}


	/**
	 * Retrieve the url this plugins dir
	 *
	 * @param string [$append] - optional path file or name to add
	 *
	 * @return string
	 */
	public static function plugin_url( $append = '' ) {

		if ( ! self::$plugin_url ) {
			self::$plugin_url = trailingslashit( plugins_url( basename( self::plugin_path() ), dirname( dirname( __FILE__ ) ) ) );
		}

		return self::$plugin_url . $append;
	}



	//********** SINGLETON **********/


	/**
	 * Instance of this class for use as singleton
	 *
	 * @var self
	 */
	protected static $instance;


	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		self::instance()->hook();
	}


	/**
	 * Get (and instantiate, if necessary) the instance of the
	 * class
	 *
	 * @static
	 * @return self
	 */
	public static function instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
