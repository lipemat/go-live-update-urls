<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: https://matlipe.com/go-live-update-urls/
Description: Updates all the URLs in the database to point to the new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: https://matlipe.com/
Version: 4.0.2
Text Domain: go-live-update-urls
*/
define( 'GLUU_VERSION', "4.0.2" );

define( 'GLUU_VIEWS_DIR', plugin_dir_path(__FILE__) . 'views/' );
define( 'GLUU_URL_VIEWS_DIR', plugins_url('go-live-update-urls').'/views/' );

require( plugin_dir_path( __FILE__ )  . '/src/GoLiveUpdateUrls.php' );

function go_live_update_urls_autoload( $class ){
	$parts = explode( '\\', $class );
	if( $parts[ 0 ] == 'Go_Live_Update_Urls' ){
		if( file_exists( dirname( __FILE__ ) . '/src/' . implode( DIRECTORY_SEPARATOR, $parts ) . '.php' ) ){
			require_once( dirname( __FILE__ ) . '/src/' . implode( DIRECTORY_SEPARATOR, $parts ) . '.php' );
		}
	}
}

spl_autoload_register( 'go_live_update_urls_autoload' );

add_action('plugins_loaded', 'gluu_load' );
function gluu_load(){
	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	GoLiveUpdateUrls::init();

	//backward compatibility
	global $GoLiveUpdateUrls;
	$GoLiveUpdateUrls = GoLiveUpdateUrls::get_instance();
}