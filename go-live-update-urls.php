<?php
/*
Plugin Name: Go Live Update URLS
Plugin URI: http://matlipe.com/go-live-update-urls/
Description: This Plugin Updates all the URLs in the database to point to the new URL when making your site live or changing domains.
Author: Mat Lipe
Author URI: http://matlipe.com/
Version: 2.4.5
*/

define( 'GLUU_VIEWS_DIR', plugin_dir_path(__FILE__) . 'views/' );
define( 'GLUU_URL_VIEWS_DIR', plugins_url('go-live-update-urls').'/views/' );

require('lib/GoLiveUpdateUrls.php');


$GoLiveUpdateUrls = new GoLiveUpdateUrls();
