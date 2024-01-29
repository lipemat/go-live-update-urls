<?php
/**
 * Plugin Name: Go Live Update Urls
 * Plugin URI: https://onpointplugins.com/go-live-update-urls/
 * Description: Updates every URL on your site when going live or changing domains.
 * Author: OnPoint Plugins
 * Author URI: https://onpointplugins.com
 * Version: 6.7.2
 * Text Domain: go-live-update-urls
 * Domain Path: /languages/
 * Network: false
 * Requires at least: 6.0.0
 * Requires PHP: 7.2.0
 *
 * @package go-live-update-urls
 */

define( 'GO_LIVE_UPDATE_URLS_VERSION', '6.7.2' );
define( 'GO_LIVE_UPDATE_URLS_REQUIRED_PRO_VERSION', '6.10.3' );
define( 'GO_LIVE_UPDATE_URLS_URL', plugin_dir_url( __FILE__ ) );

use Go_Live_Update_Urls\Admin;
use Go_Live_Update_Urls\Core;
use Go_Live_Update_Urls\Database;
use Go_Live_Update_Urls\Serialized;
use Go_Live_Update_Urls\Skip_Rows;
use Go_Live_Update_Urls\Traits\Singleton;
use Go_Live_Update_Urls\Updaters\Repo;
use Go_Live_Update_Urls\Updaters\Updaters_Abstract;
use Go_Live_Update_Urls\Updaters\Url_Encoded;
use Go_Live_Update_Urls\Updates;

/**
 * Load the plugin
 *
 * @return void
 */
function go_live_update_urls_load() {
	load_plugin_textdomain( 'go-live-update-urls', false, 'go-live-update-urls/languages' );

	Admin::init();
	Core::init();

	if ( defined( 'GO_LIVE_UPDATE_URLS_PRO_VERSION' ) && version_compare( GO_LIVE_UPDATE_URLS_REQUIRED_PRO_VERSION, GO_LIVE_UPDATE_URLS_PRO_VERSION, '>' ) ) {
		add_action( 'all_admin_notices', 'go_live_update_urls_pro_plugin_notice' );
		remove_action( 'plugins_loaded', 'go_live_update_urls_pro_load', 9 );
	}
}

add_action( 'plugins_loaded', 'go_live_update_urls_load', 8 );

/**
 * Autoload classes from PSR4 src directory
 * Mirrored after Composer dump-autoload for performance
 *
 * @since 5.0.0
 *
 * @param string $class_name - class to load.
 *
 * @return void
 */
function go_live_update_urls_autoload( $class_name ) {
	$classes = [
		Admin::class             => 'Admin.php',
		Core::class              => 'Core.php',
		Database::class          => 'Database.php',
		Repo::class              => 'Updaters/Repo.php',
		Serialized::class        => 'Serialized.php',
		Singleton::class         => 'Traits/Singleton.php',
		Skip_Rows::class         => 'Skip_Rows.php',
		Updates::class           => 'Updates.php',
		Updaters_Abstract::class => 'Updaters/Updaters_Abstract.php',
		Url_Encoded::class       => 'Updaters/Url_Encoded.php',
	];
	if ( isset( $classes[ $class_name ] ) ) {
		require __DIR__ . '/src/' . $classes[ $class_name ];
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
			/* translators: Link to plugin {%1$s}[<a href="https://onpointplugins.com/product/go-live-update-urls-pro/">]{%2$s}[</a>] */
			printf( esc_html_x( 'Go Live Update Urls requires %1$sGo Live Update Urls PRO %3$s+%2$s. Please update or deactivate the PRO version.', '{<a>}{</a>}', 'go-live-update-urls' ), '<a target="_blank" href="https://onpointplugins.com/product/go-live-update-urls-pro/">', '</a>', esc_attr( GO_LIVE_UPDATE_URLS_REQUIRED_PRO_VERSION ) );
			?>
		</p>
	</div>
	<?php
}

/**
 * Sanitize a field in a way that PHPCS may be configured to honor
 * the function as a sanitization callback.
 *
 * Like `sanitize_text_field` except we don't remove
 * URL encoded characters and HTML tags.
 *
 * @since 6.7.2
 *
 * @param int|float|string $value - User provided value to sanitize.
 *
 * @return string
 */
function go_live_update_urls_sanitize_field( $value ): string {
	$filtered = wp_unslash( (string) $value );
	$filtered = wp_check_invalid_utf8( $filtered );
	$filtered = \preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
	return \trim( (string) $filtered );
}
