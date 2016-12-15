<?php

/**
 * Go_Live_Update_Urls_PHP_5_2_Mock_Class
 *
 * Goes in place of 5.3 classes via the container.
 * Uses for old PHP install to prevent errors but will
 * minimize functionality
 *
 *
 * @author  Mat Lipe
 * @since   12/15/2016
 *
 * @package Go_Live_Update_Urls
 */
class Go_Live_Update_Urls_PHP_5_2_Mock_Class {
	public function __call( $name, $args ){
		error_log( "Go Live Update Urls requires PHP 5.3+ to call $name properly" );
	}
}