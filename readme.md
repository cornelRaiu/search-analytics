# Search Analytics for WP

![Search Analytics for WP](https://ps.w.org/search-analytics/assets/banner-1544x500.jpg)

[![Version](https://img.shields.io/wordpress/plugin/v/search-analytics)](https://wordpress.org/plugins/search-analytics/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/search-analytics)](https://wordpress.org/plugins/search-analytics/)
[![Rating](https://img.shields.io/wordpress/plugin/rating/search-analytics)](https://wordpress.org/support/plugin/search-analytics/reviews/)
[![Tested up to](https://img.shields.io/wordpress/plugin/tested/search-analytics)](https://wordpress.org/plugins/search-analytics/)
[![PHP](https://img.shields.io/wordpress/plugin/required-php/search-analytics)](https://wordpress.org/plugins/search-analytics/)

See what visitors search for on your WordPress site and which searches come up empty. Popular search terms and statistics, stored in your own database.

> [!NOTE]
> `master` can contain changes that aren't released yet. For a stable version, install the plugin from [WordPress.org](https://wordpress.org/plugins/search-analytics/) or download a [release](https://github.com/cornelRaiu/search-analytics/releases).

## What it does

Search Analytics for WP logs every search made through your site's search box, with the number of results it returned. The statistics screen shows which terms are popular and, more usefully, which ones **found nothing**: the content your visitors expect and can't find.

Everything stays in your WordPress database. There's no account, API key or outside dashboard.

## Features

- Records searches from the normal WordPress search, including theme search boxes and the **WooCommerce product search**.
- Also records wpForo forum searches and searches sent with custom URL parameters you set, REST API requests included. **AJAX live searches** take one line of code.
- A sortable table of search terms with the number of searches, average results and last search date.
- Filters for the last 24 hours, week or month, all time or any date range, and a **no-results filter**.
- Each term's history by date or hour, and a "No Group" view that lists every search.
- A daily chart compared with the previous period, and a **"When people search" chart** with the busiest days and times.
- A dashboard widget with the last 7 days: searches compared with the week before, trending terms, searches with no results, and your busiest day and time.
- **CSV export** of whatever view you're on.
- Keeps **your own searches** out: ignore user roles (even after they log out), IP addresses, repeat searches, very short terms and words you block.
- Leaves out **searches made by bots**, and junk searches that contain a link or HTML.
- Optional country and logged-in user for each search, both off by default.
- **Popular Searches** and **Latest Searches** blocks for showing what people search for on your site, with a live preview in the editor. Both are also available as shortcodes.
- Separate role access for the statistics and the settings, multisite support, and translation-ready.

## What's new in 1.6.1

- **Blocks.** Popular Searches and Latest Searches show the same lists as the two shortcodes, with the same settings and a live preview in the editor. See [Blocks and shortcodes](#blocks-and-shortcodes).
- **Links and labels.** The shortcodes and blocks can link each term to its search results, and the text above the lists can be left out.
- **No more bot searches.** Crawlers, link previews, monitoring tools and scripts are recognized by their user agent and skipped. Searches that contain a link or HTML, or that are longer than 100 characters, are skipped as junk. Both checks have a setting.
- **A redesigned dashboard widget.** The last 7 days compared with the week before, a bar per day, trending terms, top searches, searches with no results, and the busiest day and time.
- **"When people search".** A new chart on the statistics page shows the searches of the last 4 weeks by day of the week and 4-hour interval.
- **SQLite fix.** The time filters, the shortcodes and the dashboard widget failed on SQLite databases, such as the one WordPress Playground uses.
- **"Your Last Searches" fix.** The list showed the terms the visitor searched most often instead of the latest ones.

## What's new in 1.6.0

- **The charts work again.** They stopped loading in 1.5.0, and they now group searches by full date instead of by day of the month.
- **Safer data removal.** Search data is only removed when you delete the plugin with "Delete all search data and settings when the plugin is deleted" checked, never on deactivation. On multisite, deactivating on one site no longer touches the others.
- **ip2c.org** as an alternative to ip-api.com for the country lookup.
- **Flag emoji** instead of 249 bundled flag images (about 1 MB), with a small bundled font for browsers that can't draw them.
- **`mwtsa_process_search_term()`** for recording searches from your own code.
- **Faster and lighter:** database indexes, upgrades that run from the admin instead of during a visitor's search, country lookups cached per IP, and no tracking cookie on every page.
- **Security fixes** for CSV exports, the tracking cookie and role checks.
- **More accurate counts:** real "Last 24 hours" periods, and result pages and search feeds no longer count as new searches.

The full list is in the [1.6.0 release notes](https://github.com/cornelRaiu/search-analytics/releases/tag/v1.6.0) and the [changelog](https://wordpress.org/plugins/search-analytics/#developers).

## Installation

1. In your WordPress admin, go to **Plugins → Add New Plugin**, search for "Search Analytics for WP", then install and activate it.
2. Recording starts right away. Run a search on your site, then open **Search Analytics** in the admin menu.
3. Under **Search Analytics → Settings**, add your own role to **Ignore search queries for these user roles** so your test searches don't count.

Requires WordPress 4.7 or later and PHP 5.6 or later. The blocks need WordPress 5.6 or later.

To run the development version, clone this repository into `wp-content/plugins/search-analytics`. Keep that folder name, or WordPress will treat it as a different plugin from the one on WordPress.org.

## Privacy

- Searches are stored in your own database, without IP addresses.
- Nothing is sent anywhere unless you turn on **Save Search Country** (off by default). It sends the visitor's IP address to [ip-api.com](https://ip-api.com/) (HTTP, free for non-commercial use) or [ip2c.org](https://ip2c.org/) (HTTPS, IPv4 only), at most once a day per address. Private and reserved addresses are never sent.
- A cookie is only set if you use the repeat-search interval or the "even after the user has logged out" setting.

## Blocks and shortcodes

Two lists can be shown on your site, each as a block or as a shortcode. The blocks render through the shortcodes, so both give the same HTML and use the same filters.

In the block editor, add **Popular Searches** or **Latest Searches** from the Widgets category. The settings are in the block sidebar, and the editor shows a live preview. The blocks need WordPress 5.6 or later, and differ from the shortcodes in four ways:

- A new block links its terms to their search results. The shortcodes only do that with `link_terms="true"`.
- A block with nothing to list prints nothing. The shortcode prints an empty wrapper.
- A block has color, spacing and font size settings. These, and its additional CSS classes, go on a block wrapper around the shortcode's `div`.
- Pasting one of the shortcodes into the editor, or converting classic content to blocks, creates the matching block with the same settings. A Shortcode block can be transformed into it too.

### Latest Searches

Lists the most recent search terms.

- Block: `search-analytics/latest-searches`
- Shortcode: `[mwtsa_display_latest_searches]`

| Block attribute | Shortcode attribute | Default | Description |
| --- | --- | --- | --- |
| `unit` | `unit` | `week` | `minute`, `day`, `week` or `month`. Any other value removes the time limit. The block saves an empty value for "All time". |
| `amount` | `amount` | `1` | How many units to look back. |
| `count` | `count` | `10` | Number of terms. |
| `onlyWithResults` | `only_with_results` | `true` | Leave out terms that found nothing. |
| `label` | `label` | | Text above the list. Defaults to "Recently Searched Terms With Results", or "Recently Searched Terms". |
| `showLabel` | `show_label` | `true` | Show the text above the list. |
| `linkTerms` | `link_terms` | `false`, or `true` in the block | Link each term to its search results. |
| `countLinkClicks` | `count_link_clicks` | `false` | Record the searches opened from those links. Left off, they are not recorded, so the listed terms don't keep themselves on top. |
| | `wrapper_class` | `mwtsa-latest-searches` | CSS class of the wrapping `div`. |

### Popular Searches

Lists the most searched terms, plus the logged-in visitor's own recent searches when **Save Search By User** is on.

- Block: `search-analytics/popular-searches`
- Shortcode: `[mwtsa_display_search_stats]`

| Block attribute | Shortcode attribute | Default | Description |
| --- | --- | --- | --- |
| `unit` | `unit` | `week` | `minute`, `day`, `week` or `month`. Any other value removes the time limit. The block saves an empty value for "All time". |
| `amount` | `amount` | `1` | How many units to look back. |
| `mostSearched` | `most_searched` | `true` | Show the most searched terms. |
| `mostSearchedCount` | `most_searched_count` | `5` | Number of most searched terms. |
| `mostSearchedOnlyWithResults` | `most_searched_only_with_results` | `true` | Leave out terms that found nothing. |
| `mostSearchedLabel` | `most_searched_label` | | Text above the list. Defaults to "Most Searched Terms With Results", or "Most Searched Terms". |
| `userSearches` | `user_searches` | `true` | Show the visitor's own searches. |
| `userSearchesCount` | `user_searches_count` | `5` | Number of the visitor's searches. |
| `userSearchesOnlyWithResults` | `user_searches_only_with_results` | `true` | Leave out the visitor's searches that found nothing. |
| `userSearchesLabel` | `user_searches_label` | | Text above the list. Defaults to "Your Last Successful Searches", or "Your Last Searches". |
| `showLabel` | `show_label` | `true` | Show the text above each list. Without it, each list is a plain `ul` with the class `mwtsa-most-searched` or `mwtsa-user-searches`. |
| `linkTerms` | `link_terms` | `false`, or `true` in the block | Link each term to its search results. |
| `countLinkClicks` | `count_link_clicks` | `false` | Record the searches opened from those links. Left off, they are not recorded, so the listed terms don't keep themselves on top. |
| | `wrapper_class` | `mwtsa-search-stats` | CSS class of the wrapping `div`. |

### Usage

The true/false shortcode attributes accept `true`/`false`, `1`/`0`, `yes`/`no` and `on`/`off`.

The 10 most searched terms of the last 3 months, including the ones that found nothing, linked to their search results, as a shortcode:

```text
[mwtsa_display_search_stats unit="month" amount="3" most_searched_count="10" most_searched_only_with_results="false" user_searches="false" link_terms="true"]
```

The same list as block markup, for a template or a pattern:

```html
<!-- wp:search-analytics/popular-searches {"unit":"month","amount":3,"mostSearchedCount":10,"mostSearchedOnlyWithResults":false,"userSearches":false} /-->
```

With classic widgets, put the shortcode in a Text widget (WordPress 4.9 and later).

## For developers

### Record searches from your own code

`mwtsa_process_search_term( $term, $result_count )` records a search with the same checks as a normal one (ignored roles and IP addresses, minimum length, blocked words, repeat searches) and returns `true` when it was saved.

```php
if ( function_exists( 'mwtsa_process_search_term' ) ) {
    mwtsa_process_search_term( $term, $query->found_posts );
}
```

For a live search, call it once the visitor stops typing or picks a result. Called on every keystroke, it records "c", "co", "cou" and so on as separate terms.

### Filters

| Filter | Arguments | Use it to |
| --- | --- | --- |
| `mwtsa_do_not_save_search` | `$skip`, `$custom_search_value` | Decide whether a front-end request is skipped (`true` skips it). |
| `mwtsa_extra_exclude_conditions` | `$skip`, `$term` | Skip a search. Runs before the minimum length check. |
| `mwtsa_exclude_term` | `$skip`, `$term` | Skip a search. Runs after the minimum length check. |
| `mwtsa_is_bot` | `$is_bot`, `$user_agent` | Decide whether a request comes from a bot. Used when "Ignore searches made by bots" is on. |
| `mwtsa_is_junk_term` | `$is_junk`, `$term` | Decide whether a term is junk. Used when "Ignore junk searches" is on. |
| `mwtsa_result_count` | `$count`, `$term` | Change the result count saved for a front-end search. |
| `mwtsa_rest_api_do_not_save_search` | `$skip`, `$term` | Skip a REST API search. |
| `mwtsa_rest_api_result_count` | `$count`, `$term` | Provide the result count for a REST API search. With `0`, the plugin counts the results itself. |
| `mwtsa_rest_api_posts_count_query_args` | `$args` | Change the `WP_Query` used to count REST API results. |
| `mwtsa_wpforo_do_not_save_search` | `$skip`, `$term` | Skip a wpForo search. |
| `mwtsa_run_terms_history_data_query_args` | `$args` | Change the arguments of the statistics queries. |
| `mwtsa_run_terms_history_order_by` | `$order_by`, `$args` | Change the order of the statistics queries. |
| `mwtsa_export_filename` | `$filename` | Rename the CSV export. |
| `mwtsa_term_search_link` | `$link`, `$term`, `$counted` | Change the link of a term listed by the shortcodes and blocks. |
| `mwtsa_display_latest_searches_shortcode_list_item_output` | `$html`, `$atts`, `$term` | Change one term's HTML in `[mwtsa_display_latest_searches]`. |
| `mwtsa_display_latest_searches_shortcode_output` | `$html`, `$atts`, `$terms` | Change the whole output of `[mwtsa_display_latest_searches]`. |
| `mwtsa_display_search_stats_shortcode_most_searched_terms` | `$terms` | Supply your own most searched terms. |
| `mwtsa_display_search_stats_shortcode_user_searches_terms` | `$terms` | Supply your own list of the visitor's searches. |
| `mwtsa_display_search_stats_shortcode_list_item_output` | `$html`, `$atts`, `$list`, `$term` | Change one term's HTML in `[mwtsa_display_search_stats]`. `$list` is `most_searched` or `user_searches`. |
| `mwtsa_display_search_stats_shortcode_output` | `$html`, `$atts`, `$data` | Change the whole output of `[mwtsa_display_search_stats]`. |

The shortcode filters also apply to the Popular Searches and Latest Searches blocks.

The `mwtsa_stats_table_*` and `mwtsa_term_stats_table_*` filters are deprecated since 1.6.0.

### Actions

| Action | Arguments | Runs |
| --- | --- | --- |
| `mwtsa_after_term_save` | `$term`, `$term_id`, `$existed` | After a term is added, or its count goes up. |
| `mwtsa_after_history_term_save` | `$history_id`, `$term_id`, `$result_count`, `$country`, `$user_id` | After a search is saved. |

### Examples

Don't record searches that are email addresses:

```php
add_filter( 'mwtsa_exclude_term', function ( $skip, $term ) {
    return $skip || is_email( $term );
}, 10, 2 );
```

Send the links of the listed terms to the WooCommerce product search:

```php
add_filter( 'mwtsa_term_search_link', function ( $link ) {
    return add_query_arg( 'post_type', 'product', $link );
} );
```

## Support and contributing

- Questions and feature ideas: the [support forum](https://wordpress.org/support/plugin/search-analytics) or the [issues](https://github.com/cornelRaiu/search-analytics/issues) here.
- Pull requests are welcome.
- Security issues: please report them through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/search-analytics), not in a public issue.

## License

[GPLv3 or later](http://www.gnu.org/licenses/gpl-3.0.html).
