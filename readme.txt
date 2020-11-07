=== Search Analytics ===
Contributors: cornel.raiu
Tags: search, analytics, statistics, history
Requires at least: 3.1.0
Tested up to: 5.4.2
Requires PHP: 5.6
Stable tag: 1.3.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Search Analytics will keep history of the search terms used by your users and group them in a set of statistics including the number of posts resulted from that search.

== Description ==
Search Analytics will keep history of the search terms used by your users and group them in a set of statistics including the number of posts resulted from that search term.

It can easily aid you in finding what your users are really searching for on your website and make sure you provide exactly what they need.

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

== Installation ==
Search Analytics can be installed via the WordPress Automatic Plugin Install page in the admin panel.
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

Please make sure you use the standard WordPress search functionality. In V1.3 there will be some functions added to aid other methods into inserting data in the history tables.

= The history was not deleted when I deactivated the plugin =

Before uninstalling, you should go to the plugin's settings page and check the "Remove plugin tables on uninstall" setting. After doing that, deactivating the plugin should also remove all tables from the database.

= Is there any development roadmap for the plugin? =

Yes. You can visit the plugin's [Trello Board](https://trello.com/b/MvIWInjW). This should give you an overview on my plans regarding this plugin.

= Where can I make feature requests? =

For now, you can use the [Support Forum](https://wordpress.org/support/plugin/search-analytics). I will be adding a feature request/voting system to my website soon so everything will be kept in there.

== Changelog ==
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

= 1.3.3 =
Two highly requested features have been added: "custom search parameters" and "ability to exclude terms if they contain certain substrings". It also comes with a few optimizations and bug fixes.