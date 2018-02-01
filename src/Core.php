<?php

/**
 * Go_Live_Update_Urls_Core
 *
 * @author Mat Lipe
 * @since  5.0.0
 *
 */
class Go_Live_Update_Urls_Core {


	protected function hook() {

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
