<?php

namespace Go_Live_Update_Urls\Traits;

trait Singleton {

	/**
	 * Actions and filters, which are called during `init`.
	 */
	protected function hook() {
	}


	/**
	 * Instance of this class for use as singleton
	 *
	 * @var self|null
	 */
	protected static $instance;


	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		static::instance()->hook();
	}


	/**
	 * Get (and instantiate, if necessary) the instance of the
	 * class
	 *
	 * @static
	 * @return self
	 */
	public static function instance() {
		if ( ! is_a( static::$instance, __CLASS__ ) ) {
			static::$instance = new static(); // @phpstan-ignore-line
		}
		return static::$instance;
	}
}
