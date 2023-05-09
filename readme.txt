=== Plugin Name ===
Contributors: Mat Lipe, onpointplugins
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40onpointplugins%2ecom&lc=US&item_name=Go%20Live%20Update%20Urls&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: domain, url, replace, database, tools, launching, domains, domain changes, url changes
Requires at least: 5.4.0
Tested up to: 6.2.0
Requires PHP: 7.0.0
Stable tag: 6.5.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

<h3>Change the domain on your site with one click.</h3>

Goes through entire site and replaces all instances of an old URL with a new one. Used most often when changing the domain of your site.

<strong>Check out <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">Go Live Update Urls PRO</a> for more features including support for tables created by plugins, the ability to test a URL before updating, update history, real time reporting, priority support, and so much more!</strong>

<blockquote><a href=" https://onpointplugins.com/go-live-update-urls/go-live-update-urls-pro-usage/#url-counting" target="_blank">PRO version 6.8.0</a> is now available with real time reporting of urls, which will be and were updated!</blockquote>


<h3>Features</h3>
* Database table by table selection.
* Updates serialized data in core tables.
* Easy to use admin page - which may be found under Tools.
* Works on both multisite and single site installs.


<h3>Updates Entire Site including</h3>
* Posts
* Pages
* Image URLs
* Excerpts
* Post meta data
* Custom post types
* Widgets and widget data
* Options and settings
* And much more

<h3>PRO Features</h3>
* Updates database tables created by plugins without fear of breaking.
* Database tables are organized into simple intuitive sections.
* Ability to choose between a full table or sections.
* Updates serialized data across any table.
* Updates encoded URL across any table.
* Updates JSON data across any table.
* Ability to test URL changes before running them.
* URL testing report is provided for peace of mind.
* Option to fix common mistakes automatically when entering a URL.
* View and use history of your site's address.
* Predictive URLs automatically fill in the "Old URL" and "New URL.".
* Real time reporting of count and location of urls which will be updated. **NEW**
* Report of count and location of urls which were updated. **NEW**
* WP-CLI support for updating URLs from the command line.
* Priority Support with access to members only support area.

<h3>Domain Update Process</h3>
Full step-by-step instructions for a changing a site's domain <a target="_blank" href="https://onpointplugins.com/how-to-change-your-domain-name-on-wordpress/">may be found here</a>.

<h3>Included Language Translations</h3>
* English (en_US).
* French (fr_FR).
* German (de_DE).
* Spanish (es_ES).

<h3>Developers</h3>
Developer docs <a target="_blank" href="https://onpointplugins.com/go-live-update-urls/developer-docs-go-live-update-urls/">may be found here</a>.

<h3>Troubleshooting</h3>
Troubleshooting information <a target="_blank" href="https://onpointplugins.com/go-live-update-urls/go-live-update-urls-troubleshooting/">may be found here</a>.

<h3>Contribute</h3>
Send pull requests via the <a href="https://github.com/lipemat/go-live-update-urls/">Github Repo</a>

== Installation ==

Use the standard WordPress plugins search and installer.
Activate the plugin.
Use the "Go Live" page, located under the Tools menu, in the WordPress admin.

Manual Installation

1. Upload the `go-live-upload-urls` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.


== Frequently Asked Questions ==

= Where do you use this plugin? =

Under the Tools menu in the dashboard there will be a "Go Live" link.

= Why does updating the domain break some plugins? =

Some plugins will store the serialized or JSON data in the database which does not allow for easy updating of the data. You may un-check tables used by such plugins and then update the urls manually for those plugins. Currently, the options, postmeta, usermeta, commentmeta, blogmeta, and sitemeta tables are serialization safe. The <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">PRO Version</a> supports updating all tables created by plugins, including ones with serialized or JSON data.

= How do I know which tables I should not update? =

Most tables will be just fine to update. This plugin will tell you which tables not to update.
If you wish to try to update tables mentioned as "not safe" anyway, you may:
1. Make a backup of your database.
2. Run the update with all tables checked.

If you run into trouble:
1. Restore your database.
2. Un-check tables in sections.
3. Re-run this until you find the culprit.

If you find you are running into issues with custom tables, you may want to check out the <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">PRO Version</a> of this plugin, which works with any table.

== Screenshots ==

1. Tools page. The list of tables will changed depending on your database structure.
2. Successfully updated urls.
3. Update prevented due to incomplete data entered.

== Changelog ==
= 6.5.2 =
* Modernized the tools page JavaScript.
* Improved extendability by removing all `private` access modifiers.
* Improved extendability by converting all `self` to `static`.
* Improved PHPCS scanning.
* Fully support PHP 8.1.
* Tested to WordPress Core 6.2.0.

= 6.5.1 =
* Fixed handling of row skipping for PHP 7.0.

= 6.5.0 =
* Gracefully handle missing PHP classes in serialized data.
* Introduced `Skip_Rows` class for programmatically skipping database row updates.
* Introduced `go-live-update-urls-pro/database/supports-skipping` filter to disable row skipping.
* Tested to WordPress Core version 6.1.1.

= 6.4.1 =
* Improved readme.
* Added GPL license to plugin.
* Tested to WordPress core version 6.1.0.

= 6.4.0 =
* Dropped support for PHP 5.6 in favor of PHP 7.0.
* Required PRO version 6.8.0+.

= 6.3.9 =
* Tested to WordPress 6.0.1.
* Mentioned updating encoded URL in readme.

= 6.3.8 =
* Improved translations.
* Fix typo in the readme.
* Required PRO version 6.6.0+.
* Tested to WordPress 6.0.0.

= 6.3.7 =
* Added "Settings" link to plugin actions.
* Introduced `Admin::get_url` method for retrieving URL or tools page.
* Fixed title of tools page in browser tab.

= 6.3.6 =
* Improved internal URL utm structure.
* Tested to WordPress 5.9.0.

= 6.3.5 =
* Tested to WordPress 5.8.3.
* Fix issue with tables showing from other sites on multisite.

= 6.3.4 =
* Support updating URL with URL encoded characters.
* Tested to WordPress 5.8.2.

= 6.3.3 =
* Improved capitalization across plugin verbiage.
* Improved translations.
* Tested to WordPress 5.8.1.

= 6.3.2 =
* Improved sanitization of table names.
* Fully support PHP 8.
* Tested to WordPress 5.7.2.

= 6.3.1 =
* Tested to WordPress 5.6.1.
* Improved translation process and documentation.
* Improved responsiveness of admin page.

= 6.3.0 =
* Improved readme.
* Improved plugin headers.
* Required WordPress version 5.2.0+.

= 6.2.2 =
* Pass option value when flushing Elementor cache to prevent edge case conflicts.
* Improved counting of urls across subdomains.
* Improved counting of urls across serialized data. 
* Support replacing non subdomain values which duplicate because the old URL exists within the new URL.
* Introduced `go-live-update-urls/database/after-counting` action.
* Introduced `go-live-update-urls/database/before-counting` action

= 6.2.1 =
* Automatically flush Elementor's CSS cache during updates.
* Update screenshots and captions.
* Introduced new `go-live-update-urls-pro/admin/use-default-inputs` filter.
* Add CSS classes to form elements on tools page.
* Fix spacing of banners on tools page.
* Improved PHPCS implementation.

= 6.2.0 = 
* Redesign tools page for a modern block look.
* Improved various verbiage.
* Update all translations.
* More gracefully handle version conflicts with PRO.
* Support for PRO version 6.2.0.

= 6.1.4 = 
* Support updating `registration_log` and `signups` tables.
* Make `get_doubled_up_subdomain` method public.

= 6.1.3 = 
* Support WordPress version 5.5.1

= 6.1.2 =
* Improved admin form and selectors.
* Fix filter name for `go-live-update-urls/database/column-types`.
* Improved FAQs.
* Support for PRO version 6.1.0.

= 6.1.0 =
* Automatically exclude non text database columns.
* Support email addresses within serialized data.
* Greatly improved database update performance.
* Split database update steps into their own class.
* Support URL counting for upcoming <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">PRO</a> enhancements.
* Tested to WordPress version 5.5.0

= 6.0.1 =
* Improved compatibility with very old versions of PRO.
* Improved the readme.
* Add links for the documentation and troubleshooting.

= 6.0.0 =
* Entirely new code structure.
* Removed all deprecated code and filters.
* Improved filter and action names.
* Improved performance.

= 5.3.0 =
* Display error message when no tables are selected during update.
* Fix bug when updating columns which are name the same as MySQL commands.
* Remove confusing '- Serialized' label from checkboxes list.
* Improved stability to modernize code some code.
* Introduced `go-live-update-urls/views/admin-tools-page/disable-description` filter.
* Cleanup some long deprecated code.

= 5.2.12 = 
* Support for WordPress version 5.3.0
* Officially drop support for PHP 5.4 in favor of 5.6.0

= 5.2.0 =
* Support URL Encoded Urls within serialized data 
* Support JSON Encoded Urls within serialized data

= 5.1.0 =
* Added new languages including French, German, and Spanish
* Support upcoming blogmeta table in WP 5.0.0+
* Support updating urlencoded urls
* Improved support for Visual Composer
* Add PHP composer support

= 5.0.0 =
* Bring back PHP 5.2 support
* Restructure entire codebase
* Greatly improved security
* Improved performance
* UI improvements
* Use strict WP coding standards

== Upgrade Notice ==
= 6.1.2 =
Update to support PRO version 6.1.0.

= 6.0.1 =
Major version update. Not backward compatible with version 5 filters or code. Please remove any custom filters or extensions before updating.

= 5.0.6 =
Fixes bug with the submit button in some browsers

= 5.0.4 =
Fixes bug with the database not updating properly

= 5.0.1 =
For full functionality of PRO version 2.2.0
