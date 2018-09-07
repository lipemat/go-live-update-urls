<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: https://matlipe.com/go-live-update-urls/
Description: Updates all the URLs in the database to point to a new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: https://matlipe.com/
Version: 5.2.0
Text Domain: go-live-update-urls
*/

define( 'GO_LIVE_UPDATE_URLS_VERSION', '5.2.0' );

function go_live_update_urls_load() {
	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	Go_Live_Update_Urls_Admin_Page::init();
	Go_Live_Update_Urls_Core::init();
}

/**
 * Autoload classes from PSR4 src directory
 * Mirrored after Composer dump-autoload for performance
 *
 * @param string $class
 *
 * @since 5.0.0
 *
 * @return void
 */
function go_live_update_urls_autoload( $class ) {
	$classes = array(
		//core
		'Go_Live_Update_Urls_PHP_5_2_Mock_Class'     => 'PHP_5_2_Mock_Class.php',
		'Go_Live_Update_Urls_Admin_Page'             => 'Admin_Page.php',
		'Go_Live_Update_Urls_Core'                   => 'Core.php',
		'Go_Live_Update_Urls_Database'               => 'Database.php',
		'Go_Live_Update_Urls_Serialized'             => 'Serialized.php',
		//updaters
		'Go_Live_Update_Urls__Updaters__Abstract'    => 'Updaters/Abstract.php',
		'Go_Live_Update_Urls__Updaters__JSON'        => 'Updaters/JSON.php',
		'Go_Live_Update_Urls__Updaters__Repo'        => 'Updaters/Repo.php',
		'Go_Live_Update_Urls__Updaters__Url_Encoded' => 'Updaters/Url_Encoded.php',
	);
	if ( isset( $classes[ $class ] ) ) {
		require dirname( __FILE__ ) . '/src/' . $classes[ $class ];
	}
}

spl_autoload_register( 'go_live_update_urls_autoload' );

add_action( 'plugins_loaded', 'go_live_update_urls_load' );
