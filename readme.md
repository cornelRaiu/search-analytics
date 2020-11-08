## Important Note!

The plugin code in this repository may hold changes that were not yet released to the WordPress repository. So it may be unstable. 

If you want to use the latest stable version of the plugin please refer to the `Installation` section of this readme! 

## Description

Search Analytics is a WordPress plugin that will keep history of the search terms used by your users and group them in a set of statistics including the number of posts resulted from that search term.

## The `mwtsa_display_search_stats` shortcode

Starting in V1.3.5 there is a shortcode for displaying the search stats on the frontend of the website.


Example: 
`[mwtsa_display_search_stats unit="month" most_searched_count="10" most_searched_only_with_results="false"]`

Result: `The above shortcode will display the most searched terms over the past month with or without results and the last 5 currently logged in user searches that had at least 1 result.`

Here is the list of parameters the shortcode supports:

- unit                           
> supports: `'day', 'week', 'month', 'year'`
>
> **default**: `'week'`

- amount                          
> supports an integer value larger than 0
>
> **default:** `1`

- most_searched
> supports a booleanish value: `0, 1, 'false', 'true'`
>
> **default:** `'true'`

- most_searched_count
> supports an integer value larger than 0
>
> **default:** `5`

- most_searched_only_with_results
> supports a booleanish value: `0, 1, 'false', 'true'`
>
> **default:** `'true'`

- user_searches
> supports a booleanish value: `0, 1, 'false', 'true'`
>
> **default:** `'true'`

- user_searches_count
> supports an integer value larger than 0
>
> **default:** `5`

- user_searches_only_with_results
> supports a booleanish value: `0, 1, 'false', 'true'`
>
> **default:** `'true'`

## Installation

Search Analytics can be installed via the WordPress Automatic Plugin Install page in the admin panel.
It can also be downloaded from the [Search Analytics Plugin Page](https://wordpress.org/plugins/search-analytics) and installed manually.

After the installation and activation is complete you should visit the plugin's settings page ( Settings -> MWT: Search Analytics ) to make sure it is properly configured for your needs.

## Feature Requests

For now, you can use the [Support Forum](https://wordpress.org/support/plugin/search-analytics) and the issues section on this repository. I will be adding a feature request/voting system to the [www.cornelraiu.com plugin page](https://www.cornelraiu.com/mwt-search-analytics/) soon so everything will be kept in there.

Also, you can visit the plugin's [Trello Board](https://trello.com/b/MvIWInjW). This should give you an overview on my plans regarding this plugin.

Furthermore, in case you are a developer, you can submit pull requests for bug fixes or ideas you implemented. Thank you very much for doing it.
