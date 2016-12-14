<?php

namespace Go_Live_Update_Urls\Updaters;

/**
 * Register
 *
 * @author  Mat Lipe
 * @since   12/13/2016
 *
 * @package Go_Live_Update_Urls\Updates
 */
class Register {
	/**
	 * Get all registered updaters by classname
	 * This list will grow over time as things are converted over
	 *
	 * @filter go_live_update_urls_updaters
	 *
	 * @return array
	 */
	public function get_updaters(){
		$updaters[ 'json' ] = '\Go_Live_Update_Urls\Updaters\JSON';
		$updaters           = apply_filters( 'go_live_update_urls_updaters', $updaters );
		if( !is_array( $updaters ) ){
			return array();
		}

		return $updaters;
	}

	//********** SINGLETON FUNCTIONS **********/

	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;

	/**
	 * Get (and instantiate, if necessary) the instance of the
	 * class
	 *
	 * @static
	 * @return self
	 */
	public static function get_instance(){
		if( !is_a( self::$instance, __CLASS__ ) ){
			self::$instance = new self();
		}

		return self::$instance;
	}
}