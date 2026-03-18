<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

function flrt_is_woocommerce()
{
    if( class_exists('WooCommerce') ){
        return true;
    }
    return false;
}

function flrt_is_acf()
{
    if( class_exists( 'ACF' ) ){
        return true;
    }
    return false;
}

function flrt_get_mobile_width(){
    return apply_filters( 'wpc_mobile_width', 768 );
}

/**
 * @feature for other popular themes there is possibility to add action to hook get_template_part_{$slug}
 * But it seems we need to detect what current theme is enabled
 */
if( ! function_exists('flrt_wp') ){

    function flrt_wp(){
        $theme_dependencies = flrt_get_theme_dependencies();

        if( flrt_get_option('mobile_filter_settings') === 'show_bottom_widget' ){

            if( flrt_get_experimental_option('disable_buttons') !== 'on' ) {

                if (flrt_is_woocommerce()) { // It means WooCommerce installed

                    if( is_woocommerce() ){ // It means is a WooCommerce page
                        add_action('woocommerce_before_shop_loop', 'flrt_filters_button', 5);
                        add_action('woocommerce_no_products_found', 'flrt_filters_button', 5);
                    }else{
                        if (isset($theme_dependencies['button_hook']) && is_array($theme_dependencies['button_hook'])) {
                            foreach ($theme_dependencies['button_hook'] as $button_hook) {
                                add_action($button_hook, 'flrt_filters_button', 15);
                            }
                        }
                    }

                } else {
                    if (isset($theme_dependencies['button_hook']) && is_array($theme_dependencies['button_hook'])) {
                        foreach ($theme_dependencies['button_hook'] as $button_hook) {
                            add_action($button_hook, 'flrt_filters_button', 15);
                        }
                    }
                }
            }

        }

        // Add selected terms to the top
        $chips_hooks  = flrt_get_option('show_terms_in_content', []);

        if( $chips_hooks ){
            if( is_array( $chips_hooks ) && ! empty( $chips_hooks ) ){
                foreach ( $chips_hooks as $hook ){
                    add_action( $hook, 'flrt_add_selected_terms_above_the_top' );
                }
            }
        }
    }
}

function wpc_add_selected_terms_above_the_top(){
    _deprecated_function( __FUNCTION__, '1.0.7', 'flrt_add_selected_terms_above_the_top()' );
    flrt_add_selected_terms_above_the_top();
}

function flrt_add_selected_terms_above_the_top()
{
    flrt_show_selected_terms(true);
}

function flrt_get_theme_dependencies(){
    $current_theme = strtolower( get_template() );

    $theme_dependencies = array(
        'storefront'        => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#96588a',
            'button_hook'       => array('storefront_content_top'),
            'chips_hook'        => array('storefront_loop_before')
        ),
        'hello-elementor' => array(
            'posts_container'   => '.page-content',
            'sidebar_container' => '',
            'primary_color'     => '#CC3366',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'astra' => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#0274be',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentyeleven' => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#1982d1',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentytwelve' => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#21759b',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentyfourteen' => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#24890d',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentyfifteen'     => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary', // There are problems on mobile
            // because of sidebar is hidden on mobile until user open header menu.
            'primary_color'     => '#333333',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentysixteen'     => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#007acc',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentyseventeen'   => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#222222',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentynineteen'    => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#0073aa',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentytwenty'      => array(
            'posts_container'   => '#site-content',
            'sidebar_container' => '',
            'primary_color'     => '#cd2653',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'twentytwentyone'      => array(
            'posts_container'   => '#content',
            'sidebar_container' => '.widget-area',
            'primary_color'     => '#28303d',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'popularfx'         => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#0072b7',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'oceanwp'           => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#right-sidebar',
            'primary_color'     => '#13aff0',
            'button_hook'       => array('ocean_before_content'),
            'chips_hook'        => array('ocean_before_content')
        ),
        'kadence'           => array(
            'posts_container'   => '#main',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#3182ce',
            'button_hook'       => array('kadence_before_main_content'),
            'chips_hook'        => array('kadence_before_main_content')
        ),
        'zakra'             => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#269bd1',
            'button_hook'       => array('zakra_before_posts_the_loop'),
            'chips_hook'        => array('zakra_before_posts_the_loop')
        ),
        'neve'              => array(
            'posts_container'   => '.nv-index-posts',
            'sidebar_container' => '#secondary', // '.nv-sidebar-wrap',
            'primary_color'     => '#393939',
            'button_hook'       => array('neve_before_loop'),
            'chips_hook'        => array('neve_before_loop')
        ),
        'hestia'            => array(
            'posts_container'   => '#woo-products-wrap',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#e91e63',
            'button_hook'       => array('hestia_before_index_posts_loop'),
            'chips_hook'        => array('hestia_before_index_posts_loop')
        ),
        'colibri-wp'        => array(
            'posts_container'   => '.main-row-inner .h-col:not(.colibri-sidebar)',
            'sidebar_container' => '.colibri-sidebar',
            'primary_color'     => '#03a9f4',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'teluro'            => array(
            'posts_container'   => '.main-row-inner .h-col:not(.colibri-sidebar)',
            'sidebar_container' => '.colibri-sidebar',
            'primary_color'     => '#f26559',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'numinous'          => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#f4ab00',
            'button_hook'       => array('numinous_content'),
            'chips_hook'        => ''
        ),
        'sydney'            => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#d65050',
            'button_hook'       => array('sydney_before_content'),
            'chips_hook'        => ''
        ),
        // Commercial themes
        'avada' => array(
            'posts_container'   => '#content',
            'sidebar_container' => '#sidebar',
            'primary_color'     => '#65bc7b',
            'button_hook'       => array('avada_before_main_container'),
            'chips_hook'        => ''
        ),
        'generatepress'     => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '.sidebar',
            'primary_color'     => '#1e73be',
            'button_hook'       => array('generate_before_main_content'),
            'chips_hook'        => array('generate_before_main_content')
        ),
        'the7'              => array(
            'posts_container'   => '#content',
            'sidebar_container' => '#sidebar',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'dt-the7'           => array(
            'posts_container'   => '#content',
            'sidebar_container' => '#sidebar',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'flatsome'          => array(
            'posts_container'   => '.shop-container',
            'sidebar_container' => '#shop-sidebar',
            'primary_color'     => '#446084',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'betheme'           => array(
            'posts_container'   => '.sections_group',
            'sidebar_container' => '.sidebar',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'bridge'            => array(
            'posts_container'   => '.container .column1',
            'sidebar_container' => '',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'impreza'           => array(
            'posts_container'   => '#page-content .w-grid-list',
            'sidebar_container' => '',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'enfold'            => array(
            'posts_container'   => 'main.content',
            'sidebar_container' => '',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'porto'             => array(
            'posts_container'   => '#content',
            'sidebar_container' => '',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'genesis'             => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'divi' => array(
            'posts_container'   => '#primary',
            'sidebar_container' => '#secondary',
            'primary_color'     => '',
            'button_hook'       => '',
            'chips_hook'        => ''
        ),
        'woodmart' => array(
            'posts_container'   => '.site-content',
            'sidebar_container' => '#secondary',
            'primary_color'     => '#83b735',
            'button_hook'       => '',
            'chips_hook'        => array( 'woodmart_shop_filters_area', 'woodmart_main_loop')
        )
    );

    $theme_dependencies = apply_filters( 'wpc_theme_dependencies', $theme_dependencies );

    if( isset( $theme_dependencies[ $current_theme ] ) ){
        return $theme_dependencies[ $current_theme ];
    }

    return array(
        'posts_container'   => false,
        'sidebar_container' => false,
        'primary_color'     => false,
        'button_hook'       => array(),
        'chips_hook'        => array()
    );
}

add_action( 'wp', 'flrt_wp' );

if( ! function_exists('flrt_set_posts_container') ){
    function flrt_set_posts_container( $theme_posts_container )
    {
        $theme_dependencies = flrt_get_theme_dependencies();

        if( isset( $theme_dependencies[ 'posts_container' ] ) ){
            return $theme_dependencies[ 'posts_container' ];
        }

        return $theme_posts_container;
    }
}

function flrt_set_theme_color($color ){

    $theme_dependencies = flrt_get_theme_dependencies();

    if( $theme_dependencies['primary_color'] ){
        $color = $theme_dependencies['primary_color'];
    }

    return $color;
}

if( ! function_exists('flrt_init') ){
    function flrt_init()
    {
        // Set correct theme posts container
        add_filter('wpc_theme_posts_container', 'flrt_set_posts_container');

        // Set correct theme color
        add_filter('wpc_theme_color', 'flrt_set_theme_color');

        //
        if( flrt_is_acf() ) {
            add_filter( 'wpc_pre_save_filter', 'flrt_maybe_acf_field' );
            add_filter( 'wpc_default_sorting_terms', 'flrt_acf_terms_order', 10, 2 );
        }
    }
}
add_action('init', 'flrt_init');

function flrt_wpml_active(){
    if( defined('WPML_PLUGIN_BASENAME') ){
        return true;
    }
    return false;
}

add_action( 'elementor/editor/before_enqueue_scripts', 'flrt_include_elementor_script' );
function flrt_include_elementor_script(){
    $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
    $ver    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? rand(0, 1000) : FLRT_PLUGIN_VER;
    wp_enqueue_script('wpc-widgets', FLRT_PLUGIN_DIR_URL . 'assets/js/wpc-widgets' . $suffix . '.js', ['jquery', 'jquery-ui-sortable'], $ver, true );
    wp_enqueue_style('wpc-widgets', FLRT_PLUGIN_DIR_URL . 'assets/css/wpc-widgets' . $suffix . '.css', [], $ver );

    $l10n = array(
        'wpcItemNum'  => esc_html__( 'Item #', 'filter-everything')
    );
    wp_localize_script( 'wpc-widgets', 'wpcWidgets', $l10n );
}

/*
 * Polylang compatibility functions
 * */
function flrt_maybe_has_translation( $post_id, $lang = '' ){

    if( function_exists( 'pll_get_post' ) ){
        $translation_post_id = pll_get_post( $post_id, $lang );
        if( $translation_post_id ){
            $post_id = $translation_post_id;
        }
    }

    return $post_id;
}

function flrt_pll_pro_active(){
    if ( defined('POLYLANG_PRO') ) {
        return true;
    }
    return false;
}
// Allow Filter Sets to be translatable if Polylang PRO activated
// This make sense for Filter Sets with common locations only
add_action( 'after_setup_theme', 'flrt_pll_init', 20 );
function flrt_pll_init(){
    if( flrt_pll_pro_active() && defined('FLRT_ALLOW_PLL_TRANSLATIONS') && FLRT_ALLOW_PLL_TRANSLATIONS ){
        add_filter( 'pll_get_post_types', 'flrt_add_cpt_to_pll', 10, 2 );
    }
}

function flrt_add_cpt_to_pll( $post_types, $is_settings ) {
    if ( $is_settings ) {
        unset( $post_types[ FLRT_FILTERS_SET_POST_TYPE ], $post_types[ FLRT_FILTERS_SET_POST_TYPE ] );
    } else {
        $post_types[ FLRT_FILTERS_SET_POST_TYPE ] = FLRT_FILTERS_SET_POST_TYPE;
        if( defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO ) {
            $post_types[ FLRT_SEO_RULES_POST_TYPE ] = FLRT_SEO_RULES_POST_TYPE;
        }
    }
    return $post_types;
}

/**
 * Adds compatibility the Price filter with multi-currency plugins WOOCS and CURCY
 * @since  1.7.12
 */
add_action('init', 'flrt_add_currencies_support');
function flrt_add_currencies_support() {
    if ( flrt_is_woocommerce() ) {
        // For the FOX - Currency Switcher Professional for WooCommerce
        if ( defined( 'WOOCS_PLUGIN_NAME' ) && WOOCS_PLUGIN_NAME ) {
            // Converts values into selected currency. Visible in the range slider form
            add_filter( 'wpc_set_num_shift', 'flrt_set_woocs_shift', 10, 2 );
            function flrt_set_woocs_shift( $value, $entity_name ) {
                global $WOOCS;

                if ( $entity_name === '_price' ) {
                    if ( property_exists( $WOOCS, 'default_currency' ) && property_exists( $WOOCS, 'current_currency' ) ) {
                        if ( ! $WOOCS->default_currency || ! $WOOCS->current_currency ) {
                            return $value;
                        }

                        if ( $WOOCS->default_currency !== $WOOCS->current_currency && method_exists( $WOOCS, 'convert_from_to_currency' ) ) {
                            $value = $WOOCS->convert_from_to_currency( $value, $WOOCS->default_currency, $WOOCS->current_currency );
                        }
                    }
                }

                return $value;
            }

            // Converts values back to default currency for WP_Query
            add_filter( 'wpc_unset_num_shift', 'flrt_unset_woocs_shift', 10, 2 );
            function flrt_unset_woocs_shift( $value, $entity_name ) {
                global $WOOCS;

                if ( $entity_name === '_price' ) {
                    if ( property_exists( $WOOCS, 'default_currency' ) && property_exists( $WOOCS, 'current_currency' ) ) {
                        if ( ! $WOOCS->default_currency || ! $WOOCS->current_currency ) {
                            return $value;
                        }

                        $precision = 2;
                        if( method_exists( $WOOCS, 'get_currency_price_num_decimals' ) ) {
                            $precision = $WOOCS->get_currency_price_num_decimals( $WOOCS->current_currency, $WOOCS->price_num_decimals );
                        }

                        if ( $WOOCS->default_currency !== $WOOCS->current_currency && method_exists( $WOOCS, 'convert_from_to_currency' ) ) {
                            $value = $WOOCS->convert_from_to_currency( $value, $WOOCS->current_currency, $WOOCS->default_currency );
                            $value = round( $value, $precision );
                        }
                    }
                }

                return $value;
            }
        }

        // For the CURCY - Multi Currency for WooCommerce
        if ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) && WOOMULTI_CURRENCY_F_VERSION ) {
            // Converts values into selected currency. Visible in the range slider form
            add_filter( 'wpc_set_num_shift', 'flrt_set_curcy_shift', 10, 2 );
            function flrt_set_curcy_shift( $value, $entity_name ) {

                if ( $entity_name === '_price' ) {
                    if ( method_exists( 'WOOMULTI_CURRENCY_F_Data', 'get_ins' ) && function_exists('wmc_get_price' ) ) {
                        $settings = WOOMULTI_CURRENCY_F_Data::get_ins();

                        if ( ! method_exists( $settings, 'get_current_currency' ) || ! method_exists( $settings, 'get_default_currency' ) ) {
                            return $value;
                        }

                        $currency           = $settings->get_current_currency();
                        $default_currency   = $settings->get_default_currency();

                        if ( $currency !== $default_currency ) {
                            $value = wmc_get_price( $value, $currency );
                        }
                    }
                }

                return $value;
            }

            // Converts values back to default currency for WP_Query
            add_filter( 'wpc_unset_num_shift', 'flrt_unset_curcy_shift', 10, 2 );
            function flrt_unset_curcy_shift( $value, $entity_name ) {

                if ( $entity_name === '_price' ) {
                    if ( method_exists( 'WOOMULTI_CURRENCY_F_Data', 'get_ins' ) && function_exists('wmc_revert_price' ) ) {
                        $settings = WOOMULTI_CURRENCY_F_Data::get_ins();

                        if ( ! method_exists( $settings, 'get_current_currency' ) || ! method_exists( $settings, 'get_default_currency' ) ) {
                            return $value;
                        }

                        $currency           = $settings->get_current_currency();
                        $default_currency   = $settings->get_default_currency();

                        if ( $currency !== $default_currency ) {
                            $value = wmc_revert_price( $value );
                        }
                    }
                }

                return $value;
            }
        }
    }
}

/**
 * Removes WooCommerce Product Query post clauses for the price filter
 * @since  1.7.12
 */
function flrt_remove_product_query_post_clauses( $wp_query, $WC_query ) {
    $wpManager = \FilterEverything\Filter\Container::instance()->getWpManager();

    if ( $wpManager->getQueryVar( 'wpc_is_filter_request' ) ) {
        remove_filter('posts_clauses', array( $WC_query, 'product_query_post_clauses' ), 10, 2);
    }

    return $wp_query;
}

add_filter('wpc_check_broken_filter', function ($is_broken, $query) {
    $detector_class = 'FilterEverything\\Filter\\WP_Query_Source_Detector';
    $is_allowed_method = 'is_allowed';
    $source_var = 'flrt_detected_source';

    if (defined('FLRT_FILTERS_PRO')) {
        if (defined('FLRT_PRO_BUILDER_KEY')) {
            $builder_key = apply_filters('wpc_builder_key_pro', $query->get($source_var), constant('FLRT_PRO_BUILDER_KEY'));

            if ($detector_class::$is_allowed_method($builder_key)) {
                return false;
            }
        }
    }

    if (!defined('FLRT_FILTERS_PRO')) {
        $builder_key = apply_filters('wpc_builder_key', $query->get($source_var), $detector_class::$builder_key);

        if ($detector_class::$is_allowed_method($builder_key)) {
            return false;
        }
    }

    return true;
}, 10, 2);

function flrt_is_dokan() {
    return function_exists('dokan');
}

function flrt_maybe_acf_field( $filter ){
    if( $filter['entity'] === 'post_meta' ) {
        global $wpdb;
        // Try to check if this is ACF field
        $sql[] = "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}";
        $sql[] = "WHERE {$wpdb->posts}.post_excerpt = %s";
        $sql[] = "AND {$wpdb->posts}.post_type = %s";
        $sql[] = "ORDER BY {$wpdb->posts}.ID ASC";

        $sql     = implode(' ', $sql );
        $sql     = $wpdb->prepare( $sql, $filter['e_name'], 'acf-field' );
        $results = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! empty( $results ) ) {
            $ids = [];
            foreach ( $results as $single_result ) {
                if( isset( $single_result['ID'] ) ){
                    $ids[] = $single_result['ID'];
                }
            }
            $acf_field_ids = implode( ',', $ids );

            if( ! empty( $acf_field_ids ) ){
                $filter['acf_fields'] = $acf_field_ids;
            }
        }
    }

    return $filter;
}

function flrt_acf_terms_order( $entity_items, $filter ){

    if( isset( $filter['acf_fields'] ) && $filter['acf_fields'] !== '' ) {
        global $wpdb;
        // Here we have to get ACF fields by their IDs and sort terms in according to
        // the order in the field.
        $sql = [];
        $acf_field_ids = preg_replace( '/^[\d]\,/', '', $filter['acf_fields'] );
        $sql[] = "SELECT {$wpdb->posts}.post_content FROM {$wpdb->posts}";
        $sql[] = "WHERE {$wpdb->posts}.ID IN( {$acf_field_ids} )";
        $sql[] = "ORDER BY {$wpdb->posts}.ID ASC";
        $sql   = implode(' ', $sql );

        $results = $wpdb->get_results( $sql, ARRAY_A );

        if( empty( $results ) ) {
            return $entity_items;
        }

        $field_terms = [];
        foreach ( $results as $acf_field ){
            if( isset( $acf_field['post_content'] ) ) {
                $field_options = maybe_unserialize( $acf_field['post_content'] );

                if( isset( $field_options['choices'] ) && is_array( $field_options['choices'] ) ){
                    foreach ( $field_options['choices'] as $value => $label ){
                        if( ! isset( $field_terms[$value] ) ) {
                            $field_terms[$value] = $label;
                        }
                    }
                }
            }
        }

        $current_items = $entity_items;
        $sorted_items  = [];

        foreach ( $field_terms as $tslug => $tvalue ) {
            $tslug = sanitize_title( $tslug );

            if( isset( $current_items[ $tslug ] ) ) {
                $term_object = $current_items[ $tslug ];
                $term_object->name = $tvalue;
                $sorted_items[ $tslug ] = $term_object;
                unset( $current_items[ $tslug ] );
            }
        }

        if( ! empty( $current_items ) ){
            foreach ( $current_items as $slug => $item ){
                $sorted_items[$slug] = $item;
            }
        }
        $entity_items = $sorted_items;
    }

    return $entity_items;
}

/**
 * Adds correct pagination URLs to the Load more/Infinite scroll button for Elementor posts block
 */
add_filter( 'elementor/widget/render_content', 'flrt_elementor_load_more_anchor', 10, 2 );
function flrt_elementor_load_more_anchor( $widget_content, $module ){
    // Nothing is needed if there is no Filter request
    if( ! flrt_is_filter_request() ){
        return $widget_content;
    }

    if( $module instanceof ElementorPro\Modules\Posts\Widgets\Posts ){
        global $wp_rewrite;

        $urlManager = \FilterEverything\Filter\Container::instance()->getUrlManager();
        $current_page = $module->get_current_page();
        $next_page = intval( $current_page ) + 1;
        $data_next_page = $module->get_wp_link_page( $next_page );
        $rewrite = $wp_rewrite->wp_rewrite_rules();

        if ( defined('FLRT_PERMALINKS_ENABLED') && FLRT_PERMALINKS_ENABLED ) {
            // This is ok when permalinks are enabled.
            $data_next_page = str_replace( $urlManager->removePaginationBase( $data_next_page ), $urlManager->getFormActionOrFullPageUrl(), $data_next_page );
            $uri_components = explode( "?", $urlManager->getFormActionOrFullPageUrl( true ) );
            if( isset( $uri_components[1] ) ){
                $data_next_page = $data_next_page . '?'.$uri_components[1];
            }
        } else if ( ! FLRT_PERMALINKS_ENABLED && ! empty( $rewrite ) ) {
            // WordPress permalinks are Enabled, but Filter Everything permalinks are Disabled
            $uri_components = explode( "?", $urlManager->getFormActionOrFullPageUrl( true ) );
            // If URI part after ? exists
            if( isset( $uri_components[1] ) ){
                $data_next_page = $data_next_page . '?'.$uri_components[1];
            }
        } else {
            // No permalinks at all
            $current_page_url = $urlManager->getFormActionOrFullPageUrl( true );
            $url_parts = parse_url( $data_next_page );
            if( isset( $url_parts['query'] ) ){
                parse_str( $url_parts['query'], $params );
                if( isset( $params['page'] ) && $params['page'] ) {
                    $data_next_page = flrt_add_query_arg( 'page', $params['page'], $current_page_url );
                }
            }
        }

        $widget_content = preg_replace('%data-next-page\="[^"]+"%', 'data-next-page="' . $data_next_page . '"', $widget_content);
    }

    return $widget_content;
}

add_filter( 'wpc_remove_pagination_base', 'flrt_remove_pagination_base' );
function flrt_remove_pagination_base( $url ){
    global $wp_rewrite;

    $rewrite = $wp_rewrite->wp_rewrite_rules();
    $url = ( ! empty( $rewrite ) ) ? user_trailingslashit( $url ) : rtrim( $url, '/' ) . '/';

    return $url;
}

function flrt_is_woo_discount_rules()
{
    if( flrt_is_woocommerce() &&
        class_exists('Wdr\App\Helpers\Woocommerce') &&
        class_exists('Wdr\App\Controllers\Base') &&
        class_exists('Wdr\App\Helpers\Rule') &&
        class_exists('Wdr\App\Controllers\ManageDiscount') &&
        class_exists('Wdr\App\Controllers\DiscountCalculator') &&
        class_exists('Wdr\App\Models\DBTable')
    )
    {
        $tb_table = new Wdr\App\Models\DBTable();
        $wdr_rules = $tb_table::getRules();
        if(!empty($wdr_rules)){
            $discount_types = [];
            foreach ($wdr_rules as $rule){
                $discount_types[] = $rule->discount_type;
            }
            $allowed_discount_types = ['wdr_simple_discount', 'wdr_bulk_discount', 'wdr_set_discount'];
            if(!empty(array_intersect($allowed_discount_types, $discount_types))){
                return true;
            }
        }
    }
    return false;
}

function flrt_is_elementor_active()
{
    if ( is_plugin_active( 'elementor/elementor.php' ) ) {
        return true;
    }
    return false;
}

if ( flrt_is_elementor_active() ) {
    function wpc_add_elementor_widget_categories( $elements_manager ) {
        $pro_text = (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO)
            ? ' ' .esc_html__('PRO', 'filter-everything')
            : '';

        $elements_manager->add_category(
            'filter-everything',
            [
                'title' => esc_html__( 'Filter Everything', 'filter-everything'  ) . $pro_text,
                'icon' => 'fa-solid fa-filter',
            ]
        );
    }
    add_action( 'elementor/elements/categories_registered', 'wpc_add_elementor_widget_categories' );

    function wpc_register_elementor_widget( $widgets_manager ) {

        flrt_include('src/Admin/Widgets/ElementorWidgets/ChipsElementorWidget.php');
        flrt_include('src/Admin/Widgets/ElementorWidgets/FiltersElementorWidget.php');
        flrt_include('src/Admin/Widgets/ElementorWidgets/SortingElementorWidget.php');

        $widgets_manager->register( new \FilterEverything\Filter\ChipsElementorWidget() );
        $widgets_manager->register( new \FilterEverything\Filter\FiltersElementorWidget() );
        $widgets_manager->register( new \FilterEverything\Filter\SortingElementorWidget() );

    }
    add_action( 'elementor/widgets/register', 'wpc_register_elementor_widget' );

    add_action( 'elementor/editor/after_enqueue_styles', function() {
        wp_enqueue_style(
            'filter-everything-elementor',
            FLRT_PLUGIN_DIR_URL . 'assets/css/elementor-icon.css'
        );
        $css = '
        .wpc-fe-icon {
            background: url(%s) no-repeat;
            background-size: auto;
            background-size: contain;
            display: inline-block;
            width: 28px;
            height: 28px;
            margin-bottom: -5px;
          }  
        
          .elementor-navigator__element-widget .wpc-fe-icon {
            width: 15px;
            height: 15px;
            margin-bottom: 0px;
          }';
        wp_add_inline_style( 'filter-everything-elementor', sprintf( $css, esc_attr(flrt_get_icon_svg()) ) );
    });

    add_filter('elementor/document/wrapper_attributes', function($attributes) {
        $attributes['wpc-filter-elementor-widget'] = true;
        return $attributes;
    });

    add_action( 'wp_footer', function() {
        if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
            return;
        }
        if( flrt_get_option('mobile_filter_settings') === 'show_bottom_widget' ){
            if( flrt_get_experimental_option('disable_buttons') !== 'on' ) {

        ?>

        <script>
            jQuery(function($) {
                $(window).on('elementor/frontend/init', function() {
                    elementorFrontend.hooks.addAction(
                        'frontend/element_ready/filter-everything-filters.default',
                        function($scope) {
                            let $el = $('[wpc-filter-elementor-widget="1"]');
                            if($el.length > 0){
                                $el.before(`<?php flrt_filters_button() ?>`);
                            }
                        }
                    );
                });
            });
        </script>
        <?php
            }
        }
    });
}

add_action('admin_enqueue_scripts', function () {
    $handle = 'filter-everything-menu-icon';
    wp_register_style($handle, false);
    wp_enqueue_style($handle);


    $css = <<<PHP_CSS
#toplevel_page_edit-post_type-filter-set .wp-menu-image{
    content: '';
    background-repeat: no-repeat;
    background-position: center;
    background-size: 20px;
    background-image: url('data:image/svg+xml,%3C%3Fxml version="1.0" encoding="utf-8"%3F%3E%3C!--  --%3E%3Csvg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 53 53" style="enable-background:new 0 0 53 53;" xml:space="preserve"%3E%3Cstyle type="text/css"%3E .st0%7Bdisplay:none;%7D .st1%7Bdisplay:inline;%7D .st2%7Bclip-path:url(%23SVGID_00000142154616827085436530000012698217856111301567_);%7D .st3%7Bfill:%23a7aaad;%7D .st4%7Bdisplay:none;fill:%23FFFFFF;%7D .st5%7Bfill:none;stroke:%23a7aaad;stroke-width:3;stroke-miterlimit:10;%7D .st6%7Bfill:none;stroke:%23a7aaad;stroke-width:2;stroke-miterlimit:10;%7D%0A%3C/style%3E%3Cg id="Layer_2_00000047770719710110742230000003923951626148849557_" class="st0"%3E%3Crect class="st1" width="53.1" height="53.1"/%3E%3C/g%3E%3Cg id="Layer_1_00000162333103265806981530000017146624247591674556_"%3E%3Cg%3E%3Cg%3E%3Cdefs%3E%3Cpath id="SVGID_1_" d="M0,0h53v53H0V0z M23.3,37.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5 C20.8,36.1,21.9,37.2,23.3,37.2z M33.4,27.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5S32,27.2,33.4,27.2z M23,22.8c1.6,0,2.9-1.3,2.9-2.9S24.6,17,23,17s-2.9,1.3-2.9,2.9C20.2,21.5,21.5,22.8,23,22.8z"/%3E%3C/defs%3E%3CclipPath id="SVGID_00000111880200341027563210000003406718579453459379_"%3E%3Cuse xlink:href="%23SVGID_1_" style="overflow:visible;"/%3E%3C/clipPath%3E%3Cg id="BarsClipped" style="clip-path:url(%23SVGID_00000111880200341027563210000003406718579453459379_);"%3E%3Cpath class="st3" d="M39.9,31.5L18,37.3c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C40.8,30.7,40.5,31.3,39.9,31.5z"/%3E%3Cpath class="st3" d="M38.1,24.6l-21.9,5.9c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C39,23.8,38.7,24.5,38.1,24.6z"/%3E%3Cpath class="st3" d="M36.2,17.9l-21.9,5.9c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C37.2,17.1,36.8,17.7,36.2,17.9z"/%3E%3C/g%3E%3C/g%3E%3C/g%3E%3Cpath class="st4" d="M23.3,37.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5C20.8,36.1,21.9,37.2,23.3,37.2z"/%3E%3Cpath class="st4" d="M33.4,27.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5S32,27.2,33.4,27.2z"/%3E%3Cpath class="st4" d="M23,22.8c1.6,0,2.9-1.3,2.9-2.9S24.6,17,23,17s-2.9,1.3-2.9,2.9C20.2,21.5,21.5,22.8,23,22.8z"/%3E%3Cpath class="st4" d="M23.9,38.2c-1.9,0.5-3.8-0.6-4.3-2.5c-0.5-1.9,0.6-3.8,2.5-4.3s3.8,0.6,4.3,2.5C26.9,35.8,25.8,37.7,23.9,38.2 z M22.6,33.2c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C24.3,33.5,23.4,33,22.6,33.2z"/%3E%3Cpath class="st4" d="M34.1,28.2c-1.9,0.5-3.8-0.6-4.3-2.5s0.6-3.8,2.5-4.3c1.9-0.5,3.8,0.6,4.3,2.5C37.1,25.8,36,27.7,34.1,28.2z M32.8,23.2c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C34.5,23.5,33.6,23,32.8,23.2z"/%3E%3Cpath class="st4" d="M24.2,23.5c-1.9,0.5-3.8-0.6-4.3-2.5s0.6-3.8,2.5-4.3s3.8,0.6,4.3,2.5S26.1,23,24.2,23.5z M22.9,18.6 c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C24.7,18.8,23.8,18.3,22.9,18.6z"/%3E%3Cpath class="st5" d="M26.5,51.5c13.8,0,25-11.2,25-25s-11.2-25-25-25s-25,11.2-25,25S12.7,51.5,26.5,51.5z"/%3E%3Cpath class="st4" d="M33.3,21h10c0.4,0,0.8-0.3,0.8-0.8s-0.3-0.8-0.8-0.8h-10C33.3,20.2,33.3,20.2,33.3,21z"/%3E%3C/g%3E%3Ccircle class="st6" cx="23.3" cy="20.1" r="2.5"/%3E%3Ccircle class="st6" cx="33.2" cy="24.8" r="2.5"/%3E%3Ccircle class="st6" cx="23" cy="34.8" r="2.5"/%3E%3C/svg%3E');
}
#toplevel_page_edit-post_type-filter-set:hover .wp-menu-image{
    background-image: url('data:image/svg+xml,%3C%3Fxml version="1.0" encoding="utf-8"%3F%3E%3C!--  --%3E%3Csvg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 53 53" style="enable-background:new 0 0 53 53;" xml:space="preserve"%3E%3Cstyle type="text/css"%3E .st0%7Bdisplay:none;%7D .st1%7Bdisplay:inline;%7D .st2%7Bclip-path:url(%23SVGID_00000142154616827085436530000012698217856111301567_);%7D .st3%7Bfill:%2372AEE6;%7D .st4%7Bdisplay:none;fill:%23FFFFFF;%7D .st5%7Bfill:none;stroke:%2372AEE6;stroke-width:3;stroke-miterlimit:10;%7D .st6%7Bfill:none;stroke:%2372AEE6;stroke-width:2;stroke-miterlimit:10;%7D%0A%3C/style%3E%3Cg id="Layer_2_00000047770719710110742230000003923951626148849557_" class="st0"%3E%3Crect class="st1" width="53.1" height="53.1"/%3E%3C/g%3E%3Cg id="Layer_1_00000162333103265806981530000017146624247591674556_"%3E%3Cg%3E%3Cg%3E%3Cdefs%3E%3Cpath id="SVGID_1_" d="M0,0h53v53H0V0z M23.3,37.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5 C20.8,36.1,21.9,37.2,23.3,37.2z M33.4,27.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5S32,27.2,33.4,27.2z M23,22.8c1.6,0,2.9-1.3,2.9-2.9S24.6,17,23,17s-2.9,1.3-2.9,2.9C20.2,21.5,21.5,22.8,23,22.8z"/%3E%3C/defs%3E%3CclipPath id="SVGID_00000111880200341027563210000003406718579453459379_"%3E%3Cuse xlink:href="%23SVGID_1_" style="overflow:visible;"/%3E%3C/clipPath%3E%3Cg id="BarsClipped" style="clip-path:url(%23SVGID_00000111880200341027563210000003406718579453459379_);"%3E%3Cpath class="st3" d="M39.9,31.5L18,37.3c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C40.8,30.7,40.5,31.3,39.9,31.5z"/%3E%3Cpath class="st3" d="M38.1,24.6l-21.9,5.9c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C39,23.8,38.7,24.5,38.1,24.6z"/%3E%3Cpath class="st3" d="M36.2,17.9l-21.9,5.9c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C37.2,17.1,36.8,17.7,36.2,17.9z"/%3E%3C/g%3E%3C/g%3E%3C/g%3E%3Cpath class="st4" d="M23.3,37.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5C20.8,36.1,21.9,37.2,23.3,37.2z"/%3E%3Cpath class="st4" d="M33.4,27.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5S32,27.2,33.4,27.2z"/%3E%3Cpath class="st4" d="M23,22.8c1.6,0,2.9-1.3,2.9-2.9S24.6,17,23,17s-2.9,1.3-2.9,2.9C20.2,21.5,21.5,22.8,23,22.8z"/%3E%3Cpath class="st4" d="M23.9,38.2c-1.9,0.5-3.8-0.6-4.3-2.5c-0.5-1.9,0.6-3.8,2.5-4.3s3.8,0.6,4.3,2.5C26.9,35.8,25.8,37.7,23.9,38.2 z M22.6,33.2c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C24.3,33.5,23.4,33,22.6,33.2z"/%3E%3Cpath class="st4" d="M34.1,28.2c-1.9,0.5-3.8-0.6-4.3-2.5s0.6-3.8,2.5-4.3c1.9-0.5,3.8,0.6,4.3,2.5C37.1,25.8,36,27.7,34.1,28.2z M32.8,23.2c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C34.5,23.5,33.6,23,32.8,23.2z"/%3E%3Cpath class="st4" d="M24.2,23.5c-1.9,0.5-3.8-0.6-4.3-2.5s0.6-3.8,2.5-4.3s3.8,0.6,4.3,2.5S26.1,23,24.2,23.5z M22.9,18.6 c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C24.7,18.8,23.8,18.3,22.9,18.6z"/%3E%3Cpath class="st5" d="M26.5,51.5c13.8,0,25-11.2,25-25s-11.2-25-25-25s-25,11.2-25,25S12.7,51.5,26.5,51.5z"/%3E%3Cpath class="st4" d="M33.3,21h10c0.4,0,0.8-0.3,0.8-0.8s-0.3-0.8-0.8-0.8h-10C33.3,20.2,33.3,20.2,33.3,21z"/%3E%3C/g%3E%3Ccircle class="st6" cx="23.3" cy="20.1" r="2.5"/%3E%3Ccircle class="st6" cx="33.2" cy="24.8" r="2.5"/%3E%3Ccircle class="st6" cx="23" cy="34.8" r="2.5"/%3E%3C/svg%3E');
}
.wp-menu-open#toplevel_page_edit-post_type-filter-set .wp-menu-image {
    background-image: url('data:image/svg+xml,%3C%3Fxml version="1.0" encoding="utf-8"%3F%3E%3C!--  --%3E%3Csvg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 53 53" style="enable-background:new 0 0 53 53;" xml:space="preserve"%3E%3Cstyle type="text/css"%3E .st0%7Bdisplay:none;%7D .st1%7Bdisplay:inline;%7D .st2%7Bclip-path:url(%23SVGID_00000098199702249043298830000017010845528166695064_);%7D .st3%7Bfill:%23FFFFFF;%7D .st4%7Bdisplay:none;fill:%23FFFFFF;%7D .st5%7Bfill:none;stroke:%23FFFFFF;stroke-width:3;stroke-miterlimit:10;%7D .st6%7Bfill:none;stroke:%23FFFFFF;stroke-width:2;stroke-miterlimit:10;%7D%0A%3C/style%3E%3Cg id="Layer_2_00000047770719710110742230000003923951626148849557_" class="st0"%3E%3Crect x="0" y="0" class="st1" width="53.1" height="53.1"/%3E%3C/g%3E%3Cg id="Layer_1_00000162333103265806981530000017146624247591674556_"%3E%3Cg%3E%3Cdefs%3E%3Cpath id="SVGID_1_" d="M0,0h53v53H0V0z M23.3,37.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5 C20.8,36.1,21.9,37.2,23.3,37.2z M33.4,27.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5S32,27.2,33.4,27.2z M23,22.8c1.6,0,2.9-1.3,2.9-2.9S24.6,17,23,17s-2.9,1.3-2.9,2.9C20.2,21.5,21.5,22.8,23,22.8z"/%3E%3C/defs%3E%3CclipPath id="SVGID_00000112608340804245442460000013986219178199086244_"%3E%3Cuse xlink:href="%23SVGID_1_" style="overflow:visible;"/%3E%3C/clipPath%3E%3Cg id="BarsClipped" style="clip-path:url(%23SVGID_00000112608340804245442460000013986219178199086244_);"%3E%3Cpath class="st3" d="M39.9,31.5L18,37.3c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C40.8,30.7,40.5,31.3,39.9,31.5z"/%3E%3Cpath class="st3" d="M38.1,24.6l-21.9,5.9c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C39,23.8,38.7,24.5,38.1,24.6z"/%3E%3Cpath class="st3" d="M36.2,17.9l-21.9,5.9c-0.6,0.2-1.2-0.2-1.4-0.8c-0.2-0.6,0.2-1.2,0.8-1.4l21.9-5.9c0.6-0.2,1.2,0.2,1.4,0.8 C37.2,17.1,36.8,17.7,36.2,17.9z"/%3E%3C/g%3E%3C/g%3E%3Cpath class="st4" d="M23.3,37.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5C20.8,36.1,21.9,37.2,23.3,37.2z"/%3E%3Cpath class="st4" d="M33.4,27.2c1.4,0,2.5-1.1,2.5-2.5s-1.1-2.5-2.5-2.5s-2.5,1.1-2.5,2.5S32,27.2,33.4,27.2z"/%3E%3Cpath class="st4" d="M23,22.8c1.6,0,2.9-1.3,2.9-2.9S24.6,17,23,17s-2.9,1.3-2.9,2.9C20.2,21.5,21.5,22.8,23,22.8z"/%3E%3Cpath class="st4" d="M23.9,38.2c-1.9,0.5-3.8-0.6-4.3-2.5c-0.5-1.9,0.6-3.8,2.5-4.3s3.8,0.6,4.3,2.5C26.9,35.8,25.8,37.7,23.9,38.2 z M22.6,33.2c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C24.3,33.5,23.4,33,22.6,33.2z"/%3E%3Cpath class="st4" d="M34.1,28.2c-1.9,0.5-3.8-0.6-4.3-2.5s0.6-3.8,2.5-4.3c1.9-0.5,3.8,0.6,4.3,2.5C37.1,25.8,36,27.7,34.1,28.2z M32.8,23.2c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C34.5,23.5,33.6,23,32.8,23.2z"/%3E%3Cpath class="st4" d="M24.2,23.5c-1.9,0.5-3.8-0.6-4.3-2.5s0.6-3.8,2.5-4.3s3.8,0.6,4.3,2.5S26.1,23,24.2,23.5z M22.9,18.6 c-0.9,0.2-1.4,1.1-1.1,2c0.2,0.9,1.1,1.4,2,1.1c0.9-0.2,1.4-1.1,1.1-2C24.7,18.8,23.8,18.3,22.9,18.6z"/%3E%3Cpath class="st5" d="M26.5,51.5c13.8,0,25-11.2,25-25s-11.2-25-25-25s-25,11.2-25,25S12.7,51.5,26.5,51.5z"/%3E%3Cpath class="st4" d="M33.3,21h10c0.4,0,0.8-0.3,0.8-0.8s-0.3-0.8-0.8-0.8h-10C33.3,20.2,33.3,20.2,33.3,21z"/%3E%3C/g%3E%3Ccircle class="st6" cx="23.3" cy="20.1" r="2.5"/%3E%3Ccircle class="st6" cx="33.2" cy="24.8" r="2.5"/%3E%3Ccircle class="st6" cx="23" cy="34.8" r="2.5"/%3E%3C/svg%3E');
}
PHP_CSS;
    wp_add_inline_style($handle, $css);
});

/**
 * Fix Elementor query arguments for filtered content
 *
 * This function adjusts Elementor's query arguments when dealing with
 * filtered content from Filter Everything Pro plugin. It ensures proper
 * handling of custom post types in Elementor queries.
 *
 * @param array $query_args The Elementor query arguments
 * @return array Modified query arguments
 */
add_filter( 'elementor/query/get_query_args/current_query', 'flrt_fix_elementor_query_args' );
function flrt_fix_elementor_query_args( $query_args ) {
    // Verify that we're dealing with a filtered query
    if ( ! isset( $query_args['flrt_filtered_query'] ) || !$query_args['flrt_filtered_query'] ) {
        return $query_args;
    }

    // Get post type from query args or current post
    $post_type = isset( $query_args['post_type'] ) ? $query_args['post_type'] : get_post_type();

    // Sanitize the post type
    if ( ! empty( $post_type ) ) {
        $post_type = sanitize_text_field( $post_type );
    }

    // Check if the post type is non-built-in
    $post_type_object = get_post_type_object( $post_type );

    if ( $post_type_object && !$post_type_object->_builtin ) {
        // Remove taxonomy from query args if present
        if ( isset( $query_args['taxonomy'] ) ) {
            unset( $query_args['taxonomy'] );
        }
    }

    return $query_args;
}

/**
 * Fixes Elementor featured products price range query arguments.
 *
 * This function addresses an issue where Elementor's featured products widget
 * incorrectly applies meta_key filtering when sorting by price, which can
 * interfere with price range filtering functionality. It removes the meta_key
 * from query arguments for featured products sorted by price.
 *
 *
 * @param array $query_args Query arguments for Elementor widget.
 * @param object $widget The Elementor widget instance.
 * @return array Modified query arguments.
 */
if ( flrt_is_elementor_active() ) {
    add_filter( 'elementor/query/query_args', 'flrt_fix_elementor_featured_products_price_range', 10, 2 );

    function flrt_fix_elementor_featured_products_price_range( array $query_args, $widget ): array {
        $is_featured  = ( $query_args['post_type'] ?? '' ) === 'featured';
        $is_price_order = ( $query_args['orderby'] ?? '' ) === 'price';

        if ( $is_featured && $is_price_order ) {
            static $registered = false;

            if ( ! $registered ) {
                add_filter( 'elementor/query/query_args', function ( array $query_args, $widget ): array {
                    unset( $query_args['meta_key'] );
                    return $query_args;
                }, 11, 2 );

                $registered = true;
            }
        }

        return $query_args;
    }
}



//@todo check this with PLL support
//function flrt_add_cpt_to_pll_tmp( $post_types, $is_settings ) {
//    if ( $is_settings ) {
//        $post_types[ FLRT_FILTERS_SET_POST_TYPE ] = FLRT_FILTERS_SET_POST_TYPE;
//    }
//    return $post_types;
//}