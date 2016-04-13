<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: https://matlipe.com/go-live-update-urls/
Description: Updates all the URLs in the database to point to the new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: https://matlipe.com/
Version: 3.1.2
Text Domain: go-live-update-urls
*/
define( 'GLUU_VERSION', "3.1.2" );

define( 'GLUU_VIEWS_DIR', plugin_dir_path(__FILE__) . 'views/' );
define( 'GLUU_URL_VIEWS_DIR', plugins_url('go-live-update-urls').'/views/' );

require( 'lib/GoLiveUpdateUrls.php' );

add_action('plugins_loaded', 'gluu_load' );
function gluu_load(){
	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	GoLiveUpdateUrls::init();

	global $GoLiveUpdateUrls; //backward compatibility
	$GoLiveUpdateUrls = GoLiveUpdateUrls::get_instance();
}