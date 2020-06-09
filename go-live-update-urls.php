<?php
/**
 * Plugin Name: Go Live Update Urls
 * Plugin URI: https://onpointplugins.com/go-live-update-urls/
 * Description: Updates all the URLs in the database to point to a new URL when making your site live or changing domains.
 * Author: OnPoint Plugins
 * Author URI: https://onpointplugins.com/
 * Version: 5.3.0
 * Text Domain: go-live-update-urls
 *
 * @package go-live-update-urls
 */

define( 'GO_LIVE_UPDATE_URLS_VERSION', '5.3.0' );
define( 'GO_LIVE_UPDATE_URLS_URL', plugin_dir_url( __FILE__ ) );

use Go_Live_Update_Urls\Admin;
use Go_Live_Update_Urls\Core;
use Go_Live_Update_Urls\Database;
use Go_Live_Update_Urls\Serialized;
use Go_Live_Update_Urls\Updaters\Repo;
use Go_Live_Update_Urls\Traits\Singleton;
use Go_Live_Update_Urls\Updaters\Updaters_Abstract;
use Go_Live_Update_Urls\Updaters\Url_Encoded;

/**
 * Load the plugin
 *
 * @return void
 */
function go_live_update_urls_load() {
	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	Admin::init();
	Core::init();
}

/**
 * Autoload classes from PSR4 src directory
 * Mirrored after Composer dump-autoload for performance
 *
 * @param string $class - class to load.
 *
 * @since 5.0.0
 *
 * @return void
 */
function go_live_update_urls_autoload( $class ) {
	$classes = [
		Admin::class             => 'Admin.php',
		Core::class              => 'Core.php',
		Database::class          => 'Database.php',
		Repo::class              => 'Updaters/Repo.php',
		Serialized::class        => 'Serialized.php',
		Singleton::class         => 'Traits/Singleton.php',
		Updaters_Abstract::class => 'Updaters/Updaters_Abstract.php',
		Url_Encoded::class       => 'Updaters/Url_Encoded.php',
	];
	if ( isset( $classes[ $class ] ) ) {
		require __DIR__ . '/src/' . $classes[ $class ];
	}
}

spl_autoload_register( 'go_live_update_urls_autoload' );

add_action( 'plugins_loaded', 'go_live_update_urls_load' );
