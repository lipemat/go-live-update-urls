<?php

use Go_Live_Update_Urls\Database;


//phpcs:disable

/**
 * @deprecated Here to prevent fatal when using old PRO version.
 */
class Go_Live_Update_Urls_Database extends Database {
	/**
	 * Go_Live_Update_Urls_Database constructor.
	 */
	public function __construct() {
		_deprecated_constructor( __CLASS__, '6.0.0', esc_html( Database::class ) );
	}


	/**
	 * @deprecated
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, '6.0.0', esc_html( Database::class ) );
		if ( ! is_a( static::$instance, __CLASS__ ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}
}
//phpcs:enable
