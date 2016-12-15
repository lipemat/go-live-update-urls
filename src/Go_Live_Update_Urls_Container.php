<?php


/**
 * Go_Live_Update_Urls_Container
 *
 * Container to store class instances from PHP 5.2 VS 5.3
 * Users running 5.2 will receive much less functionality but
 * at least the site won't crash.
 *
 * @author Mat Lipe
 * @since  12/15/2016
 *
 */
class Go_Live_Update_Urls_Container {
	private $php_5_3 = false;


	public function check_php_version( $version = null ){
		if( !$version ){
			$version = phpversion();
		}
		$this->php_5_3 = version_compare( $version, '5.3', '>=' );
		if( !$this->php_5_3 ){
			add_action( 'gluu_before_checkboxes', array( $this, 'php_version_notice' ) );
		}
	}


	public function php_version_notice(){
		?>
		<div id="error" class="error notice notice-success is-dismissible">
			<p>
				<?php _e( 'This plugin requires PHP version 5.3+ for all functionality to work. Please update your PHP version for full support.', 'go-live-update-urls' ); ?>
				<?php printf( _x( 'You are running version %s', '{php version}', 'go-live-update-urls' ), phpversion() ); ?>
			</p>
		</div>
		<?php
	}


	/**
	 *
	 *
	 * @return Go_Live_Update_Urls\Updaters\Register|Go_Live_Update_Urls_PHP_5_2_Mock_Class
	 */
	public function get_updaters(){
		if( $this->php_5_3 ){
		    //must use string because PHP 5.2 won't parse
			return call_user_func( array( '\Go_Live_Update_Urls\Updaters\Register', 'get_instance' ) );
		} else {
			require_once( dirname( __FILE__ ) . '/' . 'Go_Live_Update_Urls_PHP_5_2_Mock_Class.php' );
			return new Go_Live_Update_Urls_PHP_5_2_Mock_Class();
		}

	}


	//********** SINGLETON FUNCTIONS **********/

	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;


	/**
	 * Create the instance of the class
	 *
	 * @static
	 * @return void
	 */
	public static function init(){
		$instance = self::get_instance();
		$instance->check_php_version();
	}


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