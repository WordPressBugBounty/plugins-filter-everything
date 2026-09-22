<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

use FilterEverything\Filter\Pro\Api\ApiRequests;
use FilterEverything\Filter\Shortcodes;
use FilterEverything\Filter\Pro\PluginPro;
use FilterEverything\Filter\DefaultSettings;
use FilterEverything\Filter\PluginHelpers;

class Plugin
{
    use PluginHelpers;
    private $wpManager;

    public function __construct()
    {
        $this->wpManager = Container::instance()->getWpManager();
        $this->wpManager->init();
        $this->register_hooks();

        new Shortcodes();

        if( defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO ){
            new PluginPro();
        }

        if( is_admin() ){
            new Admin();
        }

        // «Plugin notifications» channel. Not admin-only: its daily fetch runs in WP-Cron.
        new Messages();
    }

    public function register_hooks(){
        /**
         * string
         */
        $getData  = Container::instance()->getTheGet();

        if( ! is_admin() ){
            // Priority 1000: run AFTER third-party routers (e.g. Brain\Cortex inside
            // WP User Manager, priority 100). When Cortex ran after us, it received
            // our "false", found no route of its own and re-ran $wp->query_posts(),
            // wiping the filtered main query with an unfiltered one.
            add_filter( 'do_parse_request', array( $this->wpManager, 'customParseRequest' ), 1000, 3 );

            add_action( 'parse_request', array( $this->wpManager, 'parseRequest' ) );
            add_action( 'pre_get_posts', array( $this->wpManager, 'addFilterQueryToWpQuery' ), 9999 );

            add_filter( 'posts_where', [ $this->wpManager, 'fixPostsWhereForSearch' ], 10, 2 );
            add_filter( 'post_limits_request', [ $this->wpManager, 'addSQlComment' ], 10, 2 );
            add_action( 'pre_get_posts', array( $this->wpManager, 'fixSearchPostType' ), 9999 );

            add_action( 'template_redirect', [ $this, 'prepareEntities' ] );

            add_action( 'wpc_filtered_query_end', [ $this, 'addSearchArgsToWpQuery' ] );
            add_action( 'wpc_all_set_wp_queried_posts', [ $this, 'addSearchArgsToWpQuery' ] );

            add_filter( 'posts_where', [ $this, 'postDateWhere' ], 10000, 2 );

            if ( flrt_is_woocommerce() ){
                add_action( 'woocommerce_product_query', 'flrt_remove_product_query_post_clauses', 10, 2 );
                add_filter( 'posts_search', [$this, 'addSkuSearchSql'], 10000, 2 );
            }

            $sorting = new Sorting();
            $sorting->registerHooks();
        }

        add_action( 'body_class', array( $this, 'bodyClass' ) );

        add_action( 'admin_print_styles', array( $this, 'includeAdminCss' ) );
        add_action( 'admin_print_scripts', array( $this, 'includeAdminJs' ) );

        // Do not include JS, if this page is admin or can't contain filters
        if( ! is_admin() && ! is_login() ){
            add_action( 'wp_head', [ $this, 'inlineFrontCss' ] );
            add_action( 'wp_print_styles', array( $this, 'includeFrontCss' ) );
            add_action( 'wp_print_scripts', array( $this, 'includeFrontJs' ) );
            add_action( 'wp_print_styles', array( $this, 'dynamicFrontCss' ) );
        }

        add_action( 'wp_footer', [$this, 'footerHtml'] );

        if( ! defined('FLRT_FILTERS_PRO') ) {
            add_action( 'wp_head', array($this, 'noIndexFilterPages'), 1 );
            add_filter( 'wpc_filter_set_default_fields', [ $this, 'addAvailableInProFields' ], 10, 2 );
            add_filter( 'wpc_pre_save_set_fields', [ $this, 'unsetAvailableInProFields' ] );
        }

        add_filter( 'wpc_filter_set_default_fields', [ $this, 'addSetTailFields' ], 20, 2 );

        // Disable single search result redirect
        add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

        add_action( 'save_post', [$this, 'resetTransitions'] );
        add_action( 'delete_post', [$this, 'resetTransitions'] );
        add_action( 'woocommerce_ajax_save_product_variations', [$this, 'resetTransitions'] );
        // Term changes bypass save_post but alter the filter data (term names,
        // slugs, product assignments via wp-cli/imports) — same invalidation
        add_action( 'created_term', [$this, 'resetTransitions'] );
        add_action( 'edited_term', [$this, 'resetTransitions'] );
        add_action( 'delete_term', [$this, 'resetTransitions'] );

        if( isset( $getData['reset_filters_cache'] ) && $getData['reset_filters_cache'] == true ){
            $this->resetTransitions();
        }

        add_action( 'wpc_before_filter_set_settings_fields', [$this, 'removeApplyButtonOrderField'] );
        add_filter( 'wpc_filter_set_prepared_values', [$this, 'handleFilterSetFieldsVisibility'] );

        add_action( 'wpc_cycle_filter_fields', [$this, 'showCombinedFields'], 10, 2 );

        add_action( 'pre_get_posts', [$this, 'burpOutAllWpQueries'], 9999 );

        add_action('wp_ajax_wpc-get-set-location-terms', [$this, 'sendSetLocationTerms']);

        add_filter('wpc_relevant_set_ids', [$this, 'findRelevantSets'], 10, 2);
        add_filter('wpc_is_filtered_query_free', [$this, 'isFilteredQuery'], 10, 2);


        add_filter('wpc_prepare_filter_set_parameters', [$this, 'prepareSetParameters'], 10, 2);

        add_filter('wpc_filter_before_make_default_set_values', [$this, 'legacyPrepareWpPageTypeValue'] );

        add_filter('wpc_validation_wp_page_type_entities', [$this, 'validationWpPageTypeEntities'] );

        add_filter('wpc_validation_location_entities', [$this, 'validationLocationEntities'], 10, 2);

        add_action( 'wpc_before_filter_set_settings_location_fields', [$this, 'showLocationFields'] );

        add_filter('manage_edit-' . FLRT_FILTERS_SET_POST_TYPE . '_columns', array($this, 'filterSetPostTypeCol'));
        add_action('manage_' . FLRT_FILTERS_SET_POST_TYPE . '_posts_custom_column', array($this, 'filterSetPostTypeColContent'), 10, 2);

        $woo_shortcodes = array(
            'products',
            'featured_products',
            'sale_products',
            'best_selling_products',
            'recent_products',
            'product_attribute',
            'top_rated_products'
        );

        // Set first install timestamp
        $first_install = get_option( 'wpc_first_install' );
        if ( ! $first_install ){
            $first_install = [];
            $first_install['install_time']  = time();
            $first_install['rate_disabled'] = false;
            $first_install['rate_delayed']  = false;
            update_option( 'wpc_first_install', $first_install );
        }

        // Fix caching problem for products queried by shortcode
        foreach ( $woo_shortcodes as $woo_shortcode ){
            add_filter( "shortcode_atts_{$woo_shortcode}", [$this, 'disableCacheProductsShortcode'] );
        }
    }

    public function resetTransitions()
    {
        $em = Container::instance()->getEntityManager();
        $all_filters = $em->getGlobalConfiguredSlugs();

        if( is_array( $all_filters ) ){
            foreach ( $all_filters as $entityEname => $slug ){
                // For terms it should be entity name
                $parts = explode( '#', $entityEname, 2 );
                $e_name = isset( $parts[1] ) ? $parts[1] : '';
                $type   = isset( $parts[0] ) ? $parts[0] : '';

                if ( in_array( $type, [ 'post_meta_num', 'tax_numeric' ] ) ) {
                    // Entity code appends the queried post types between the e_name and
                    // the format suffix (wpc_terms_post_meta_num__price_product_product_variation_v2),
                    // so the exact key can not be rebuilt here - match on the unsuffixed base
                    $this->deleteTermsTransientsLike( 'wpc_terms_' . $type . '_' . $e_name );
                }

                if ( in_array( $type, [ 'post_date' ] ) ) {
                    $this->deleteTermsTransientsLike( 'wpc_terms_post_date_' );
                }

                if ( in_array( $type, [ 'post_meta_date' ] ) ) {
                    $this->deleteTermsTransientsLike( 'wpc_terms_post_meta_date_' );
                }

                if ( $type === 'post_meta_exists' ) {
                    delete_transient( flrt_get_post_ids_transient_key( $e_name .'_yes' ) );
                    delete_transient( flrt_get_post_ids_transient_key( $e_name .'_no' ) );
                }

                // With an external object cache transients are not in the options
                // table and the LIKE lookups above find nothing, so always delete
                // the post-type-less key variant directly as well
                $terms_transient_key    = flrt_get_terms_transient_key( $type . '_'. $e_name );
                $post_ids_transient_key = flrt_get_post_ids_transient_key( $slug );
                $var_meta_transient_key = flrt_get_variations_transient_key( 'attribute_'. $e_name );

                delete_transient( $terms_transient_key );
                delete_transient( $post_ids_transient_key );
                delete_transient( $var_meta_transient_key );
            }
        }

        $variations_key = 'wpc_posts_variations' . FLRT_CACHE_FORMAT_SUFFIX;
        $filter_key     = 'wpc_filters_query';

        delete_transient($variations_key);
        delete_transient($filter_key);

        // Static filter-data files (see maybeWriteJsonBlobFile): bump the
        // version so new URLs are minted, and remove the now-unused files
        update_option( 'flrt_json_blob_ver', time(), false );
        $uploads = wp_upload_dir();
        if ( empty( $uploads['error'] ) ) {
            $blob_files = glob( trailingslashit( $uploads['basedir'] ) . 'flrt-cache/filters-*.json' );
            if ( is_array( $blob_files ) ) {
                foreach ( $blob_files as $blob_file ) {
                    @unlink( $blob_file );
                }
            }
        }

        unset( $terms_transient_key, $post_ids_transient_key, $var_meta_transient_key, $all_filters, $em );
    }

    /**
     * Deletes all DB-stored transients whose name contains the given base key.
     * Used by resetTransitions() for term caches whose full key includes parts
     * unknown at reset time (queried post types, language code, format suffix).
     */
    private function deleteTermsTransientsLike( $base_key )
    {
        global $wpdb;

        $like  = '%' . $wpdb->esc_like( $base_key ) . '%';
        $names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $like ) );

        if ( ! is_array( $names ) ) {
            return;
        }

        $keys = [];
        foreach ( $names as $name ) {
            $keys[] = str_replace( [ '_transient_timeout_', '_transient_' ], '', $name );
        }

        foreach ( array_unique( $keys ) as $key ) {
            delete_transient( $key );
        }
    }

    public function prepareEntities()
    {
        $wpManager  = Container::instance()->getWpManager();
        $em         = Container::instance()->getEntityManager();
        $sets       = $wpManager->getQueryVar('wpc_page_related_set_ids');

        if( $sets ){
            foreach( $sets as $set ){
                $em->prepareEntitiesToDisplay( array( $set ) );
            }
        }
    }


    public function addAvailableInProFields( $fields, $filterSet )
    {
        foreach ( $fields as $key => $attributes ){
            // Always insert regular 'old' field
            $new_fields[$key] = $attributes;

            if( $key === 'show_count' ){

                $new_fields['instead_custom_posts_container'] = array(
                    'type'          => 'inProButton',
                    'label'         => esc_html__('Results container', 'filter-everything'),
                    'pro_label'     => flrt_pro_promo_label(),
                    'name'          => $filterSet->generateFieldName('instead_custom_posts_container'),
                    'id'            => $filterSet->generateFieldId('instead_custom_posts_container'),
                    'default'       => '',
                    'settings'      => true
                );

            }

            if( $key === 'hide_empty' ){
                $new_fields['instead_hide_empty_filter_container'] = array(
                        'type'          => 'inProButton',
                        'label'         => esc_html__('Hide empty Filters', 'filter-everything'),
                        'pro_label'     => flrt_pro_promo_label(),
                        'name'          => $filterSet->generateFieldName('instead_custom_posts_container'),
                        'id'            => $filterSet->generateFieldId('instead_custom_posts_container'),
                        'default'       => '',
                        'instructions'  => esc_html__('Hide the Entire Filter if no one term contains posts', 'filter-everything'),
                        'settings'      => true
                );

            }

        }

        return $new_fields;
    }

    public function addSetTailFields(  $fields, $filterSet )
    {
        $new_fields     = [];
        // In PRO the tail block goes right after hide_empty_filter, which pushes
        // custom_posts_container below the horizontal filters — the same position
        // its free-mode teaser stub has
        $insert_after   = defined('FLRT_FILTERS_PRO') ? 'hide_empty_filter' : 'show_count';

        foreach ( $fields as $key => $attributes ){
            // Always insert regular 'old' field
            $new_fields[$key] = $attributes;

            if( $key === $insert_after ){

                $new_fields['use_search_field'] =  array(
                    'type'          => 'Checkbox',
                    'label'         => esc_html__('Enable Search Field', 'filter-everything'),
                    'name'          => $filterSet->generateFieldName('use_search_field'),
                    'id'            => $filterSet->generateFieldId('use_search_field'),
                    'class'         => 'wpc-field-use-search-field',
                    'default'       => 'no',
                    'instructions'  => esc_html__('Allows you to search by text among filtered posts', 'filter-everything'),
                    'settings'      => true
                );

                $new_fields['search_field_menu_order'] = array(
                    'type'          => 'Hidden',
                    'label'         => '',
                    'class'         => 'wpc-menu-order-field',
                    'id'            => $filterSet->generateFieldId('search_field_menu_order'),
                    'name'          => $filterSet->generateFieldName('search_field_menu_order'),
                    'default'       => -1,
                    'settings'      => true
                );

                $new_fields['search_field_label'] = array(
                    'type'          => 'Text',
                    'label'         => esc_html__('Search Field Title', 'filter-everything'),
                    'name'          => $filterSet->generateFieldName('search_field_label'),
                    'id'            => $filterSet->generateFieldId('search_field_label'),
                    'class'         => 'wpc-search-field-label',
                    'default'       => esc_html__('Search', 'filter-everything'),
                    'instructions'  => esc_html__('Specify if needed or leave empty', 'filter-everything'),
                    'settings'      => true
                );

                $new_fields['search_field_placeholder'] = array(
                    'type'          => 'Text',
                    'label'         => esc_html__('Search Field Placeholder', 'filter-everything'),
                    'name'          => $filterSet->generateFieldName('search_field_placeholder'),
                    'id'            => $filterSet->generateFieldId('search_field_placeholder'),
                    'class'         => 'wpc-search-field-placeholder',
                    'placeholder'   => esc_html__( 'e.g. Search products, Search posts', 'filter-everything' ),
                    'default'       => '',
                    'settings'      => true
                );

                $new_fields['use_apply_button'] =  array(
                    'type'          => 'Checkbox',
                    'label'         => esc_html__('«Apply Button» mode', 'filter-everything'),
                    'name'          => $filterSet->generateFieldName('use_apply_button'),
                    'id'            => $filterSet->generateFieldId('use_apply_button'),
                    'class'         => 'wpc-field-use-apply-button',
                    'default'       => 'no',
                    'instructions'  => esc_html__('Enables filtering by clicking the Apply button', 'filter-everything'),
                    'settings'      => true
                );

                $new_fields['apply_button_menu_order'] = array(
                    'type'          => 'Hidden',
                    'label'         => '',
                    'class'         => 'wpc-menu-order-field',
                    'id'            => $filterSet->generateFieldId('apply_button_menu_order'),
                    'name'          => $filterSet->generateFieldName('apply_button_menu_order'),
                    'default'       => -1,
                    'settings'      => true
                );

                $new_fields['apply_button_text'] = array(
                    'type'          => 'Text',
                    'label'         => esc_html__('Apply Button label', 'filter-everything'),
                    'name'          => $filterSet->generateFieldName('apply_button_text'),
                    'id'            => $filterSet->generateFieldId('apply_button_text'),
                    'class'         => 'wpc-field-apply-button-text',
                    'default'       => esc_html__('Apply', 'filter-everything'),
                    'settings'      => true
                );

                $new_fields['reset_button_text'] = array(
                    'type'          => 'Text',
                    'label'         => esc_html__('Reset Button label', 'filter-everything'),
                    'name'          => $filterSet->generateFieldName('reset_button_text'),
                    'id'            => $filterSet->generateFieldId('reset_button_text'),
                    'class'         => 'wpc-field-reset-button-text',
                    'default'       => esc_html__('Reset', 'filter-everything'),
                    'settings'      => true
                );

                $new_fields['horizontal_view'] = array(
                        'type'          => 'Checkbox',
                        'label'         => esc_html__('Horizontal filters', 'filter-everything'),
                        'name'          => $filterSet->generateFieldName('horizontal_view'),
                        'id'            => $filterSet->generateFieldId('horizontal_view'),
                        'class'         => 'wpc-field-horizontal-view-text',
                        'default'       => 'no',
                        'instructions'  => esc_html__('Display filters side by side instead of one per row', 'filter-everything'),
                        'settings'      => true
                );
                $columns_options = [];
                for ( $i = 2; $i <= 5; $i++ ){
                    $columns_options[(string)$i] = (string)$i;
                }
                $new_fields['horizontal_view_column'] = array(
                        'type'          => 'Select',
                        'label'         => esc_html__('Number of columns', 'filter-everything'),
                        'class'         => 'wpc-field-horizontal-view-column',
                        'id'            => $filterSet->generateFieldId('horizontal_view_column'),
                        'name'          => $filterSet->generateFieldName('horizontal_view_column'),
                        'options'       => $columns_options,
                        'default'       => '3',
                        'instructions'  => esc_html__('How many columns to use in horizontal mode', 'filter-everything'),
                        'settings'      => true
                );

                $screen = function_exists('get_current_screen') ? get_current_screen() : null;
                $new_fields['horizontal_view_priority'] = array(
                        'type'          => 'Select',
                        'label'         => esc_html__('Horizontal priority', 'filter-everything'),
                        'class'         => 'wpc-field-horizontal-view-priority',
                        'id'            => $filterSet->generateFieldId('horizontal_view_priority'),
                        'name'          => $filterSet->generateFieldName('horizontal_view_priority'),
                        'options'       => ['widget' => 'widget', 'filter_set' => 'filter_set'],
                        'default'       => ($screen && $screen->base === 'post' && $screen->action === 'add') ? 'filter_set' : 'widget',
                        'instructions'  => esc_html__('How many columns to use in horizontal mode', 'filter-everything'),
                        'settings'      => true,
                );
            }
        }

        return $new_fields;
    }

    public function unsetAvailableInProFields( $setFields )
    {
        unset( $setFields['instead_post_name'], $setFields['instead_custom_posts_container'] );

        return $setFields;
    }

    public function noIndexFilterPages()
    {
        if( flrt_is_filter_request() ){
            $robots['index']    = 'noindex';
            $robots['follow']   = 'nofollow';
            $content = implode(', ', $robots );
            echo sprintf('<meta name="robots" content="%s">', $content)."\r\n";
        }
    }

    /**
     * @see DynamicCss::printInlineCss()  (kept as the wp_head callback name)
     */
    public function inlineFrontCss()
    {
        ( new DynamicCss() )->printInlineCss();
    }

    /**
     * @see DynamicCss::enqueueDynamicCss()  (kept as the wp_print_styles callback name)
     */
    public function dynamicFrontCss()
    {
        ( new DynamicCss() )->enqueueDynamicCss();
    }

    public function bodyClass( $classes )
    {
        if( flrt_get_option('mobile_filter_settings') === 'show_open_close_button' ){
            $classes[] = 'wpc_show_open_close_button';
        }

        if( flrt_is_filter_request() ){
            $classes[] = 'wpc_is_filter_request';
        }

        return $classes;
    }

    public static function activate()
    {
        $defaultSettings = new DefaultSettings();
        if ( ! get_option('wpc_filter_settings') ) {
            add_option('wpc_filter_settings', $defaultSettings->wpc_filter_settings() );
        }

        if( ! get_option( 'wpc_filter_experimental' ) ){
            add_option('wpc_filter_experimental', $defaultSettings->wpc_filter_experimental() );
        }

        // Stamp the current version on fresh installs so 'update'-triggered
        // admin notices (see AdminNotices) fire only when an existing install is
        // later updated, never on a brand-new installation.
        if ( ! get_option( 'flrt_version' ) ) {
            add_option( 'flrt_version', FLRT_PLUGIN_VER );
        }
        // When the plugin arrived — Messages keeps promos quiet for a day after it.
        if ( ! get_option( Messages::INSTALLED_AT_OPTION ) ) {
            add_option( Messages::INSTALLED_AT_OPTION, time(), '', false );
        }

        // PRO: put the managed block back into a physical robots.txt right away
        // (deactivate() removed it) instead of waiting for the next trigger.
        if ( function_exists( 'flrt_robots_file_sync' ) ) {
            flrt_robots_file_sync( 'activate' );
        }
    }

    /**
     * Deactivation: PRO takes its managed block out of the physical robots.txt
     * and stops the daily sync cron. Options and posts are kept for the
     * next activation — that is uninstall()'s job.
     */
    public static function deactivate()
    {
        if ( function_exists( 'flrt_robots_file_teardown' ) ) {
            flrt_robots_file_teardown();
        }

        Messages::teardown();
    }

    /**
     * Clears all plugin data: options and posts
     */
    /**
     * Removes the static filter-data cache (uploads/flrt-cache) with its
     * folder. Defensive on purpose: the path must look exactly like our own
     * folder, only known file patterns are deleted, every step is
     * error-suppressed and any unexpected failure is swallowed — the cleanup
     * is best-effort and must never break the uninstall process.
     */
    private static function deleteJsonBlobCache()
    {
        try {
            $uploads = wp_upload_dir();
            if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
                return;
            }

            $dir = trailingslashit( $uploads['basedir'] ) . 'flrt-cache';

            if ( substr( $dir, -strlen( '/flrt-cache' ) ) !== '/flrt-cache' || ! is_dir( $dir ) ) {
                return;
            }

            $blob_files = glob( $dir . '/filters-*.json' );
            $tmp_files  = glob( $dir . '/filters-*.json.*.tmp' );

            $to_delete   = array_merge(
                is_array( $blob_files ) ? $blob_files : [],
                is_array( $tmp_files ) ? $tmp_files : []
            );
            $to_delete[] = $dir . '/index.php';

            foreach ( $to_delete as $file ) {
                if ( is_file( $file ) ) {
                    @unlink( $file );
                }
            }

            // Removed only when empty — anything unexpected inside stays put
            @rmdir( $dir );
        } catch ( \Throwable $e ) {
            // Best-effort cleanup: never break the uninstall
        }
    }

    public static function uninstall( $force = false )
    {
        $allow_to_delete = true;

        if( is_multisite() ){
            $active_plugins = get_site_option('active_sitewide_plugins');
            if( is_array( $active_plugins ) ){
                $active_plugins = array_keys( $active_plugins );
            }
        }else{
            $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
        }

        $fe_active    = [];
        $to_compare   = [
            'filter-everything-pro/filter-everything.php',
            'filter-everything/filter-everything.php'
        ];

        if( ! empty( $active_plugins ) ){
            foreach ( $active_plugins as $plugin_path ){
                if( in_array( $plugin_path, $to_compare ) ){
                    $fe_active[] = $plugin_path;
                }
            }
        }

        if( count( $fe_active ) > 0 ){
            $allow_to_delete = false;
        }

        if ( $force == true ) {
            $allow_to_delete = true;
        }

        if( $allow_to_delete ){

            if ( function_exists( 'flrt_robots_file_teardown' ) ) {
                flrt_robots_file_teardown();
            }

            $options = [
                'wpc_robots_file_state',
                'wpc_filter_settings',
                'wpc_indexing_deep_settings',
                'wpc_filter_permalinks',
                'wpc_seo_rules_settings',
                'wpc_xml_write_date',
                'wpc_filter_experimental',
                'flrt_json_blob_ver',
                'widget_wpc_filters_widget',
                'widget_wpc_sorting_widget',
                'widget_wpc_chips_widget',
            ];

            foreach ( $options as $option_name ){
                delete_option( $option_name );
            }

            self::deleteJsonBlobCache();

            // «Plugin notifications»: cron event, cached feed, consent, per-user dismissals
            Messages::uninstall();
            if ( class_exists( __NAMESPACE__ . '\\ReviewRequest' ) ) {
                ReviewRequest::uninstall();
            }

            // Deactivate and erase license if exists
            if ( defined( 'FLRT_FILTERS_PRO' ) && FLRT_FILTERS_PRO ) {

                wpc_clear_folder(FLRT_XML_PATH);

                $to_send             = false;
                $saved_value         = get_option( FLRT_LICENSE_KEY );

                if( isset( $saved_value['license_key'] ) ) {
                    $saved_value_arr = maybe_unserialize( base64_decode( $saved_value['license_key'] ) );
                    $to_send         = $saved_value_arr;
                }

                if ( is_array( $to_send ) ){
                    $to_send['home_url'] = home_url();

                    // Make data suitable to send as GET variables
                    $to_send = array_map( 'urlencode', $to_send );

                    if ( isset( $saved_value_arr['id'] ) && $saved_value_arr['id'] ) {
                        $apiRequest = new ApiRequests();
                        $result     = $apiRequest->sendRequest('DELETE', 'license', $to_send );

                        // If license was deactivated, we have to refresh updates info
                        delete_transient(FLRT_VERSION_TRANSIENT );
                        delete_option( FLRT_LICENSE_KEY );
                    }
                }

            }

            $postTypes = array(
                FLRT_FILTERS_SET_POST_TYPE,
                FLRT_FILTERS_POST_TYPE
            );

            if( defined( 'FLRT_SEO_RULES_POST_TYPE' ) ){
                $postTypes[] = FLRT_SEO_RULES_POST_TYPE;
            }

            $filterPosts = new \WP_Query(
                array(
                    'posts_per_page' => -1,
                    'post_status' => array('any'),
                    'post_type' => $postTypes,
                    'fields' => 'ids',
                    'suppress_filters' => true
                )
            );

            $filterPostsIds = $filterPosts->get_posts();

            if( ! empty( $filterPostsIds ) ){
                foreach ($filterPostsIds as $post_id) {
                    wp_delete_post( $post_id, true );
                }
            }

            $filterTrashPosts = new \WP_Query(
                array(
                    'posts_per_page' => -1,
                    'post_status' => array('trash'),
                    'post_type' => $postTypes,
                    'fields' => 'ids',
                    'suppress_filters' => true
                )
            );

            $filterTrashPostsIds = $filterTrashPosts->get_posts();

            if( ! empty( $filterTrashPostsIds ) ){
                foreach ($filterTrashPostsIds as $post_id) {
                    wp_delete_post( $post_id, true );
                }
            }
        }
    }

    public static function switchTheme() {
        flrt_remove_option('posts_container');
        flrt_remove_option('primary_color');
    }

    public function includeAdminCss()
    {
        $screen = get_current_screen();
        if ( ! is_null( $screen ) && property_exists( $screen, 'base' ) ) {
            $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
            $ver    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? rand(0, 1000) : FLRT_PLUGIN_VER;
            $is_about_pro_tab = isset($_GET['tab']) && $_GET['tab'] === 'aboutpro';

            if ( in_array( $screen->base, [ 'edit', 'post', 'edit-tags', 'term' ] ) || ( strpos( $screen->base, 'filters-settings' ) !== false ) ) {
                wp_enqueue_style( 'wpc-filter-everything-admin', FLRT_PLUGIN_DIR_URL . 'assets/css/filter-everything-admin'.$suffix.'.css', ['wp-color-picker'], $ver );
                if($is_about_pro_tab){
                    wp_enqueue_style( 'wpc-filter-everything-pro-benefits', FLRT_PLUGIN_DIR_URL . 'assets/css/pro-benefits-page'.$suffix.'.css', ['wpc-filter-everything-admin'], $ver );
                }
            }

            if ( $screen->base === 'widgets' ) {
                wp_enqueue_style('wpc-widgets', FLRT_PLUGIN_DIR_URL . 'assets/css/wpc-widgets' . $suffix . '.css', [], $ver );
            }

        }
    }

    public function includeAdminJs()
    {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $ver    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? rand(0, 1000) : FLRT_PLUGIN_VER;
        $select2ver = '4.1.0';

        wp_register_script( 'jquery-tiptip', FLRT_PLUGIN_DIR_URL . 'assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), $ver, true );
        wp_enqueue_script('jquery-tiptip');
        wp_enqueue_script('wpc-filters-admin', FLRT_PLUGIN_DIR_URL . 'assets/js/wpc-filters-common-admin' . $suffix . '.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'select2'), $ver, true );

        $l10n = array(
            'prefixesOrderAvailableInPro' => esc_html__( 'Editing the order of URL prefixes is available in the PRO version', 'filter-everything' ),
            'chipsPlaceholder'            => esc_html__( 'Select or enter hooks', 'filter-everything' ),
            'colorSwatchesPlaceholder'    => esc_html__( 'Click to select taxonomies', 'filter-everything' ),
            'chooseElementHelpText'    => esc_html__( 'Hover over the Results container and click to select it', 'filter-everything' ),
        );
        wp_localize_script( 'wpc-filters-admin', 'wpcFiltersAdminCommon', $l10n );

        wp_enqueue_script( 'select2', FLRT_PLUGIN_DIR_URL . "assets/js/select2/select2".$suffix.".js", array('jquery'), $select2ver );
        wp_enqueue_style('select2', FLRT_PLUGIN_DIR_URL . "assets/css/select2/select2".$suffix.".css", '', $select2ver );

        $screen = get_current_screen();

        if( ! is_null( $screen ) && property_exists( $screen, 'base' ) && $screen->base === 'widgets' ){
            wp_enqueue_script('wpc-widgets', FLRT_PLUGIN_DIR_URL . 'assets/js/wpc-widgets' . $suffix . '.js', array('jquery'), $ver );
            $l10n = array(
                'wpcItemNum'  => esc_html__( 'Item #', 'filter-everything')
            );
            wp_localize_script( 'wpc-widgets', 'wpcWidgets', $l10n );
        }
    }

    public function includeFrontCss()
    {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        $ver = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? rand(0, 1000) : FLRT_PLUGIN_VER;
        /**
         * string
         */
        $getData  = Container::instance()->getTheGet();
        if( isset( $getData['fl_builder'] ) ){
            wp_enqueue_style('wpc-widgets', FLRT_PLUGIN_DIR_URL . 'assets/css/wpc-widgets' . $suffix . '.css', [], $ver );
        }
        if ( isset( $getData['flrt_get_html_selector'] ) && $getData['flrt_get_html_selector'] === '1' && current_user_can('manage_options') ) {
            wp_enqueue_style('wpc-element-picker', FLRT_PLUGIN_DIR_URL . 'assets/css/element-picker' . $suffix . '.css', [], $ver );
        }

        // Do not include plugin CSS if there are no Filter Sets on the page
        $sets = $this->wpManager->getQueryVar('wpc_page_related_set_ids', []);
        if( empty( $sets ) ) {
            return false;
        }

        wp_enqueue_style('wpc-filter-everything', FLRT_PLUGIN_DIR_URL . 'assets/css/filter-everything' . $suffix . '.css', [], $ver);
    }

    public function footerHtml()
    {
        echo '<div class="wpc-filters-overlay"></div>'."\r\n";
    }

    public function includeFrontJs()
    {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $ver    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? rand(0, 1000) : FLRT_PLUGIN_VER;
        /**
         * string
         */
        $getData  = Container::instance()->getTheGet();
        $em       = Container::instance()->getEntityManager();

        $wpcFrontJsVariables = [];

        if ( isset( $getData['flrt_get_html_selector'] ) && $getData['flrt_get_html_selector'] === '1' && current_user_can('manage_options') ) {
            wp_enqueue_script( 'wpc-element-picker-bundle', FLRT_PLUGIN_DIR_URL . 'assets/js/js-element-picker/element-picker.bundle' . $suffix . '.js', array( 'jquery' ), $ver, true );
            wp_enqueue_script( 'wpc-element-picker', FLRT_PLUGIN_DIR_URL . 'assets/js/element-picker' . $suffix . '.js', array( 'jquery', 'wpc-element-picker-bundle' ), $ver, true );
            add_action( 'wp_footer', [$this, 'showElementPickerPanel'] );
        }

        if( isset( $getData['fl_builder'] ) ){
            wp_enqueue_script('wpc-widgets', FLRT_PLUGIN_DIR_URL . 'assets/js/wpc-widgets' . $suffix . '.js', array('jquery'), $ver );
            $l10n = array(
                'wpcItemNum'  => esc_html__( 'Item #', 'filter-everything' )
            );
            wp_localize_script( 'wpc-widgets', 'wpcWidgets', $l10n );
        }

        // Do not include plugin JS if there are no Filter Sets on the page
        $sets               = $this->wpManager->getQueryVar( 'wpc_page_related_set_ids', [] );
        $related_filters = $em->getSetsRelatedFilters( $sets );
        $date_filters       = [];
        $include_timepicker = false;

        if ( ! empty( $related_filters ) ) {
            foreach ( $related_filters as $filter ) {
                if ( in_array( $filter['entity'], [ 'post_date', 'post_meta_date' ] ) ) {

                    $date_time = flrt_split_date_time( $filter['date_format'] );

                    global $wp_locale;
                    $date_filters[ $filter['ID'] ] = [
                        'date_type'     => $filter['date_type'],
                        'date_format'   => flrt_convert_date_to_js( $date_time['date'] ),
                        'time_format'   => flrt_convert_time_to_js( $date_time['time'] ),
                    ];

                    if ( in_array( $filter['date_type'], [ 'time', 'datetime' ] ) ) {
                        $include_timepicker = true;
                    }

                }
            }
        }

        /**
         * Do not continue and do not include any assets
         * if this page does not contain Filter Sets
         */
        if ( empty( $sets ) ) {
            return false;
        }

        $showBottomWidget   = 'no';
        $ajaxEnabled        = false;
        $autoScroll         = false;
        $waitCursor         = false;
        $wpcUseSelect2      = false;
        $wpcPopupCompatMode = false;
        $autoScrollOffset   = apply_filters( 'wpc_auto_scroll_offset', 150 );
        $wpc_mobile_width   = flrt_get_mobile_width();
        $per_page           = [];
        $applyButtonSets    = [];
        $queryOnThePageSets = [];
        $filterSetService   = Container::instance()->getFilterSetService();

        if ( flrt_get_option('mobile_filter_settings') === 'show_bottom_widget' ) {
            $showBottomWidget = 'yes';
        }

        if ( flrt_get_option('enable_ajax') === 'on' ) {
            $ajaxEnabled = true;
        }

        if ( flrt_get_experimental_option('auto_scroll') === 'on' ) {
            $autoScroll = true;
        }

        if ( flrt_get_experimental_option( 'use_wait_cursor' ) === 'on' ) {
            $waitCursor = true;
        }

        if ( flrt_get_option('bottom_widget_compatibility') ) {
            $wpcPopupCompatMode = true;
        }

        //@todo This appears on login page and produce not an array error
        foreach( $sets as $set ){
            if( $set['filtered_post_type'] === 'product' && function_exists('wc_get_default_products_per_row') ){
                $numberposts = apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() );
            }else{
                $numberposts = get_option( 'posts_per_page' );
            }

            $per_page[ $set['ID'] ] = intval($numberposts);
            $theSet = $filterSetService->getSet( $set['ID'] );

            if( isset( $set['query_on_the_page'] ) && $set['query_on_the_page'] ){
                if( (int) $set['ID'] > 0 ) {
                    $queryOnThePageSets[] = (int) $set['ID'];
                }
            }

            if( isset( $theSet['use_apply_button']['value'] ) && $theSet['use_apply_button']['value'] === 'yes' ){
                if( (int) $set['ID'] > 0 ){
                    $applyButtonSets[] = (int) $set['ID'];
                }
            }
        }

        $per_page = apply_filters( 'wpc_filter_sets_posts_per_page', $per_page );

        $wpcPostContainers = apply_filters( 'wpc_posts_containers', flrt_get_option( 'posts_container', flrt_default_posts_container() ) );

        if ( is_array( $wpcPostContainers ) ) {
            $wpcPostContainers = array_map( 'wp_specialchars_decode', $wpcPostContainers );
        }

        wp_register_script( 'wc-jquery-ui-touchpunch', FLRT_PLUGIN_DIR_URL . 'assets/js/jquery-ui-touch-punch/jquery-ui-touch-punch'.$suffix.'.js', [], $ver, true );

        $isPro = defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO;

        $wpcFrontJsVariables = array(
            'ajaxUrl'                    => admin_url('admin-ajax.php'),
            'wpcAjaxEnabled'             => $ajaxEnabled,
            'wpcStatusCookieName'        => FLRT_FOLDING_COOKIE_NAME,
            'wpcMoreLessCookieName'      => FLRT_MORELESS_COOKIE_NAME,
            'wpcHierarchyListCookieName' => FLRT_HIERARCHY_LIST_COOKIE_NAME,
            'wpcWidgetStatusCookieName'  => FLRT_OPEN_CLOSE_BUTTON_COOKIE_NAME,
            'wpcMobileWidth'             => $wpc_mobile_width,
            'showBottomWidget'           => $showBottomWidget,
            '_nonce'                     => wp_create_nonce('wpcNonceFront'),
            'wpcPostContainers'          => $wpcPostContainers,
            'wpcAutoScroll'              => $autoScroll,
            'wpcAutoScrollOffset'        => $autoScrollOffset,
            'wpcWaitCursor'              => $waitCursor,
            'wpcPostsPerPage'            => $per_page,
            'wpcUseSelect2'              => $wpcUseSelect2,
            'wpcDateFilters'             => false,
            'wpcPopupCompatMode'         => $wpcPopupCompatMode,
            'wpcApplyButtonSets'         => $applyButtonSets,
            'wpcQueryOnThePageSets'      => $queryOnThePageSets,
            'wpcNoPostsContainerMsg'     => esc_html__('It appears that this page does not contain the container specified in the «Results container» option. Try to specify the correct one in the Filter Set settings or the common plugin Settings.', 'filter-everything'),
            'wpcIsPro'                   => $isPro,
            'permalinksEnabled'          => FLRT_PERMALINKS_ENABLED,
            'wpcMoreLessCount'           => flrt_more_less_count(),
            'wpcSearchChipsText'         => esc_html__('search: %s', 'filter-everything' ),
            'chipsTitle'                 => esc_html__('Remove %s from results', 'filter-everything'),
            'chipsReset'                 => esc_html__('Reset all', 'filter-everything'),
        );

        /**
         * We includes Flatpickr.js only on pages, where date filter is used.
         */
        if ( ! empty( $date_filters ) ) {

            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'wpc-datepicker', FLRT_PLUGIN_DIR_URL . 'assets/css/datepicker/jquery-ui'.$suffix.'.css', array(), '1.11.4' );

            if ( $include_timepicker ) {
                wp_enqueue_script( 'wpc-timepicker', FLRT_PLUGIN_DIR_URL . "assets/js/timepicker/jquery-ui-timepicker-addon".$suffix.".js", array( 'jquery-ui-datepicker' ), '1.6.3' );
                wp_enqueue_style( 'wpc-timepicker', FLRT_PLUGIN_DIR_URL . "assets/css/timepicker/jquery-ui-timepicker-addon".$suffix.".css", array(), '1.6.3' );
            }

            $wpcFrontJsVariables['wpcDateFilters'] = $date_filters;
            $wpcFrontJsVariables['wpcDateFiltersLocale'] = determine_locale();
            $wpcFrontJsVariables['wpcDateFiltersL10n'] = array(
                'closeText'       => _x( 'Filter', 'Date Picker closeText', 'filter-everything' ),
                'currentText'     => _x( 'Today', 'Date Picker currentText', 'filter-everything' ),
                'nextText'        => _x( 'Next', 'Date Picker nextText', 'filter-everything' ),
                'prevText'        => _x( 'Prev', 'Date Picker prevText', 'filter-everything' ),
                'weekHeader'      => _x( 'Wk', 'Date Picker weekHeader', 'filter-everything' ),
                'timeOnlyTitle'   => _x( 'Choose Time', 'Date Time Picker timeOnlyTitle', 'filter-everything' ),
                'timeText'        => _x( 'Time', 'Date Time Picker timeText', 'filter-everything' ),
                'hourText'        => _x( 'Hour', 'Date Time Picker hourText', 'filter-everything' ),
                'minuteText'      => _x( 'Minute', 'Date Time Picker minuteText', 'filter-everything' ),
                'secondText'      => _x( 'Second', 'Date Time Picker secondText', 'filter-everything' ),
                'timezoneText'    => _x( 'Time Zone', 'Date Time Picker timezoneText', 'filter-everything' ),
                'selectText'      => _x( 'Select', 'Date Time Picker selectText', 'filter-everything' ),
                'amNames'         => array(
                    _x( 'AM', 'Date Time Picker amText', 'filter-everything' ),
                    _x( 'A', 'Date Time Picker amTextShort', 'filter-everything' ),
                ),
                'pmNames'       => array(
                    _x( 'PM', 'Date Time Picker pmText', 'filter-everything' ),
                    _x( 'P', 'Date Time Picker pmTextShort', 'filter-everything' ),
                ),

                'monthNames'      => array_values( $wp_locale->month ),
                'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
                'dayNames'        => array_values( $wp_locale->weekday ),
                'dayNamesMin'     => array_values( $wp_locale->weekday_initial ),
                'dayNamesShort'   => array_values( $wp_locale->weekday_abbrev ),
                'firstDay'        => get_option( 'start_of_week' ),
                'applyText'       => _x( 'Apply', 'Date Picker applyText', 'filter-everything' ),
            );
        }

        /**
         * Include Main Front filters javascript
         */
        wp_enqueue_script('wpc-filter-everything', FLRT_PLUGIN_DIR_URL . 'assets/js/filter-everything'.$suffix.'.js', array('jquery', 'jquery-ui-slider', 'wc-jquery-ui-touchpunch'), $ver, true );

        if( flrt_get_experimental_option('select2_dropdowns') === 'on' ){
            $select2ver = '4.1.0';
            $wpcUseSelect2 = 'yes';
            wp_enqueue_script( 'select2', FLRT_PLUGIN_DIR_URL . "assets/js/select2/select2".$suffix.".js", array('jquery'), $select2ver );
            wp_enqueue_style('select2', FLRT_PLUGIN_DIR_URL . "assets/css/select2/select2".$suffix.".css", '', $select2ver );
        }

        $wpcFrontJsVariables['wpcUseSelect2'] = $wpcUseSelect2;

        wp_localize_script( 'wpc-filter-everything', 'wpcFilterFront', $wpcFrontJsVariables );

        if(!empty($applyButtonSets) && flrt_instant_recount()){
            $this->inlineScriptJsonData();
        }

        unset( $filterSetService, $wpcFrontJsVariables );
    }

    private function inlineScriptJsonData()
    {
        global $wp, $wp_rewrite;
        $flrt_json_data = Container::instance()->getFilterContext()->jsonData();
        if(!empty($flrt_json_data)){
            $wpc_filter_permalinks = get_option( 'wpc_filter_permalinks', [] );
            $permalinksTab = new PermalinksTab();
            $wpc_filter_permalinks_numbering = [];
            $wpc_filter_permalinks_entities = [];
            $permalinks_result = [];
            $includeEntities = ['post_meta_num', 'tax_numeric', 'post_date', 'post_meta_date'];
            $i = 1;
            // Maps are keyed by the full "entity#e_name" pair: two filters may share
            // an e_name with different entities (post_meta_num#_sale_price "Sale Price"
            // vs post_meta_exists#_sale_price "On Sale") and keying by e_name alone
            // made the last one win, sending On Sale into the no-slug GET branch (?yes=on)
            foreach ($permalinksTab->movePostMetaNumInTheEnd($wpc_filter_permalinks) as $key => $value) {
                $parts = explode('#', $key);
                $permalinks_result[$key] = $value;
                if(in_array($parts[0], $includeEntities)){
                    $wpc_filter_permalinks_entities[$key] = $parts[0];
                }
                $wpc_filter_permalinks_numbering[$i++] =  $key;
            }
            $flrt_json_data['wpcFilterPermalinks'] = $permalinks_result;
            $flrt_json_data['wpcFilterPermalinksNum'] = $wpc_filter_permalinks_numbering;
            $flrt_json_data['wpcFilterEntitiesWithoutSlug'] = $wpc_filter_permalinks_entities;
            // Pagination must never leak into the Apply URL base: applying a new
            // selection restarts the results from page 1 (deeper pages may not exist)
            $request         = (string) $wp->request;
            $pagination_base = ! empty( $wp_rewrite->pagination_base ) ? $wp_rewrite->pagination_base : 'page';
            $request         = preg_replace( '#(^|/)' . preg_quote( $pagination_base, '#' ) . '/\d+$#', '', $request );

            $flrt_json_data['domain'] = trailingslashit(home_url(add_query_arg([], $request)));
            // The site's permalink convention (mirrors user_trailingslashit) —
            // the client-side URL builder must terminate paths the same way
            $flrt_json_data['trailingSlash'] = ( user_trailingslashit( 'wpc' ) === 'wpc/' );
            // On shops that list variations as standalone catalog items (XStore's
            // variable_products_detach) the client-side recount must keep displayed
            // counts in variation space — mirrors the server-side
            // wpc_from_variations_to_products counting gate.
            $flrt_json_data['variationsAsProducts'] = function_exists( 'flrt_variations_listed_as_products' )
                ? (bool) flrt_variations_listed_as_products() : false;

            /**
             * The selection-independent bulk of the data (term post lists,
             * meta values, the set universe — ~95% of the payload, tens of
             * MB on large catalogs) is served as a hash-versioned STATIC
             * FILE: cacheable across pages and visits, fetched off the
             * critical path. Only the page-scoped part stays inline. When
             * the file cannot be written, everything falls back inline —
             * still as an inert JSON block, never as a JS object literal
             * (the full JS parser needed ~28 s for 32 MB; JSON.parse of the
             * same payload takes ~60 ms).
             */
            $blobUrl  = $this->maybeWriteJsonBlobFile( $flrt_json_data );
            $pagePart = [];

            if ( $blobUrl ) {
                foreach ( [ 'wpcFilterPermalinks', 'wpcFilterPermalinksNum', 'wpcFilterEntitiesWithoutSlug', 'domain', 'trailingSlash', 'variationsAsProducts' ] as $rootKey ) {
                    if ( array_key_exists( $rootKey, $flrt_json_data ) ) {
                        $pagePart[ $rootKey ] = $flrt_json_data[ $rootKey ];
                    }
                }
                foreach ( $flrt_json_data as $key => $setData ) {
                    if ( ! is_numeric( $key ) ) {
                        continue;
                    }
                    $pagePart[ $key ] = [
                        'filteredAllPostsIds' => isset( $setData['filteredAllPostsIds'] ) ? $setData['filteredAllPostsIds'] : [],
                        'totalFilteredCount'  => isset( $setData['totalFilteredCount'] ) ? $setData['totalFilteredCount'] : 0,
                    ];
                }
                $pagePart['blobUrl'] = $blobUrl;
                $json = wp_json_encode( $pagePart );
            } else {
                $json = wp_json_encode( $flrt_json_data );
            }

            add_action( 'wp_print_footer_scripts', function () use ( $json ) {
                echo '<script type="application/json" id="wpc-filter-json-data">' . $json . '</script>' . "\n";
            }, 1 );

            $bootstrap  = 'try { var wpcFilterJsonDataEl = document.getElementById("wpc-filter-json-data");';
            $bootstrap .= ' if ( wpcFilterJsonDataEl ) { var wpcFilterPagePart = JSON.parse( wpcFilterJsonDataEl.textContent );';
            $bootstrap .= ' wpcFilterJsonDataEl.parentNode.removeChild( wpcFilterJsonDataEl );';
            $bootstrap .= ' if ( wpcFilterPagePart.blobUrl ) {';
            $bootstrap .= ' window.wpcFilterJsonBlobUrl = wpcFilterPagePart.blobUrl;';
            $bootstrap .= ' window.wpcFilterJsonDataPromise = fetch( wpcFilterPagePart.blobUrl, { credentials: "same-origin" } )';
            $bootstrap .= '.then( function (r) { if ( ! r.ok ) { throw new Error( "HTTP " + r.status ); } return r.json(); } )';
            $bootstrap .= '.then( function (blob) { for ( var k in wpcFilterPagePart ) { if ( k === "blobUrl" ) { continue; }';
            $bootstrap .= ' if ( blob[k] && typeof blob[k] === "object" && ! Array.isArray( blob[k] ) && wpcFilterPagePart[k] && typeof wpcFilterPagePart[k] === "object" && ! Array.isArray( wpcFilterPagePart[k] ) ) {';
            $bootstrap .= ' for ( var kk in wpcFilterPagePart[k] ) { blob[k][kk] = wpcFilterPagePart[k][kk]; } } else { blob[k] = wpcFilterPagePart[k]; } }';
            $bootstrap .= ' window.wpcFilterJsonData = blob; return blob; } )';
            $bootstrap .= '.catch( function (e) { if ( window.console ) { console.error( "Filter Everything: filter data fetch failed", e ); } } );';
            $bootstrap .= ' } else { window.wpcFilterJsonData = wpcFilterPagePart; } }';
            $bootstrap .= ' } catch (e) { if ( window.console ) { console.error( "Filter Everything: filter data JSON parse failed", e ); } }';

            wp_add_inline_script( 'wpc-filter-everything', $bootstrap, 'before' );
        }
    }

    /**
     * Writes the selection-independent part of $flrt_json_data to a static
     * JSON file in uploads/flrt-cache/ and returns its URL (false = keep
     * everything inline). The file name carries a hash of the inputs that
     * define the content (plugin/cache versions, blob version bumped in
     * resetTransitions(), set group, language, universe), so URLs are
     * immutable: a stale file is impossible, only unused ones — those are
     * garbage-collected in resetTransitions().
     */
    private function maybeWriteJsonBlobFile( $flrt_json_data )
    {
        if ( apply_filters( 'wpc_json_static_file', true ) === false ) {
            return false;
        }

        $setIds = array_filter( array_keys( $flrt_json_data ), 'is_numeric' );
        if ( empty( $setIds ) ) {
            return false;
        }

        $firstSet = $flrt_json_data[ reset( $setIds ) ];
        $group    = isset( $firstSet['relatedSets'] ) && $firstSet['relatedSets'] !== '' ? $firstSet['relatedSets'] : implode( '_', $setIds );
        $group    = preg_replace( '/[^0-9_]/', '', (string) $group );
        $universe = ( isset( $firstSet['allPostsIds'] ) && is_array( $firstSet['allPostsIds'] ) ) ? array_keys( $firstSet['allPostsIds'] ) : [];
        $lang     = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '';

        // The filter CONFIGS (view, More/Less, parent, labels, ...) shape the
        // stored blob, but no hook is guaranteed to bump flrt_json_blob_ver for
        // every way a config can change — a missed bump used to freeze a stale
        // config into the immutable file forever (e.g. a filter switched to
        // More/Less kept more_less='no' in the blob and the recount hid every
        // term of it). Fingerprint the configs directly: a config change now
        // mints a new file name by construction. 'values' is page-scoped and
        // normalized out of the stored blob — it must not vary the name.
        $configFingerprint = [];
        foreach ( $setIds as $sid ) {
            if ( empty( $flrt_json_data[ $sid ]['allEntities'] ) ) {
                continue;
            }
            foreach ( $flrt_json_data[ $sid ]['allEntities'] as $eName => $entity ) {
                $filterConf = is_object( $entity )
                    ? ( isset( $entity->filter ) ? (array) $entity->filter : [] )
                    : ( isset( $entity['filter'] ) ? (array) $entity['filter'] : [] );
                unset( $filterConf['values'] );
                $configFingerprint[ $sid ][ $eName ] = $filterConf;
            }
        }

        $hash = md5( implode( '|', [
            FLRT_PLUGIN_VER,
            FLRT_CACHE_FORMAT_SUFFIX,
            (string) get_option( 'flrt_json_blob_ver', 1 ),
            // The same DB serves different filter data in free and PRO (PRO-only
            // fields are gated at read time) — the modes must not share a file
            defined( 'FLRT_FILTERS_PRO' ) ? 'pro' : 'free',
            $group,
            $lang,
            count( $universe ),
            md5( implode( ',', $universe ) ),
            md5( (string) wp_json_encode( $configFingerprint ) ),
        ] ) );

        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) ) {
            return false;
        }

        $fileName = 'filters-' . $group . '-' . $hash . '.json';
        $dir      = trailingslashit( $uploads['basedir'] ) . 'flrt-cache';
        $file     = $dir . '/' . $fileName;
        $url      = trailingslashit( $uploads['baseurl'] ) . 'flrt-cache/' . $fileName;

        if ( file_exists( $file ) ) {
            return $url;
        }

        if ( ! wp_mkdir_p( $dir ) ) {
            return false;
        }

        if ( ! file_exists( $dir . '/index.php' ) ) {
            @file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" );
        }

        $encoded = wp_json_encode( $this->normalizeJsonBlob( $flrt_json_data ) );
        if ( ! $encoded ) {
            return false;
        }

        // Write to a temp name first: a concurrent request must never fetch
        // a half-written multi-MB file
        $tmp = $file . '.' . wp_generate_password( 8, false ) . '.tmp';
        if ( @file_put_contents( $tmp, $encoded ) === false ) {
            return false;
        }
        if ( ! @rename( $tmp, $file ) ) {
            @unlink( $tmp );
            return false;
        }

        return $url;
    }

    /**
     * Strips/zeroes the page-scoped leaves so that every page of the set
     * group produces an identical blob: filteredAllPostsIds and the current
     * result counts live inline; cross_count, current range bounds and
     * per-bucket counts are recomputed client-side on the first recount and
     * are never read from the JSON before that.
     */
    private function normalizeJsonBlob( $flrt_json_data )
    {
        // Entities are objects — one encode/decode round-trip detaches a
        // plain array tree we can normalize without touching the originals
        $data = json_decode( wp_json_encode( $flrt_json_data ), true );

        $rangeLeaves = [ 'min', 'max', 'from', 'to', 'time_from', 'time_to' ];

        foreach ( $data as $key => &$setData ) {
            if ( ! is_numeric( $key ) ) {
                continue;
            }

            unset( $setData['filteredAllPostsIds'], $setData['totalFilteredCount'] );

            if ( empty( $setData['allEntities'] ) || ! is_array( $setData['allEntities'] ) ) {
                continue;
            }

            foreach ( $setData['allEntities'] as &$entity ) {
                unset( $entity['filter']['values'] );

                if ( array_key_exists( 'new_date_query', $entity ) ) {
                    $entity['new_date_query'] = [];
                }

                foreach ( $rangeLeaves as $leaf ) {
                    if ( array_key_exists( $leaf, $entity ) ) {
                        $entity[ $leaf ] = false;
                    }
                }

                if ( empty( $entity['items'] ) || ! is_array( $entity['items'] ) ) {
                    continue;
                }

                foreach ( $entity['items'] as &$item ) {
                    if ( array_key_exists( 'cross_count', $item ) ) {
                        $item['cross_count'] = 0;
                    }
                    if ( array_key_exists( 'wp_queried', $item ) ) {
                        $item['wp_queried'] = false;
                    }
                    foreach ( $rangeLeaves as $leaf ) {
                        if ( array_key_exists( $leaf, $item ) ) {
                            $item[ $leaf ] = false;
                        }
                    }
                    if ( ! empty( $item['range_list_input'] ) && is_array( $item['range_list_input'] ) ) {
                        foreach ( $item['range_list_input'] as $bucket => $count ) {
                            if ( is_numeric( $count ) ) {
                                $item['range_list_input'][ $bucket ] = 0;
                            }
                        }
                    }
                }
                unset( $item );
            }
            unset( $entity );
        }
        unset( $setData );

        // These stay inline (page-scoped or tiny site config)
        unset( $data['domain'], $data['trailingSlash'], $data['variationsAsProducts'], $data['wpcFilterPermalinks'], $data['wpcFilterPermalinksNum'], $data['wpcFilterEntitiesWithoutSlug'] );

        return $data;
    }

    public function removeApplyButtonOrderField( &$set_settings_fields )
    {
        unset( $set_settings_fields['apply_button_menu_order'], $set_settings_fields['search_field_menu_order'] );
    }

    public function handleFilterSetFieldsVisibility( $filterSetFields )
    {
        if( isset( $filterSetFields['use_apply_button']['value'] ) && $filterSetFields['use_apply_button']['value'] === 'yes' ) {
            $filterSetFields['apply_button_text']['additional_class'] = 'wpc-opened';
            $filterSetFields['reset_button_text']['additional_class'] = 'wpc-opened';
        }

        if ( isset( $filterSetFields['use_search_field']['value'] ) && $filterSetFields['use_search_field']['value'] === 'yes' ) {
            $filterSetFields['search_field_placeholder']['additional_class'] = 'wpc-opened';
            $filterSetFields['search_field_label']['additional_class']       = 'wpc-opened';
        }

        if (isset( $filterSetFields['horizontal_view']['value'] ) && $filterSetFields['horizontal_view']['value'] === 'yes'){
            $filterSetFields['horizontal_view_column']['additional_class'] = 'wpc-opened';
        }

        return $filterSetFields;
    }

    public function disableCacheProductsShortcode( $out )
    {
        $wpManager          = Container::instance()->getWpManager();
        $is_filter_request  = $wpManager->getQueryVar('wpc_is_filter_request');
        $thePost            = Container::instance()->getThePost();
        $action             = isset( $thePost['action'] ) ? $thePost['action'] : false;

        // wpc_get_wp_queries - action to get WP_Queries on a page
        if( isset( $out['cache'] ) && ( $is_filter_request || $action === 'wpc_get_wp_queries' ) ){
            $out['cache'] = false;
        }

        return $out;
    }

    public function showCombinedFields( &$filter, $field_key )
    {
        $includeExclude = flrt_extract_vars( $filter, array('exclude', 'include') );
        if( $includeExclude ):

            ?><tr class="<?php echo esc_attr( flrt_filter_row_class( $includeExclude['exclude'] ) ); ?>"<?php flrt_maybe_hide_row( $includeExclude['exclude'] ); ?>><?php

            flrt_include_admin_view('filter-field-label', array(
                    'field_key'  => 'exclude',
                    'attributes' => $includeExclude['exclude']
                )
            );
            ?>
            <td class="wpc-filter-field-td wpc-filter-field-include-exclude-td">
                <div class="wpc-filter-field-include-exclude-wrap">
                    <div class="wpc-field-wrap wpc-field-exclude-wrap <?php if( isset( $includeExclude['exclude']['id'] ) ){ echo esc_attr( $includeExclude['exclude']['id'] ); } ?>-wrap">
                        <?php echo flrt_render_input( $includeExclude['exclude'] ); // Already escaped in function ?>
                        <?php do_action('wpc_after_filter_input', $includeExclude['exclude'] ); ?>
                    </div>
                    <div class="wpc-field-wrap wpc-field-include-wrap <?php if( isset( $includeExclude['include']['id'] ) ){ echo esc_attr( $includeExclude['include']['id'] ); } ?>-wrap">
                        <?php echo flrt_render_input( $includeExclude['include'] ); // Already escaped in function ?>
                        <?php do_action('wpc_after_filter_input', $includeExclude['include'] ); ?>
                    </div>
                </div>
            </td>
            </tr><?php

        endif;

        if ( $field_key === 'min_num_label' ) {
            $min_max_num_labels = flrt_extract_vars( $filter, array( 'min_num_label', 'max_num_label' ) );

            if( $min_max_num_labels ) :
                $min_field_id = $max_field_id = '';

                if (isset($min_max_num_labels['min_num_label']['id'])) {
                    $min_field_id = esc_attr($min_max_num_labels['min_num_label']['id']);
                }
                if( isset( $min_max_num_labels['max_num_label']['id'] ) ){
                    $max_field_id =  esc_attr( $min_max_num_labels['max_num_label']['id'] );
                }

                if( isset($min_max_num_labels['min_num_label']['value']) ){
                    $min_max_num_labels['min_num_label']['data-caret'] = mb_strlen( $min_max_num_labels['min_num_label']['value'] ) + 1;
                }

                if( isset($min_max_num_labels['max_num_label']['value']) ){
                    $min_max_num_labels['max_num_label']['data-caret'] = mb_strlen( $min_max_num_labels['max_num_label']['value'] ) + 1;
                }

                ?><tr class="<?php echo esc_attr( flrt_filter_row_class( $min_max_num_labels['min_num_label'] ) ); ?>"<?php flrt_maybe_hide_row( $min_max_num_labels['min_num_label'] ); ?>><?php

                flrt_include_admin_view('filter-field-label', array(
                        'field_key'  => 'min_num_label',
                        'attributes' => $min_max_num_labels['min_num_label']
                    )
                );
                ?>
                <td class="wpc-filter-field-td wpc-filter-field-min-max-labels-td">
                    <div class="wpc-filter-field-min-max-labels-wrap">
                        <div class="wpc-field-wrap wpc-field-min_num_label-wrap <?php echo $min_field_id; ?>-wrap">
                            <?php echo flrt_render_input( $min_max_num_labels['min_num_label'] ); // Already escaped in function ?>
                            <?php do_action('wpc_after_filter_input', $min_max_num_labels['min_num_label'] ); ?>
                        </div>
                        <div class="wpc-field-wrap wpc-field-max_num_label-wrap <?php echo $max_field_id; ?>-wrap">
                            <?php echo flrt_render_input( $min_max_num_labels['max_num_label'] ); // Already escaped in function ?>
                            <?php do_action('wpc_after_filter_input', $min_max_num_labels['max_num_label'] ); ?>
                        </div>
                        <p class="description"><?php echo wp_kses(
                                sprintf( __('For example, "Price from <span class="wpc-variable-inserter" data-field="%s" title="Click to insert the variable">{value}</span> $" will be displayed as "Price from 150 $"', 'filter-everything'), $min_field_id ),
                                array( 'span' => array(
                                        'class' => true,
                                        'data-field' => true,
                                        'title' => true,
                                ) )
                            ) ?></p>
                    </div>
                </td>
                </tr><?php

            endif;
        }
    }

    public function addSearchArgsToWpQuery( $wp_query )
    {
        if ( isset( $_GET['srch'] ) && $_GET['srch']  ) {
            $keyword = filter_input( INPUT_GET, 'srch', FILTER_DEFAULT );
            $wp_query->set( 's', $keyword );
        }

        return $wp_query;
    }

    public function addSkuSearchSql( $search, $wp_query )
    {
        if( $wp_query->get('flrt_query_hash') || $wp_query->get('flrt_query_clone') ){

            if ( $wp_query->get('wc_query') === 'product_query' || $wp_query->get('post_type') === 'product' /* || $wp_query->get('post_type') === 'product_variation' */ ) {
                global $wpdb;

                $product_id = wc_get_product_id_by_sku( $wp_query->get('s') );
                if ( ! $product_id ) {
                    return $search;
                }

                $product = wc_get_product( $product_id );
                if ( $product->is_type( 'variation' ) ) {
                    $product_id = $product->get_parent_id();
                }

                $search = str_replace( 'AND (((', "AND (({$wpdb->posts}.ID IN (" . $product_id . ")) OR ((", $search );
                return $search;
            }

        }
        return $search;
    }

    public function postDateWhere( $where, $wp_query )
    {   global $wpdb;
        $sql = [];
        $operator = '';
        $wpc_date_query = $wp_query->get( 'wpc_date_query' );

        if( ! empty( $wpc_date_query ) && is_array( $wpc_date_query ) ) {
                foreach ( $wpc_date_query as $edge => $value ) {
                    if( $edge === 'from' ) {
                        $operator = '>=';
                    } elseif ( $edge === 'to' ) {
                        $operator = '<=';
                    }

                    $sql[] = $wpdb->prepare( "AND {$wpdb->posts}.post_date {$operator} %s ", $value );

                }

                $where = implode( ' ', $sql ) . $where;
        }

        return $where;
    }

    public function showElementPickerPanel()
    {
        $getData  = Container::instance()->getTheGet();

        $html = sprintf(
                '<div id="wpc-choose-selector" data-set-id="%1$s">
            <div id="wpc-choose-id-css" class="wpc-choose-block">
               <span>%2$s</span>
               <input id="wpc-chooses-html-id">
            </div>
            <div id="wpc-choose-class-css" class="wpc-choose-block">
                <span>%3$s</span>
                <input id="wpc-chooses-html-css">
            </div>
            <div id="wpc-choose-empty" class="wpc-choose-block">
              
               <span id="wpc-chooses-empty-element">%4$s</span>
            </div>
            <div id="wpc-choose-selector-buttons">
                <button id="wpc-choose-another-selector">%5$s</button>
                <button id="wpc-choose-save-btn">%6$s</button>
            </div>
        </div>',
                !(empty($getData['flrt_set_id'])) ? esc_attr($getData['flrt_set_id']) : 'global_posts_container',
                esc_html__( 'Chosen ID: ', 'filter-everything' ),
                esc_html__( 'Chosen Class: ', 'filter-everything' ),
                esc_html__( 'The element does not have any CSS selectors', 'filter-everything' ),
                esc_html__( 'Choose another', 'filter-everything' ),
                esc_html__( 'Save', 'filter-everything' )
        );

        // Localize script with nonce and AJAX URL
        wp_localize_script('wpc-element-picker', 'wpcElementPickerData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'error_saving_selector' => esc_html__('Error saving selector', 'filter-everything'),
                'no_element_selected' => esc_html__('Error: No element selected', 'filter-everything'),
                'failed_to_save_selector' => esc_html__('Error: Failed to save selector. Please try again.', 'filter-everything'),
                'saving' => esc_html__('Saving...', 'filter-everything'),
                'save' => esc_html__('Save', 'filter-everything'),
        ));

        echo $html;
    }
}
