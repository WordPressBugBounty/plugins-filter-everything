=== Filter Everything&nbsp;— WordPress & WooCommerce Filters ===
Contributors: stepasyuk
Tags: woocommerce product filter, woocommerce filter, product filter, post filter, ajax filter
Stable tag: 1.9.2
Requires at least: 4.6
Tested up to: 6.9.4
Requires PHP: 5.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Filter anything on WordPress & WooCommerce. The most flexible filtering plugin.

== Description ==
*Help visitors quickly find the content they need on your WordPress/WooCommerce site.*
**Filter Everything** is a WordPress filtering plugin that **_provides everything needed_** for filtering.

It filters any content by virtually any criteria and includes all the options and features needed to build a filtering system.
https://www.youtube.com/watch?v=g1_qlJvNdsg

#### Complete filtering solution
The plugin includes _highly configurable filters_ and also supports: sorting, keyword search, mobile-friendly filters, multiple filter layouts, different submission modes, widgets, AJAX, shortcodes, color swatches and more.
_— Everything you need to build a complete filtering system._

#### Filters everything by anything
Allows you to filter any type of content.
Posts • WooCommerce products • listings • events • portfolios • any custom post type.
Filtering criteria can be virtually anything.
Price • brand • category • attributes • color • size • weight — virtually any other data in your content.
_— Maximum flexibility._

#### Works with your existing content and setup
The plugin integrates easily into your existing website structure and works with standard WordPress queries, taxonomies, and custom fields (including ACF, Meta Box fields), without requiring additional tables, indexing systems, or duplicate data.
_— No need to restructure your content. Just install and use it._

#### Compatible. Fast. Supported
It works seamlessly with your theme, page builder, and plugins, and delivers fast performance thanks to its WordPress-standards-based architecture.
Actively maintained, regularly updated, and continuously improved by the team.
_— Built for reliability._

## Core Features at a Glance
-   **Filter any content**
Works with WooCommerce products, posts, and any custom post types on your website.
-   **All essential filtering options included**
25+ built-in filtering options designed to cover virtually any filtering scenario.
-   **Filter by virtually any criteria**
Filter content by price, brand, color, category, size, weight, or any other criteria based on the data stored in taxonomies or custom fields.
-   **Works on any page**
Each section of your website can have its own set of filters relevant to its content.
-   **Widgets for page builders**
Built-in Filters, Chips, and Sorting widgets for Gutenberg, Elementor, Divi, Breakdance, Beaver Builder, and other page builders.
-   **Flexible filter layouts and display options**
Use checkboxes, radio buttons, dropdowns, labels, color swatches, rating stars, numeric ranges, or date ranges, and display filters as horizontal toolbars or vertical panels.
-   **Flexible filtering modes**
Step-by-step filtering, auto-submission, or selecting multiple filters and applying them manually.
-   **Sorting and keyword search**
Allow visitors to sort and search within filtered results.
-   **Developer-friendly and extensible**
Customize and extend plugin behavior using WordPress actions and filters.
-   **Mobile-friendly**
Works out of the box on mobile devices.

_— And many other built-in capabilities._

## Filter Everything PRO
The plugin is also available in a PRO version that significantly expands filtering capabilities:

-   Support for filtering **any custom WP_Query**
-   **∞ Unlimited Filter Sets**
-   **SEO tools** that help bring additional organic traffic to your website
-   **Smart filtering** for WooCommerce variable and out-of-stock products
-   **Advanced mobile features**
-   **Import and export** of filters, SEO Rules, and settings
-   **Duplicate Filter Sets** in one click
-   **Priority support**

_— And many other powerful features available in Filter Everything PRO._

## Why use filters on your website?
Filters help visitors quickly _find the content they need_ in just a few clicks, especially on websites that contain large amounts of content.

This improves navigation, reduces bounce rates, saves visitors’ time, and creates a better overall user experience. For high-traffic websites, efficient filtering can also help reduce server load.

== Frequently Asked Questions ==

= How do I get support? =

You can try to find a solution to your problem in the plugin [documentation](https://filtereverything.pro/resources/&utm_source=repository) or ask your question on the support [forum](https://wordpress.org/support/plugin/filter-everything/). PRO version users can get more info about support [here](https://filtereverything.pro/support/).

== Installation ==

Uploading via WordPress dashboard
1. From the WordPress dashboard visit Plugins > Add New > Upload Plugin
2. Choose plugin zip file and upload it
3. Install and Activate the plugin
4. After installation, you will find a new menu item “Filters”
5. Read the documentation to [get started](https://filtereverything.pro/resources/quickstart/)

Uploading via FTP
1. Download the Filter Everything plugin zip file
2. Extract zip file and upload “filter-everything” folder to the /wp-content/plugins/ directory
3. Activate the plugin through the “Plugins” menu in WordPress
4. After installation, you will find a new menu item “Filters”
5. Read the documentation to [get started](https://filtereverything.pro/resources/quickstart/)

== Screenshots ==

1. Everything needed for filtering
2. All essential filter views
3. Built-in, user-friendly filter widget for mobile devices
4. All the necessary filter options
5. Individual filters for any post type

== Changelog ==

= 1.9.2 =
*Release Date - 18 March 2026*
* Dev   - Added the ability to use filters on Singular pages and filter native WordPress queries, including the Main WP Query and posts displayed with Gutenberg
* Dev   - Added Filter, Chips, and Sorting widgets for page builders: Elementor, Divi, Bricks Builder, Breakdance, Beaver Builder, and Gutenberg
* Dev   - Added the ability to create date filters for both regular post dates and dates stored in custom fields, including fields created with ACF
* Dev   - Added the ability to create a large number of filters in a Filter Set, such as 100 or more
* Dev   - Added a 'Horizontal view' checkbox to Filter Sets
* Dev   - Added functionality for automatic filter creation based on popular criteria
* Dev   - Added improved styles for the Filters widget
* Tweak - Added the ability to reset values in numeric fields
* Tweak - Added an icon to the 'Where to filter?' field for quick preview of the page where the Filter Set works
* Tweak - Added suggestions for custom field filters, along with autocomplete while typing
* Tweak - Improved the Rating filter by adding better star icons and two usage modes
* Tweak - Moved the 'Where to filter?' and 'What to filter?' fields into a separate metabox called 'Location'
* Tweak - Improved the Apply and Reset buttons in Apply Button mode. They are now floating for better usability
* Tweak - Improved the Filters widget on mobile option
* Fix   - Fixed bugs

= 1.9.1 =
*Release Date - 22 July 2025*
* Dev   - Added a new View ‘Rating’ for filters that displays rating stars
* Dev   - Added iOS-style toggle switches instead of regular checkboxes in the plugin settings fields
* Dev   - Added compatibility for the 'Discount Rules for WooCommerce' plugin to the Price range and the On sale filters
* Tweak - The “Collapse Filters Widget on Mobile devices” option has been improved
* Tweak - Added different borders to the fields of the Filter Widget depending on hover, focus, blur events
* Tweak - Added the FLRT_SET_TRANSIENT_ENABLED constant to disable transients
* Tweak - Added the preview link to the eye button for the new Filter Set
* Tweak - Increased a filter item height and added the "More options" button in dashboard
* Fix   - Fixed a bug with the “+” symbol in filter terms and URLs
* Fix   - Fixed a bug with the "?" character that remained in the URL after using the Range slider filter
* Fix   - Fixed a bug with a PHP message that there is no array element with key 'cols_count' in FiltersWidget.php
* Fix   - Fixed a bug and removed numeric values from the Range slider filter if a term is empty
* Fix   - Fixed a bug in Query Loop Pagination

= 1.9.0 =
*Release Date - 21 March 2025*
* Dev   - Added support for Woo Brands
* Tweak - Improved and fixed CSS styles for filters widget
* Tweak - Made floor(); and ceil(); for range values optional via apply_filters(); function
* Tweak - Added FLRT_DISABLE_CREDENTIALS constant to disable credentials link on filtered pages
* Fix   - Fixed bug with a Filter Set that contains the only Search field
* Fix   - Fixed bug with Fatal error /filter-everything/src/Admin/AdminHooks.php:144

= 1.8.9 =
*Release Date - 21 January 2025*
* Dev   - Added ability to make Color swatches rounded
* Fix   - Small CSS fixes for range slider, widget element margins.
* Fix   - Fixed bug in JS code for the mobile widget button counter ((18)) issue
* Fix   - Fixed bug with empty Parent filter label
* Fix   - Fixed bug with Elementor pagination e.g. "e-page-5dabfd1=2"

= 1.8.8 =
*Release Date - 07 November 2024*
* Fix   - Fixed bug with sorting method
* Tweak - Added attribute all="true" for the [fe_posts_found] shortcode that counts all posts for all Filter Sets on a page

= 1.8.7 =
*Release Date - 29 October 2024*
* Tweak - Added hook 'wpc_swatch_image_size'
* Fix   - Fixed issues with Select2 dropdowns after WooCommerce update to the > 9.0.0
* Fix   - Fixed style issues with the Avada theme
* Fix   - Fixed compatibility with the Load more button/Infinite scroll in Elementor
* Fix   - Fixed bug with sorting terms

= 1.8.6 =
*Release Date - 18 July 2024*
* Dev   - Added ability to translate SEO Rules with Polylang
* Fix   - Fixed compatibility issue with Polylang plugin
* Fix   - Fixed the ability to rewrite functions wrapped with function_exists()
* Fix   - Fixed small issue with terms order equal to the order in ACF field
* Fix   - Fixed issue with text search through filtered posts and apostrophe character
* Tweak - Added hooks 'wpc_taxonomy_location_terms', 'wpc_post_type_location_terms', 'wpc_author_location_terms'

= 1.8.5 =
*Release Date - 15 May 2024*
* Dev   - Added "Labels for Chips" option to configure chip labels
* Dev   - Added "Dropdown Label" option
* Tweak - The "Show in Chips" option was hidden due to lack of demand
* Tweak - Now default terms order in a Custom Field is the same as in ACF field
* Tweak - If ACF field terms have labels, they displays in the Filters widget instead of values
* Fix   - Fixed issue with WPML and Homepage in different languages

= 1.8.4 =
*Release Date - 08 April 2024*
* Dev   - Tested and improved compatibility with WordPress 6.5
* Fix   - Fixed issue with Filter Set for a post page
* Fix   - Fixed issue with preview products in draft status and PHP > 8.2
* Fix   - Fixed JS error occurred on type in the Search field input
* Fix   - Issue with incorrect attribute 'for' in the Filters Widget title label

= 1.8.3 =
*Release Date - 14 February 2024*
* Dev   - Increased plugin performance and made faster it up to 10 times
* Fix   - Added compatibility with PHP > 8.1, removed FILTER_SANITIZE_STRING error
* Fix   - Fixed bug with Date view available by default in a filter
* Fix   - Fixed the issue with products with empty '_sale_price' meta values and wrong On sale counters
* Fix   - Fixed issue with a parent filter, when current WordPress term archive page is selected term in the parent filter
* Fix   - Fixed bug when the only Search Field presents in a Filter Set
* Fix   - Fixed issues with Polylang plugin when language functions are not defined
* Fix   - Fixed issue with double click on the Apply Button after using the Search Field
* Fix   - Set correct permissions for the /assets dir
* Tweak - Added hook 'wpc_plugin_user_caps' to allow to modify user roles that can use the plugin
* Tweak - Made AJAX loading circle thinner

= 1.8.2 =
*Release Date - 10 January 2024*
* Fix   - Fixed issue with GET-style parameters in filter URLs after 1.8.0 update

= 1.8.1 =
*Release Date - 10 January 2024*
* Fix   - Fixed warning message on the login screen
* Fix   - Fixed issue with resetting filters cache

= 1.8.0 =
*Release Date - 08 January 2024*
* Dev   - Added new filter type by Post Date
* Tweak - Made Numeric Range filters collapsible
* Fix   - Renamed 'wpc_clean' function to 'flrt_clean' to avoid conflicts

= 1.7.16 =
*Release Date - 14 December 2023*
* Dev   - Added support for Dokan store pages
* Dev   - Added Experimental option that hides variable products with out of stock variations
* Tweak - Improved search field and added variations to search by SKU
* Tweak - Added global variable $flrt_plugin to access the class
* Tweak - Added ability to create translations for "Any %entity%" Filter Set type
* Fix   - Fixed the issue with double SEO titles and SEO Rules entities on block themes
* Fix   - Fixed issue with term_taxonomy_id and taxonomy filter counters
* Fix   - Fixed Select2 CSS conflict in Woocommerce admin forms

= 1.7.15 =
*Release Date - 01 August 2023*
* Dev   - Added Spanish translation
* Dev   - Tested compatibility with WordPress 6.3
* Fix   - Added 301 redirect to canonical URL with (or without) correct user trailing slash on filtering pages
* Fix   - Added hook 'wpc_do_filter_request' to the collectFilteredPostsIds(); method to fix term counter
* Fix   - Fixed hover "checked" effect for Color swatches on mobile devices
* Fix   - Improved On Sale and Regular price translations for the On Sale filter
* Tweak - Added hook 'wpc_set_min_max' to modify the $min_and_max array
* Tweak - Sorted Filter and SEO Rule terms alphabetically for greater convenience

= 1.7.14 =
*Release Date - 19 June 2023*
* Dev   - Added German translation. Thanks to Daniel (microteq)
* Tweak - Added the "How to?" Meta box on the Filter Set edit screen for quick help with popular questions
* Fix   - Hotfix for the 'MetaBoxes::adviceMetabox() cannot be called statically' error

= 1.7.11 =
*Release Date - 31 May 2023*
* Tweak - Added support for multi-currency for the WOOCS and CURCY plugins
* Fix   - Fixed location for the Apply button, when Filter Set is directed to All archive pages/Any taxonomy,post,author
* Fix   - Fixed posts search count for the search by SKU

= 1.7.10 =
*Release Date - 26 May 2023*
* Fix   - Fixed missing styles on Color swatches and logos edit pages in dashboard

= 1.7.9 =
*Release Date - 25 May 2023*
* Fix   - Fixed location for the Apply button, when Filter Set is directed to All archive pages/Any taxonomy,post,author
* Fix   - Fixed bug when the Apply button does not appear on frontend in the latest position of the Filter Set
* Fix   - Fixed bug with negative numbers for Numeric filters
* Fix   - Fixed bug with 404 errors, when Filter Set was in Trash and there were filters without Filter Set in DB
* Fix   - Fixed bug with JS alert for mobile Pop-up widget when AJAX is disabled
* Tweak - Replaced /page/ with $wp_query->pagination_base in permalinks

= 1.7.8 =
*Release Date - 08 May 2023*
* Fix   - Fixed fatal error in wpc-utility-functions.php(279): flrt_get_post_type_location_terms();

= 1.7.7 =
*Release Date - 08 May 2023*
* Dev   - Added Search field in the Filters widget. It is compatible with filtered posts, supports AJAX and allows to search by SKU among Woo products
* Dev   - Added ability to direct Filter Set to all singular pages (Any page)
* Fix   - Fixed compatibility issue for Bricks Builder and Filter Set for "Any taxonomy"
* Fix   - Improved CURL outer request to avoid 10 seconds freezing in /wp-admin when the request is failed
* Fix   - Fixed fatal error in FiltersWidget.php(32): extract()
* Tweak - Added filter get terms hooks to allow to select terms from external tables
* Tweak - Added hook 'widget_title' for all widget titles
* Tweak - Added hook 'wpc_do_filter_request' to handle every filter action for wp_query
* Tweak - Added hooks 'wpc_all_set_wp_queried_posts' and 'wpc_variations_meta_query'
* Tweak - Added notice, when "HTML id or class of the Posts Container" configured wrong

= 1.7.6 =
*Release Date - 14 March 2023*
* Dev   - Added [fe_posts_found] shortcode to display filtered posts number
* Fix   - Fixed bug with "+" symbol in ACF fields
* Fix   - Fixed compatibility related with post types for the latest Polylang Pro
* Tweak - Optimized main CSS file
* Tweak - Disabled including assets on pages does not related with filters
* Tweak - Improved frontend for the RTL version
* Tweak - Removed Uncategorized from Category pages list
* Tweak - Improved filter templates. Overridden templates should be updated

[See changelog for all versions](https://demo.filtereverything.pro/changelog.txt).

== Upgrade Notice ==

= 1.9.2 =
*Release Date - 18 March 2026*
* Dev   - Added the ability to use filters on Singular pages and filter native WordPress queries, including the Main WP Query and posts displayed with Gutenberg
* Dev   - Added Filter, Chips, and Sorting widgets for page builders: Elementor, Divi, Bricks Builder, Breakdance, Beaver Builder, and Gutenberg
* Dev   - Added the ability to create date filters for both regular post dates and dates stored in custom fields, including fields created with ACF
* Dev   - Added the ability to create a large number of filters in a Filter Set, such as 100 or more
* Dev   - Added a 'Horizontal view' checkbox to Filter Sets
* Dev   - Added functionality for automatic filter creation based on popular criteria
* Dev   - Added improved styles for the Filters widget
* Tweak - Added the ability to reset values in numeric fields
* Tweak - Added an icon to the 'Where to filter?' field for quick preview of the page where the Filter Set works
* Tweak - Added suggestions for custom field filters, along with autocomplete while typing
* Tweak - Improved the Rating filter by adding better star icons and two usage modes
* Tweak - Moved the 'Where to filter?' and 'What to filter?' fields into a separate metabox called 'Location'
* Tweak - Improved the Apply and Reset buttons in Apply Button mode. They are now floating for better usability
* Tweak - Improved the Filters widget on mobile option
* Fix   - Fixed bugs