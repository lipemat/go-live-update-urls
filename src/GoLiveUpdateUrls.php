<?php

/**
 * deprecated 5.0.0
 *
 */
class GoLiveUpdateUrls {
	const TABLE_INPUT_NAME = Go_Live_Update_Urls_Admin_Page::TABLE_INPUT_NAME;

	protected function __construct() {
		_deprecated_constructor( 'GoLiveUpdateUrls', '5.0.0' );
	}


	/**
	 * @deprecated 5.0.0 in favor of Go_Live_Update_Urls_Database::get_all_tables()
	 * @see        Go_Live_Update_Urls_Database::get_all_table_names()
	 */
	public static function get_all_tables() {
		_deprecated_function( 'GoLiveUpdateUrls::get_all_tables', '5.0.0', 'Go_Live_Update_Urls_Database::get_all_tables' );

		return Go_Live_Update_Urls_Database::instance()->get_all_table_names();
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
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
