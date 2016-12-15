<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: https://matlipe.com/go-live-update-urls/
Description: Updates all the URLs in the database to point to the new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: https://matlipe.com/
Version: 4.1.2
Text Domain: go-live-update-urls
*/
define( 'GLUU_VERSION', "4.1.2" );
define( 'GLUU_VIEWS_DIR', plugin_dir_path(__FILE__) . 'views/' );

function go_live_update_urls_autoload( $class ){
	$parts = explode( '\\', $class );
	if( $parts[ 0 ] == 'Go_Live_Update_Urls' ){
		if( file_exists( dirname( __FILE__ ) . '/src/' . implode( DIRECTORY_SEPARATOR, $parts ) . '.php' ) ){
			require( dirname( __FILE__ ) . '/src/' . implode( DIRECTORY_SEPARATOR, $parts ) . '.php' );
		}
	}
}
spl_autoload_register( 'go_live_update_urls_autoload' );

require( plugin_dir_path( __FILE__ )  . '/src/GoLiveUpdateUrls.php' );
require( plugin_dir_path( __FILE__ )  . '/src/Go_Live_Update_Urls_Container.php' );


function go_live_update_urls_load(){
	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	Go_Live_Update_Urls_Container::init();
	GoLiveUpdateUrls::init();
}

add_action('plugins_loaded', 'go_live_update_urls_load', 10 );
