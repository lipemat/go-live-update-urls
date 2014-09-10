=== Plugin Name ===
Contributors: Mat Lipe
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40lipeimagination%2einfo&lc=US&item_name=Go%20Live%20Update%20Urls&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: Go Live, Urls, Domain Changes 
Requires at least: 3.1
Tested up to: 4.0.0
Stable tag: 2.4.5

== Description ==

Goes through entire site and replaces all instances of and old url with a new one. Used to change the domain of a site.

Works on both multisite and single site installs.

Some of the features this plugin offers:

* Database table by table selection in case of issues
* Supports seralized data in the options table 
* Very easy to use admin page - which may be found under Tools

Updates Entire Site including:

* Posts
* Pages
* Image urls
* Excerpts
* Post Meta data
* Custom Post Types
* Widgets and widget data
* Site settings
* And much more

The admin screen is extendable for developers familiar with using filters or template overrides. 

Additonal Seralized Safe tables may be adding using the 'gluu-seralized-tables' filter checkboxes to tap into this will be coming in a future release.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the `go-live-upload-urls` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Where do you use this plugin? =

Under the Tools menu in the dashboard there will be a "Go Live" link.

= Why does updating the domain break some plugins? =

Some plugins will store the data in the database seralized which does not allow for easy updating of the data. You may uncheck tables used by such plugins to avoid breakage and then update the urls manually for those plugins. There are future plans to allow for seralized safe updating via table by table selection but currently the only table that is safe is the options table

= How do I know which tables I should not update? =

Most tables will be just fine to update. You may make a backup of your database, run this on all tables and if you run into trouble, restore your database, uncheck tables in sections, and rerun this until you find the culpurit. If you find a particular table gives you trouble, let me know and I will add it to the urgent list for seralized safe updating.



== Screenshots ==

1. Screenshot of a tyical settings page. The verbage will change slightly depending on your database structure

== Changelog ==

= 2.4 =
* Added multisite support
* Added an uncheck all tables button
* Enhanced Security

= 2.3 =
* Added Post Meta to Seralized Safe to coincide with Simple Links Version 2.0

= 2.2 =
* Added Gravity Forms Support to Seralized Safe 
* Added a filter for additional seralized safe tables

= 2.0 =
* Made updating the options table seralized safe *
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

= 2.4 =
This Version works properly on mulitsite
Enhanced Security - you should probably update

= 1.5 -
This Version will automatically keep email addresses intact when switch to a subdomain like www

= 1.3 =
This Version will allow you to switch to www without having to run it twice

= 1.2.1 =
This Version will uncheck your options table by default for the wp_options as well as other table prefixes.

= 1.2 =
This Version will add the wp_options to the available tables and uncheck the table by default.

= 1.1 =
This version will remove the wp_options from the available tables.

