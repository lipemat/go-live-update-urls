<?php
/**
 * Plugin Name: Go Live Update Urls
 * Plugin URI: https://onpointplugins.com/go-live-update-urls/
 * Description: Updates all the URLs in the database to point to a new URL when making your site live or changing domains.
 * Author: OnPoint Plugins
 * Author URI: https://onpointplugins.com/
 * Version: 6.0.1
 * Text Domain: go-live-update-urls
 *
 * @package go-live-update-urls
 */

define( 'GO_LIVE_UPDATE_URLS_VERSION', '6.0.1' );
define( 'GO_LIVE_UPDATE_URLS_REQUIRED_PRO_VERSION', '6.0.0' );
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
	require __DIR__ . '/deprecated/Go_Live_Update_Urls_Database.php';

	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	Admin::init();
	Core::init();

	if ( defined( 'GO_LIVE_UPDATE_URLS_PRO_VERSION' ) && version_compare( GO_LIVE_UPDATE_URLS_REQUIRED_PRO_VERSION, GO_LIVE_UPDATE_URLS_PRO_VERSION, '>' ) ) {
		add_action( 'admin_notices', 'go_live_update_urls_pro_plugin_notice' );
	}
}

add_action( 'plugins_loaded', 'go_live_update_urls_load', 8 );

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

/**
 * Display a warning if we don't have the required PRO version installed
 *
 * @return void
 */
function go_live_update_urls_pro_plugin_notice() {
	?>
	<div id="message" class="error">
		<p>
			<?php
			/* translators: {%1$s}[<a>]{%2$s}[</a>] https://wordpress.org/plugins/go-live-update-urls/ */ //phpcs:disable
			printf( esc_html_x( 'Go Live Update Urls requires %1$sGo Live Update Urls PRO %3$s+%2$s. Please update or deactivate the PRO version.', '{<a>}{</a>}', 'go-live-update-urls' ), '<a target="_blank" href="https://onpointplugins.com/product/go-live-update-urls-pro/">', '</a>', esc_attr( GO_LIVE_UPDATE_URLS_REQUIRED_PRO_VERSION ) );
			?>
		</p>
	</div>
	<?php
}

