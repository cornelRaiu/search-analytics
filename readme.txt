=== WP Search Analytics ===
Contributors: cornel.raiu
Tags: search, analytics, statistics, history
Requires at least: 4.4.0
Tested up to: 6.1.1
Requires PHP: 5.6
Stable tag: 1.4.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WP Search Analytics will store and display the search terms used on your website. No third-party service is used!

== Description ==
WP Search Analytics will keep history of the search terms used by your users and group them in a set of statistics including the number of posts resulted from that search term.

It can easily aid you in finding what your users are really searching for on your website and make sure you provide exactly what they need.

Help and/or ideas are greatly appreciated! You can contribute to the GitHub repository: [WP Search Analytics](https://github.com/cornelRaiu/search-analytics)

**NOTE: WP Search Analytics stores all the statistics in your WordPress database. No info is sent to third-party services!**

= Features =
* Record all the search queries made using the **standard WordPress search form**.
* Exclude searches made by **users with certain user roles** or **with certain IP addresses**
* Exclude duplicate searches made **in certain conditions**
* Choose which user roles are allowed to see the statistics
* Filter statistics by **time periods, with/without results, strings/substrings**
* View each term **individual statistics**
* **Export data** in the current view to CSV
* Easily **delete certain search terms** from history
* Easily **erase all history from the database** in case a reset is needed
* Easily **erase history older than** in case a general cleanup is needed
* Dashboard widget for a quick glance over your last week's search stats
* **Multisite compatible**
* Country Geolocation
* Display search statistics on the front of your website using shortcodes

== Installation ==
WP Search Analytics can be installed via the WordPress Automatic Plugin Install page in the admin panel.
It can also be downloaded from the WordPress Plugin Directory and installed manually.

After the installation and activation is complete you should visit the plugin's settings page ( Settings -> MWT: Search Analytics ) to make sure it is properly configured for your needs.

== Screenshots ==

1. Main statistics view with time filters
2. Export data to CSV
3. Single term statistics view
4. Single term statistics view with results grouped by date
5. Settings page
6. Dashboard widget
7. Erase history section in the settings page

== Frequently Asked Questions ==

= The search history on my website is not being saved =

The plugin works with the standard WordPress search functionality by default. However, if you need to you can add custom search queries in the plugin's settings or, why not, programmatically add searches to the plugin's database tables to be displayed in the admin panel. For other requirements, please use the [Support Forum](https://wordpress.org/support/plugin/search-analytics) or open new issues on the GitHub repository: [WP Search Analytics](https://github.com/cornelRaiu/search-analytics).

= The shortcode is not displaying the stats in widgets

For enabling the shortcodes in widgets you need to add the following code in your child theme's `functions.php` file:

`add_filter( 'widget_text', 'do_shortcode' );`

= The history was not deleted when I deactivated the plugin =

Before uninstalling, you should go to the plugin's settings page and check the "Remove plugin tables on uninstall" setting. After doing that, deactivating the plugin should also remove all tables from the database.

= Where can I make feature requests or report bugs? =

You can use the [Support Forum](https://wordpress.org/support/plugin/search-analytics) or open new issues on the GitHub repository: [WP Search Analytics](https://github.com/cornelRaiu/search-analytics).

== Changelog ==
= 1.4.5 =
* Bugfix: Fix PHP Compatibility issue: PHP 5.6 - 7.2

= 1.4.4 =
* Bugfix: Date filters not working if the browser is set in a language different from English
* Feature: Add setting: "Show results dates as UTC", default: true
* Feature: Make dates in the results list show as UTC by default.
* Feature: Add 6 more filters and 2 actions for developers to be able to extend the plugin. An overview post will be published here: [WP Search Analytics: Filters Reference](https://www.cornelraiu.com/search-analytics-filters-reference/)
* Optimization: Security improvements and general code optimization
* Optimization: Updates to the settings page
* Deprecations: Deprecated the `mwt_wp_date_format_to_js_datepicker_format()` helper function

= 1.4.3 =
* Bugfix: Make sure that shortcode **mwtsa_display_latest_searches** displays unique terms
* Feature: Add more parameters to some filters. An overview post will be published here: [WP Search Analytics: Filters Reference](https://www.cornelraiu.com/search-analytics-filters-reference/)

= 1.4.2 Hotfix =
* Bugfix: fix default filters in the results view

= 1.4.1 =
* Feature: Add shortcode **mwtsa_display_latest_searches** for displaying the latest searches on the frontend of the website
* Feature: Add 3 more filters for developers to be able to extend the plugin. An overview post will be published here: [WP Search Analytics: Filters Reference](https://www.cornelraiu.com/search-analytics-filters-reference/)
* Optimization: Security improvements and general code optimization
* Others: Add link to the complete changelog

= 1.4.0 =
* Feature: Add REST API search support
* Feature: Add 9 filters for developers to be able to extend the plugin. An overview post will be published here: [WP Search Analytics: Filters Reference](https://www.cornelraiu.com/search-analytics-filters-reference/)
* Optimization: Add the search term to the **mwtsa_extra_exclude_conditions** filter
* Optimization: Check for minimum PHP and WP versions when activating the plugin
* Optimization: **Compatibility with WP versions up to 6.0.1**
* Optimization: **Compatibility with PHP v8.1**
* Optimization: Security improvements and general code optimization
* Bugfix: Fix styling on WP 4.4.0 - 4.9.20
* Bugfix: Fix broken settings page URL from the results page
* Others: Rename the plugin to "WP Search Analytics"

= 1.3.6 =
* Bugfix: Users can not see the statistics page in some cases. [Bug report](https://github.com/cornelRaiu/search-analytics/issues/3)
* Bugfix: Database error on term delete success page
* Optimization: **Compatibility with WP versions up to 5.8**
* Optimization: **Compatibility with PHP versions between 5.6 - 8.0**
* Optimization: Security improvements and general code optimization
* Optimization: Remove filters and groups on the term delete success page
* Others: Add more "Useful Links"
* Others: Add quick rate tool

= 1.3.5 =
* Bugfix: Fix dates filter not allowing you to select the current day in certain timezones
* Bugfix: Deleting multiple entries with the bulk action would trigger 2 notices
* Feature: Add support for WpForo
* Feature: Add **mwtsa_export_filename** filter to allow control over the filename generated when exporting data
* Feature: Add shortcode **mwtsa_display_search_stats** for displaying search statistics on the frontend of the website
* Optimization: Prepare the plugin for community translation
* Optimization: Security improvements and general code optimization

= 1.3.4 Hotfix =
* Bugfix: Fix fatal error for missing `wp_timezone()` in WP < 5.3.0

= 1.3.3 =
* Bugfix: Times displayed in UTC time instead of the website's timezone
* Feature: allow filtering searches by user
* Experimental Feature: prevent terms from being saved if they contain certain substrings
* Experimental Feature: allow the plugin to capture search strings from custom search parameters
* Optimization: hook **load_plugin_textdomain** on the **init** action instead of the **plugins_loaded** one
* Optimization: prefix helper functions **create_date_range** and **get_current_user_ip** with **mwt_** to avoid eventual naming conflicts

= 1.3.2 =
* Bugfix: "Only display the statistics and settings page for these user roles" not working correctly
* Bugfix: Fix missing script error if charts disabled
* Bugfix: Add prefix to the option setting group to prevent conflicts
* Bugfix: Database error if search-term URL param is empty
* Feature: Split the "Only display the statistics and settings page for these user roles" in 2 different settings
* Optimization: **compatibility with WP versions up to 5.4**
* Optimization: Add prefix to the option setting group to prevent conflicts
* Optimization: Review and patch the plugin from a security perspective
* Optimization: Made sure administrator display rights can not be taken away by making the field disabled

= 1.3.1 Hotfix =
* Bugfix: database not being updated correctly in case of plugin update. It only worked for manual plugin activation
* Bugfix: search country locked to Canada.

= 1.3.0 =
* Feature: add **save_search_term()** method to allow external search saving
* Feature: add **mwtsa_extra_exclude_conditions** filter to allow more control over the conditions in which a search is processed
* Feature: add **mwtsa_exclude_term** filter to allow more control over the conditions in which terms are saved
* Feature: save searches by user so the user can see his search history
* Feature: add country geolocation for the searches
* Feature: add more options for the chart
* Feature: add period comparison in the chart
* Optimization: **compatibility with version 5.2.2**
* Optimization: **Add multisite support**
* Optimization: Build separate methods for displaying charts to be able to easily integrate it in other views
* Optimization: Make chart include "today"
* Optimization: general code optimizations
* Optimization: general code optimizations

= 1.2.3 =
* Feature: add ability to delete all search history older than a selected number of days
* Feature: add setting: "Exclude searches from IPs list"
* Feature: add setting: "Only record searches with at least the number of characters"
* Feature: add "Ungroup" view for the list of terms for having a chronological data view
* Optimization: **compatibility with version 5.1**
* Optimization: change default sort to last search date
* Optimization: average number of results column to only 2 decimals
* Optimization: update the singleton pattern

= 1.2.2 =
* Bugfix: fix bug in the database version update
* Bugfix: "By Hour" group is not working correctly
* Bugfix: "Hide graphical charts" setting not working correctly
* Feature: Add a setting for using a cookie for previously logged in user for not counting searches made by users having a user role in the excluded roles list
* Feature: Add a setting for not counting duplicate searches over a period of time
* Feature: add role visibility for the dashboard widget
* Optimization: update the way Group By works on the term details view
* Optimization: use function_exists() and class_exists() for all function/class declarations
* Optimization: add javascript graph compatibility with older IE versions
* Optimization: show fewer points on the Y axis on the graphical chart if search count gets high

= 1.2.1 =
* Bugfix: fix "Unknown column 'average_posts' in 'where clause'" error on single term view ( introduced in v1.2.0 )
* Bugfix: remove bulk actions on single term view ( introduced in v1.2.0 )
* Bugfix: fix "Last 24 hours" time filter ( introduced in v1.2.0 )
* Feature: add admin dashboard widget with last week stats
* Feature: add possibility to group single term view results by day and hour
* Feature: add charts for graphical data representation
* Optimization: add link on searched term for faster navigation
* Optimization: make custom views and filters language variables ( introduced in v1.2.0 )
* Optimization: make all date and time columns use WP date and time format settings
* Optimization: code updates to better comply to the WP coding standards

= 1.2.0 =
* Bugfix: fix PHP notice on the settings page ( introduced in v1.0.3 )
* Bugfix: fix search resetting time and result filters ( introduced in v1.1.2 )
* Bugfix: fix notice "Undefined index: date_from" ( introduced in v1.1.2 )
* Bugfix: fix notice "Undefined index: date_to" ( introduced in v1.1.2 )
* Feature: ability to download history in a CSV file
* Feature: add single term view with detailed search historical stats
* Feature: ability to choose which user roles can see the search history stats
* Optimization: set table names in plugin constants for cleaner calls
* Optimization: move history data return from db function to independent static class.
* Optimization: change time filter default to "All Time"

= 1.1.3 =
* Bugfix: fix jquery.ui load over https ( introduced in v1.1.2 )

= 1.1.2 =
* Bugfix: make stats page full responsive ( introduced in v1.1 )
* Feature: add filters: all, only successful, only unsuccessful ( 0 posts ) results
* Feature: Time range filters
* Optimization: make bulk action "Delete" use language variable ( introduced in v1.1.1 )
* Optimization: add screen option to allow selection of number of results per page
* Optimization: last search date - sortable column
* Optimization: add "clear" option to the date pickers
* Optimization: add 2 months view on the calendars

= 1.1.1 =
* Bugfix: remove limit of results on the table ( introduced in v1.1 )
* Feature: ability to delete terms from the results
* Feature: bulk terms delete action
* Optimization: results per page reduced from 30 to 20

= 1.1 =
* Bugfix: exclude empty search strings ( introduced in v1.0 )
* Design: display tables using the Wordpress Admin Tables
* Feature: pagination for better data analysis
* Feature: custom sorting on all columns
* Feature: filter results by string

= 1.0.4 =
* fix a warning occurring in certain cases on search

= 1.0.3 =
* Add ability to delete all history
* Add ability to remove the tables on plugin deactivate
* Stats page restyling

= 1.0.2 =
* Update results sorting for better viewing the data
* Minimum styling on the stats page
* Add link to the settings page

= 1.0.1 =
* Fix deprecated notice

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 1.4.5 =
Fixed a PHP compatibility issue on PHP 5.6 - 7.2