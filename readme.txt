=== Plugin Name ===
Contributors: Mat Lipe, onpointplugins
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40onpointplugins%2ecom&lc=US&item_name=Go%20Live%20Update%20Urls&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: urls, launching, site changes, tools, domain, domains, domain changes, url changes
Requires at least: 4.8.0
Tested up to: 5.5.0
Requires PHP: 5.6.0
Stable tag: 6.1.0

== Description ==

Goes through entire site and replaces all instances of an old url with a new one. Used to change the domain of a site. Works on both multi-site and single site installs.

<strong>Check out <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">Go Live Update Urls PRO</a> for more features including support for tables created by plugins, the ability to test a URL before updating, update history, priority support, and so much more!</strong>

<blockquote><a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">Pro version 6.0.0</a> is now available with a greatly improved testing and updating experience!</blockquote>


<h3>Features</h3>
* Database table by table selection.
* Updates serialized data in core tables.
* Very easy to use admin page - which may be found under Tools.


<h3>Updates Entire Site including</h3>
* Posts
* Pages
* Image urls
* Excerpts
* Post Meta data
* Custom Post Types
* Widgets and widget data
* Options and settings
* And much more

<h3>Pro Features</h3>
* Updates database tables created by plugins without fear of breaking.
* Database tables are organized into simple intuitive sections.
* Updates serialized data across any table.
* Updates JSON data across any table.
* Improved admin page.
* Ability to test URL changes before running them.
* URL testing report is provided for peace of mind.
* Option to fix common mistakes automatically when entering a URL.
* iew and use history of your site's address.
* Predictive URLs automatically fill in the "Old URL" and "New URL.".
* Ability to choose between a full table or sections.
* WP-CLI support for updating URLs from the command line.
* Priority Support with access to members only support area.

<h3>Currently ships with the following languages</h3>
* English (US)
* French (fr_FR)
* German (de_DE)   
* Spanish (es_ES)

<h3>Developers</h3>
Developer docs may be found <a target="_blank" href="https://onpointplugins.com/go-live-update-urls/developer-docs-go-live-update-urls/">here</a>.

<h3>Troubleshooting</h3>
Troubleshooting information may be found <a target="_blank" href="https://onpointplugins.com/go-live-update-urls/go-live-update-urls-troubleshooting/">here</a>.

<h3>Contribute</h3>
Send pull requests via the <a href="https://github.com/lipemat/go-live-update-urls/">Github Repo</a>

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

Some plugins will store the data in the database serialized which does not allow for easy updating of the data. You may un-check tables used by such plugins to avoid breakage and then update the urls manually for those plugins. Currently the options, postmeta, usermeta, commentmeta, and sitemeta tables are serialization safe. The <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">Pro Version</a> supports updating these tables created by plugins.

= How do I know which tables I should not update? =

Most tables will be just fine to update. This plugin will tell you which ones not to update.
If you wish to try to update tables mentioned as not safe anyway, you may make a backup of your database, run this on all tables and if you run into trouble, restore your database, un-check tables in sections, and rerun this until you find the culprit. If you find you are running into issues with custom tables, you may want to check out the <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">Pro Version</a> of this plugin which works with any table.

== Screenshots ==

1. Typical settings page. The verbiage will change slightly depending on your database structure.

== Changelog ==
= 6.1.0 =
* Automatically exclude non text database columns.
* Support email addresses within serialized data.
* Greatly improve database update performance.
* Split database update steps into their own class.
* Support URL counting for upcoming <a href="https://onpointplugins.com/product/go-live-update-urls-pro/" target="_blank">PRO</a> enhancements.
* Tested to WordPress version 5.5.0

= 6.0.0 =
* Entirely new code structure.
* Removed all deprecated code and filters.
* Improved filter and action names.
* Improved performance.

= 5.3.0 =
* Display error message when no tables are selected during update.
* Fix bug when updating columns which are name the same as MySQL commands.
* Remove confusing '- Serialized' label from checkboxes list.
* Improve stability to modernize code some code.
* Introduce `go-live-update-urls/views/admin-tools-page/disable-description` filter.
* Cleanup some long deprecated code.

= 5.2.12 = 
* Support for WordPress version 5.3.0
* Officially drop support for PHP 5.4 in favor of 5.6.0

= 5.2.0 =
* Support URL Encoded URLS within serialized data 
* Support JSON Encoded URLS within serialized data

= 5.1.0 =
* Added new languages including French, German, and Spanish
* Support upcoming blogmeta table in WP 5.0.0+
* Support updating urlencoded urls
* Improved support for Visual Composer
* Add PHP composer support

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


== Upgrade Notice ==
= 6.0.1 =
Major version update. Not backward compatible with version 5 filters or code. Please remove any custom filters or extensions before updating.

= 5.0.6 =
Fixes bug with the submit button in some browsers

= 5.0.4 =
Fixes bug with the database not updating properly

= 5.0.1 =
For full functionality of PRO version 2.2.0

= 3.1 =
Upgrade to support WP 4.4's term meta.

