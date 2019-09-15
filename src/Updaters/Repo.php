<?php

/**
 * Register
 *
 * @author  OnPoint Plugins
 * @since   5.0.0
 *
 * @package Go_Live_Update_Urls\Updates
 */
class Go_Live_Update_Urls__Updaters__Repo {
	/**
	 * Get all registered updaters by classname
	 * This list will grow over time as things are converted over
	 *
	 * @filter go_live_update_urls_updaters
	 *
	 * @return array
	 */
	public function get_updaters() {
		$updaters['json']        = 'Go_Live_Update_Urls__Updaters__JSON';
		$updaters['url-encoded'] = 'Go_Live_Update_Urls__Updaters__Url_Encoded';
		$updaters                = apply_filters( 'go_live_update_urls_updaters', $updaters );
		if ( ! is_array( $updaters ) ) {
			return array();
		}

		return $updaters;
	}



	//********** SINGLETON **********/


	/**
	 * Instance of this class for use as singleton
	 *
	 * @var self
	 */
	protected static $instance;


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
