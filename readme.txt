=== Plugin Name ===
Contributors: Mat Lipe
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40matlipe%2ecom&lc=US&item_name=Go%20Live%20Update%20Urls&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: Go Live, Urls, Domain Changes 
Requires at least: 4.5.0
Tested up to: 4.9.1
Requires PHP: 5.2.4
Stable tag: 5.0.0

== Description ==

Goes through entire site and replaces all instances of an old url with a new one. Used to change the domain of a site. Works on both multi-site and single site installs.

<strong>Check out <a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">Go Live Update Urls Pro</a> for more features including priority support, the ability to test a URL before running, updating of tables created by plugins, and so much more!</strong>

<blockquote><a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">Pro version 2.0.3</a> just dropped with lots of great new stuff!</blockquote>


<h4>Features</h4>
* Database table by table selection.
* Updates serialized data in core tables.
* Very easy to use admin page - which may be found under Tools.


<h4>Updates Entire Site including</h4>
* Posts
* Pages
* Image urls
* Excerpts
* Post Meta data
* Custom Post Types
* Widgets and widget data
* Options and settings
* And much more

<h4>Pro Features</h4>
* Updates database tables created by plugins without fear of issues.
* Database tables are organized into understandable sections.
* Updates serialized data across any table.
* Priority support.
* Improved admin page.
* Ability to test URL changes before running them. **NEW**
* URL testing report is provided for peace of mind. **NEW**
* Optionally fix common mistakes when entering a URL automatically. **NEW**

<h4>Contribute</h4>
Send pull requests via the <a href="https://github.com/lipemat/go-live-update-urls/">Github Repo</a>

== Installation ==

Use the standard WordPress plugins search and installer.
Activate the plugin.
Use the plugin under the Tools menu in the WordPress admin

Manual Installation

1. Upload the `go-live-upload-urls` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Where do you use this plugin? =

Under the Tools menu in the dashboard there will be a "Go Live" link.

= Why does updating the domain break some plugins? =

Some plugins will store the data in the database serialized which does not allow for easy updating of the data. You may un-check tables used by such plugins to avoid breakage and then update the urls manually for those plugins. Currently the options, postmeta, usermeta, commentmeta, and sitemeta tables are serialization safe. The <a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">Pro Version</a> supports updating these tables created by plugins.

= How do I know which tables I should not update? =

Most tables will be just fine to update. This plugin will tell you which ones not to update.
If you wish to try to update tables mentioned as not safe anyway, you may make a backup of your database, run this on all tables and if you run into trouble, restore your database, un-check tables in sections, and rerun this until you find the culprit. If you find you are running into issues often with custom table you may want to check out the <a href="https://matlipe.com/product/go-live-update-urls-pro/" target="_blank">Pro Version</a> of this plugin which works with any table.

== Screenshots ==

1. Typical settings page. The verbiage will change slightly depending on your database structure

== Changelog ==
= 5.0.0 =
* Bring back PHP 5.2 support
* Restructure entire codebase
* Greatly improve security
* Improve performance
* UI improvements
* Use strict WP coding standards

= 4.1.0 =
* Drop PHP 5.2 support in favor of PHP 5.3
* Support updating JSON urls
* Support Revolution Sliders
* Add custom updaters support

= 4.0.0 =
* Restructure admin page to separate WP Core from custom tables
* Remove custom styles in admin
* Improved js structure
* Improved actions and filters
* Improved verbiage with admin

= 3.1 =
* Add support for serialized term meta

= 3.0 =
* Greatly improve security
* Improve code organization
* Remove misleading UI messages
* Clears cache when finished

= 2.4 =
* Added multi-site support
* Added an un-check all tables button
* Enhanced Security

= 2.3 =
* Added Post Meta to Serialized Safe to coincide with Simple Links Version 2.0

= 2.2 =
* Added Gravity Forms Support to Serialized Safe
* Added a filter for additional serialized safe tables

= 2.0 =
* Made updating the options table serialized safe *
* Add extending ability of views and css *
* Moved the Admin page to the Tools Section *
* Improved the structure to allow for future changes *

= 1.5 =
* Added support for automatically keeping email addresses intact when switching to a subdomain like www

= 1.3 =
* Added support for adding subdomains like www

= 1.2.1 =
* Added support for other prefixes besides wp_

= 1.2 =
* Added the wp_options to the available tables to be updated and unchecked the table by default.

= 1.1 =
* Removed the wp-options table from the tables to be updated.

== Upgrade Notice ==
= 3.1 =
Upgrade to support WP 4.4's term meta.

= 2.4 =
This Version works properly on mulit-site
Enhanced Security - you should probably update

= 1.5 -
This Version will automatically keep email addresses intact when switch to a sub-domain like www

= 1.3 =
This Version will allow you to switch to www without having to run it twice

= 1.2.1 =
This Version will un-check your options table by default for the wp_options as well as other table prefixes.

= 1.2 =
This Version will add the wp_options to the available tables and uncheck the table by default.

= 1.1 =
This version will remove the wp_options from the available tables.

