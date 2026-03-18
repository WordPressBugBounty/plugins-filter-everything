<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

use FilterEverything\Filter\Container;
use \FilterEverything\Filter\PostDateEntity;
use \FilterEverything\Filter\ImportSettings;

// Make post type name lowercase in posts found message
add_filter( 'wpc_label_singular_posts_found_msg', 'mb_strtolower' );
add_filter( 'wpc_label_plural_posts_found_msg', 'mb_strtolower' );

add_action( 'init', 'flrt_initiate_overridden_functions' );
function flrt_initiate_overridden_functions()
{
// All hooks with function wrapper with function_exists()
    add_filter('wpc_filter_post_meta_num_term_name', 'flrt_ucfirst_term_slug_name');
    add_filter('wpc_filter_post_meta_term_name', 'flrt_ucfirst_term_slug_name');
    add_filter('wpc_filter_tax_numeric_term_name', 'flrt_ucfirst_term_slug_name');
    add_filter('wpc_filter_post_meta_exists_term_name', 'flrt_custom_field_exists_name');
    add_filter('wpc_filter_post_meta_term_name', 'flrt_stock_status_term_name', 10, 2);
    add_filter('wpc_filter_post_meta_exists_term_name', 'flrt_on_sale_term_name', 15, 2);
    add_filter('wpc_filter_taxonomy_term_name', 'flrt_modify_taxonomy_term_name', 10, 2);
    add_filter('wpc_filter_term_query_args', 'flrt_exclude_uncategorized_category', 10, 3);
    add_filter('wpc_filter_get_taxonomy_terms', 'flrt_exclude_product_visibility_terms', 10, 2);
    add_filter('wpc_filter_author_query_post_types', 'flrt_remove_author_query_post_types');
    add_filter('wpc_filter_post_types', 'flrt_exclude_post_types');
    add_action('wpc_after_filter_input', 'flrt_after_filter_input');
    add_filter('wpc_filters_checkbox_term_html', 'wpc_term_brand_logo', 5, 4);
    add_filter('wpc_filters_radio_term_html', 'wpc_term_brand_logo', 5, 4);
    add_filter('wpc_filters_label_term_html', 'wpc_term_brand_logo', 5, 4);
    add_filter('wpc_taxonomy_location_terms', 'flrt_remove_default_category_location', 10, 2);
    add_filter( 'wpc_set_num_shift', 'flrt_round_numeric_values', 10, 3 );

    if (!function_exists('flrt_ucfirst_term_slug_name')) {
        function flrt_ucfirst_term_slug_name($term_name)
        {
            $term_name = flrt_ucfirst($term_name);
            return $term_name;
        }
    }


    if (!function_exists('flrt_custom_field_exists_name')) {
        function flrt_custom_field_exists_name($term_name)
        {
            if ($term_name === 'yes') {
                return esc_html__('Yes', 'filter-everything');
            } else if ($term_name === 'no') {
                return esc_html__('No', 'filter-everything');
            }
            return $term_name;
        }
    }

    if (!function_exists('flrt_stock_status_term_name')) {
        function flrt_stock_status_term_name($term_name, $e_name)
        {
            if ($e_name === '_stock_status') {
                $term_name = strtolower($term_name);
                if ($term_name === "instock") {
                    $term_name = esc_html__('In stock', 'filter-everything');
                }

                if ($term_name === "onbackorder") {
                    $term_name = esc_html__('On backorder', 'filter-everything');
                }

                if ($term_name === "outofstock") {
                    $term_name = esc_html__('Out of stock', 'filter-everything');
                }
            }

            return $term_name;
        }
    }

    if (!function_exists('flrt_on_sale_term_name')) {
        function flrt_on_sale_term_name($term_name, $entity)
        {
            if ($entity === '_sale_price') {
                $check_name = mb_strtolower($term_name);

                if (in_array($check_name, ['yes', 'ja', 'ano', 'sí', 'так'])) {
                    $term_name = esc_html__('On Sale', 'filter-everything');
                }
                if (in_array($check_name, ['no', 'nein', 'ne', 'ні'])) {
                    $term_name = esc_html__('Regular price', 'filter-everything');
                }
            }
            return $term_name;
        }
    }


    if (!function_exists('flrt_modify_taxonomy_term_name')) {
        function flrt_modify_taxonomy_term_name($term_name, $e_name)
        {
            if (in_array($e_name, array('product_type', 'product_visibility'))) {
                $term_name = flrt_ucfirst($term_name);
            }
            return $term_name;
        }
    }


    if (!function_exists('flrt_exclude_uncategorized_category')) {
        function flrt_exclude_uncategorized_category($args, $entity, $e_name)
        {
            if ($e_name === 'category') {
                $args['exclude'] = array(1); // Uncategorized category
            }

            return $args;
        }
    }


    if (!function_exists('flrt_exclude_product_visibility_terms')) {
        function flrt_exclude_product_visibility_terms($terms, $e_name)
        {
            if ($e_name === 'product_visibility') {
                if (is_array($terms)) {
                    foreach ($terms as $index => $term) {

                        if (in_array($term->slug, array('exclude-from-search', 'exclude-from-catalog'))) {
                            unset($terms[$index]);
                        }
                    }
                }
            }

            if ($e_name === 'product_cat') {
                if (is_array($terms)) {
                    foreach ($terms as $index => $term) {
                        if (in_array($term->slug, array('uncategorized'))) {
                            unset($terms[$index]);
                        }
                    }
                }
            }

            return $terms;
        }
    }

    if (!function_exists('flrt_remove_author_query_post_types')) {
        function flrt_remove_author_query_post_types($post_types)
        {
            if (isset($post_types['attachment'])) {
                unset($post_types['attachment']);
            }
            return $post_types;
        }
    }

    if (!function_exists('flrt_exclude_post_types')) {
        function flrt_exclude_post_types($post_types)
        {

            $post_types = array(
                FLRT_FILTERS_POST_TYPE,
                FLRT_FILTERS_SET_POST_TYPE,
                'attachment',
                'elementor_library',
                'e-landing-page',
                'jet-smart-filters',
                'ct_template'
            );

            return $post_types;
        }
    }


    if (!function_exists('flrt_after_filter_input')) {
        function flrt_after_filter_input($attributes)
        {
            if (isset($attributes['class']) && $attributes['class'] === 'wpc-field-slug' && $attributes['value'] === '') {
                echo '<p class="description">' . esc_html__('a-z, 0-9, "_" and "-" symbols supported only', 'filter-everything') . '</p>';
            }

        }
    }

    if (!function_exists('wpc_term_brand_logo')) {
        function wpc_term_brand_logo($html, $link_attributes, $term, $filter)
        {
            if (!in_array($filter['e_name'], flrt_brand_filter_entities())) {
                return $html;
            }
            if (!isset($term->slug)) {
                return $html;
            }
            $src = flrt_get_term_brand_image($term->term_id, $filter);

            $link_attributes .= ' title="' . $term->name . '"';

            if ($src) {
                $img = '<span class="wpc-term-image-wrapper"><img src="' . $src . '" alt="' . $term->name . '" /></span>';
                $html = '<a ' . $link_attributes . '>' . $img . '<span class="wpc-term-name">' . $term->name . '</span></a>';
            }

            return $html;
        }
    }

    if( !function_exists( 'flrt_remove_default_category_location' ) ) {
        function flrt_remove_default_category_location( $terms, $taxonomy ){
            $default_category = 0;

            if ( $taxonomy === 'product_cat' ) {
                $default_category = get_option( 'default_product_cat' );
            } elseif( $taxonomy === 'category' ) {
                $default_category = get_option('default_category');
            }

            // Remove default category from the list
            if ( $default_category > 0 ) {
                unset( $terms[ $default_category ] );
            }

            return $terms;
        }
    }

    /**
     * Fix for the LiveCanvas page builder
     */
    if( defined( 'LC_MU_PLUGIN_NAME' ) && LC_MU_PLUGIN_NAME === 'livecanvas-must-use-plugin.php' ) {
        add_filter('wpc_pre_save_set_fields', 'flrt_safe_save_set_fields');
        function flrt_safe_save_set_fields($setFields)
        {

            if (isset($setFields['wp_filter_query_vars'])) {
                $query_vars = $setFields['wp_filter_query_vars'];
                $new_query_vars = [];
                $search_values = [
                    '…'
                ];
                $replace_values = [
                    '&hellip;'
                ];

                foreach ($query_vars as $hash => $vars_serialized_str) {
                    $new_query_vars[$hash] = str_replace($search_values, $replace_values, $vars_serialized_str);
                }

                $setFields['wp_filter_query_vars'] = $new_query_vars;
            }

            return $setFields;
        }
    }

    function flrt_round_numeric_values( $value, $entity_name, $limit )
    {
        if( $limit === 'min' ) {
            $value = floor( $value );
        } elseif ( $limit === 'max' ){
            $value = ceil( $value );
        }

        return $value;
    }

}
$ff = 'ad'.'d'.'_'.'f'.'il'.  'ter';

function flrt_chips( $showReset = false, $setIds = [] ) {
    $templateManager    = \FilterEverything\Filter\Container::instance()->getTemplateManager();
    $wpManager          = \FilterEverything\Filter\Container::instance()->getWpManager();

    if( empty( $setIds ) || ! $setIds || ! is_array( $setIds ) ){
        $relatedSetIds = $wpManager->getQueryVar('wpc_page_related_set_ids');

        if( is_array( $relatedSetIds ) ) {
            foreach ( $relatedSetIds as $set ){
                $setIds[] = $set['ID'];
            }
        }
    }

    $chipsObj = new \FilterEverything\Filter\Chips( $showReset, $setIds );
    $chips = $chipsObj->getChips();

    $templateManager->includeFrontView( 'chips', array( 'chips' => $chips, 'setid' => reset($setIds) ) );
}

function flrt_show_selected_terms( $showReset = true, $setIds = [], $class = [] )
{
    $default_class  = array('wpc-custom-selected-terms');

    if(! empty( $class ) && is_array($class) ){
        $default_class = array_merge( $default_class, $class );
    }

    if( isset( $_POST['action'] ) && $_POST['action'] === 'elementor_ajax' ){
        echo '<strong>'.esc_html__( 'Filter Everything &mdash; Chips', 'filter-everything' ).'</strong>';
        return;
    }

    if( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ){
        echo '<strong>'.esc_html__( 'Filter Everything &mdash; Chips', 'filter-everything' ).'</strong>';
        return;
    }

    echo '<div class="'.implode(' ', $default_class).'">'."\r\n";
        flrt_chips( $showReset, $setIds );
    echo '</div>'."\r\n";
}

add_filter( 'wpc_dropdown_option_attr', 'flrt_parse_dropdown_value' );
function flrt_parse_dropdown_value( $attr ){
    if( ! is_array( $attr ) ){
        $new_attr = array();
        $new_attr['label'] = $attr;
        return $new_attr;
    }

    return $attr;
}

add_filter( 'wpc_unnecessary_get_parameters', 'flrt_unnecessary_get_parameters' );
function flrt_unnecessary_get_parameters( $params ){
    $unnecessary_params = array(
        'product-page' => true,
        '_pjax' => true,
        'cst'   => true,
    );

    $get = \FilterEverything\Filter\Container::instance()->getTheGet();

    if( ! empty( $get ) && is_array( $get ) ) {
        foreach ( $get as $param_name => $param_value ) {
            if( preg_match( '%query\-[0-9]+\-page%', $param_name ) ) {
                $unnecessary_params[$param_name] = true;
            }

            if( preg_match( '%e\-page\-[a-zA-Z0-9]+%im', $param_name ) ) {
                $unnecessary_params[$param_name] = true;
            }
        }
    }

    return array_merge( $params, $unnecessary_params );
}

add_filter('wpc_posts_containers', 'flrt_convert_posts_container_to_array');
function flrt_convert_posts_container_to_array( $container ){

    if( ! is_array( $container ) ){
        return [ 'default' => trim($container) ];
    }

    return $container;
}

add_filter( 'wpc_seo_title', 'do_shortcode' );
add_filter( 'wpc_seo_description', 'do_shortcode' );
add_filter( 'wpc_seo_h1', 'do_shortcode' );

/**
 * @return int
 * @since 1.7.1
 */
function flrt_more_less_count() {
    return apply_filters( 'wpc_more_less_count', 5 );
}
/**
 * @return mixed|void
 * @since 1.7.1
 */
function flrt_more_less_opened() {
    return apply_filters( 'wpc_more_less_opened', [] );
}
/**
 * @return mixed|void
 * @since 1.7.1
 */
function flrt_folding_opened() {
    return apply_filters( 'wpc_folding_opened', [] );
}
/**
 * @return mixed|void
 * @since 1.7.1
 */
function flrt_hierarchy_opened() {
    return apply_filters( 'wpc_hierarchy_opened', [] );
}
/**
 * @param $filter
 * @return mixed|void
 * @since 1.7.1
 */
function flrt_dropdown_default_option( $filter ) {
    $label = sprintf( __( '- Select %s -', 'filter-everything' ),  $filter['label'] );
    if( isset( $filter['dropdown_label'] ) && $filter['dropdown_label'] ){
        $label = $filter['dropdown_label'];
    }
    return apply_filters( 'wpc_dropdown_default_option', $label, $filter );
}

function flrt_brand_filter_entities(){
    return apply_filters( 'wpc_brand_filter_entities', ['product_brand', 'pa_brand', 'pwb-brand', 'yith_product_brand'] );
}

add_filter( 'wpc_filter_classes', 'wpc_frontend_filter_classes', 10, 2 );
function wpc_frontend_filter_classes( $classes, $filter ){
    if( in_array( $filter['e_name'], flrt_brand_filter_entities() ) ) {
        $classes[] = 'wpc-filter-has-brands';
    }

    return $classes;
}

add_filter( 'wpc_filter_classes', 'flrt_frontend_filter_classes', 10, 2 );
function flrt_frontend_filter_classes( $classes, $filter ){
    if ( $filter['show_term_names'] === 'yes' ) {
        $classes[] = 'wpc-filter-visible-term-names';
    } else {
        $classes[] = 'wpc-filter-hidden-term-names';
    }

    return $classes;
}

//add_filter( 'wpc_filter_classes', 'flrt_same_swatches_width', 20, 4 );
//function flrt_same_swatches_width( $classes, $filter, $default_classes, $terms, $args ){
//    if( in_array( 'wpc-filter-has-swatches', $classes ) ){
//        if( ! empty( $terms ) ) {
//            $counters = [];
//            foreach ( $terms as $single_term ) {
//                $counters[] = $single_term->cross_count;
//            }
//
//            $max = max( $counters );
//            $classes[] = 'wpc-counter-width-'. strlen( (string)$max );
//        }
//    }
//    return $classes;
//}


// Bricks Builder fix for Any Category Filter Set
add_action( 'wpc_all_set_wp_queried_posts', 'flrt_bricks_builder_category_compat', 10, 2 );
function flrt_bricks_builder_category_compat( $set_wp_query, $setId ){

    //to check if there is Bricks Builder
    if ( defined( 'BRICKS_VERSION' ) && BRICKS_VERSION ) {
        $filterSet  = \FilterEverything\Filter\Container::instance()->getFilterSetService();
        $the_set    = $filterSet->getSet( $setId );

        if ( isset( $the_set['wp_page_type']['value'] ) && isset( $the_set['post_name']['value'] ) ){
            if ( strpos( $the_set['wp_page_type']['value'], 'taxonomy_' ) !== false ) {
                if ( strpos( $the_set['post_name']['value'], '-1' ) !== false ) {
                    $queried_object = get_queried_object();
                    if ( property_exists( $queried_object, 'taxonomy' ) ){
                        $set_wp_query->set( $queried_object->taxonomy, $queried_object->slug );
                    }
                }
            }
        }
    }

    return $set_wp_query;
}

add_filter( 'wpc_chips_term_name', 'flrt_chips_labels', 10, 3 );
function flrt_chips_labels( $term_name, $term, $filter ) {
    if ( in_array( $filter['entity'], ['post_meta_num', 'tax_numeric', 'post_date', 'post_meta_date'] ) ) {
        // 'min_num_label'
        // 'max_num_label'

        if( $filter['min_num_label'] !== '' ){
            if( ! is_null( $term ) && property_exists( $term, 'slug' ) && in_array( $term->slug, ['min', 'from'] ) ){
                if( strpos( $filter['min_num_label'], '{value}' ) !== false ){
                    $term_name = str_replace( '{value}', $filter['values'][$term->slug], $filter['min_num_label'] );
                }else {
                    $term_name = $filter['min_num_label'] .' '.$filter['values'][$term->slug];
                }

            }
        }

        if( $filter['max_num_label'] !== '' ){
            if( ! is_null( $term ) && property_exists( $term, 'slug' ) && in_array( $term->slug, ['max', 'to'] ) ){
                if( strpos( $filter['max_num_label'], '{value}' ) !== false ){
                    $term_name = str_replace( '{value}', $filter['values'][$term->slug], $filter['max_num_label'] );
                }else {
                    $term_name = $filter['max_num_label'] .' '.$filter['values'][$term->slug];
                }
            }
        }

    }

    return $term_name;
}

add_filter( 'query_loop_block_query_vars', 'flrt_query_loop_block_query_vars', 10, 2 );

function flrt_query_loop_block_query_vars( $query, $block ){
    global $xd;
    $xd++;

    if( ! is_null( $block ) && property_exists( $block, 'parsed_block' ) ){
        if( isset( $block->parsed_block['blockName'] ) ) {
            if( in_array( $block->parsed_block['blockName'],
                [
                    'core/query-pagination-previous',
                    'core/query-pagination-next',
                    'core/query-pagination-numbers',
                ]
                ) ){
                $query['flrt_pagination'] = true;
            }
        }
    }

    return $query;
}

add_filter( 'wpc_settings_field_checkbox', 'flrt_collapse_widget_checkbox_handler', 10, 2 );
function flrt_collapse_widget_checkbox_handler( $checkbox, $args )
{
    $checkbox  = '<div class="flrt-checkbox-switch-wrapper">';
    $checkbox .= '<label class="flrt-checkbox-switch"><input type="checkbox" name="%s[%s]" %s id="%s" %s>';
    $checkbox .= '<span class="flrt-checkbox-slider"></span>';
    $checkbox .= '</label>';
    $checkbox .= '<span class="wpc-checkbox-placeholder">%s</span>';
    $checkbox .= '</div>';
    return $checkbox;
}

add_filter( 'wpc_input_type_checkbox', 'flrt_input_checkbox_switcher', 10, 2 );
function flrt_input_checkbox_switcher( $html, $attributes )
{

    $html_wrap = '<div class="flrt-checkbox-switch-wrapper">';
    $html_wrap .= '<label class="flrt-checkbox-switch">';
    $html_wrap .=  $html;
    $html_wrap .= '<span class="flrt-checkbox-slider"></span>';
    $html_wrap .= '</label>';
    $html_wrap .= '</div>';

    return $html_wrap;
}


add_filter('wpc_get_broken_builders', function ($array) {
    return [3669169978, 339203640];
}, 10, 1);



if(!defined('FLRT_FILTERS_PRO')) {

    add_filter('flrt_before_render_admin_select_option', 'flrt_add_data_to_option_before_render', 10, 4);
    function flrt_add_data_to_option_before_render($option_value, $attr, $label, $disabled)
    {
            $attributes = ' data-link="' . flrt_reneder_preview_link($option_value, $attr) . '"';

            if(is_array($disabled) && in_array($option_value, $disabled)){
                $class = $option_value . '-disabled';
                $attributes .= ' class="' . $class . '"';
            }

            return $attributes;
    }
    function flrt_reneder_preview_link($option_value, $attr)
    {
        $link = '';
        if ($attr['id'] == 'wpc_set_fields-post_type') {
            if ($option_value == 'page') {
                $option_value = 'post';
            }



            $archive_link = get_post_type_archive_link($option_value);
            if ($archive_link) {
                $link = $archive_link;
            }

            if (!$archive_link) {
                $taxonomies = get_object_taxonomies($option_value);

                if (!empty($taxonomies)) {
                    $first_taxonomy = $taxonomies[0];
                    $taxonomy = get_taxonomy($first_taxonomy);
                    if (!empty($taxonomy) && !is_wp_error($taxonomy)) {
                        $terms = get_terms([
                            'taxonomy'   => $taxonomy->name,
                            'number'     => 1,
                            'hide_empty' => false,
                        ]);
                        if (!empty($terms) && !is_wp_error($terms)) {
                            $term_link = get_term_link($terms[0]);
                            $link = esc_url($term_link);
                        }
                    }
                }
            }
        }
        return $link;
    }
}

add_filter('wpc_is_check_errors_pro', function ($filter_sets, $query) {
    $detector_class = 'FilterEverything\\Filter\\WP_Query_Source_Detector';
    $is_allowed_method = 'is_allowed';
    $source_var = 'flrt_detected_source';

    if (defined('FLRT_FILTERS_PRO')) {
        if (defined('FLRT_PRO_BUILDER_KEY')) {
            $builder_key = apply_filters('wpc_builder_key_pro', $query->get($source_var), constant('FLRT_PRO_BUILDER_KEY'));

            if ($detector_class::$is_allowed_method($builder_key)) {
                return apply_filters('wpc_is_filtered_query_pro', [], $query);
            }
        }
    }

    if (!defined('FLRT_FILTERS_PRO')) {
        $builder_key = apply_filters('wpc_builder_key', $query->get($source_var), $detector_class::$builder_key);

        if ($detector_class::$is_allowed_method($builder_key)) {
            return apply_filters('wpc_is_filtered_query_free', [], $query);
        }
    }

    return [];
}, 10, 2);

add_filter('wpc_set_min_max', function($min_and_max, $filter_name) {
    global $wp_query;

    if (empty($wp_query->posts)) {
        $min_and_max = ['min' => 0, 'max' => 0];
        return $min_and_max;
    }

    return $min_and_max;
}, 10, 2);

if(!function_exists('wpc_default_custom_meta_keys_filter')){
    function wpc_default_custom_meta_keys_filter(){
        $normalize_keys = function($data) use (&$normalize_keys) {
            $result = [];
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $v = $normalize_keys($v);
                }
                if (is_int($k)) {
                    $result[(string)$v] = "";
                } else {
                    $result[$k] = $v;
                }
            }
            return $result;
        };

        $product_post_meta = array(
            '_price'             => esc_html__('filter by Product price', 'filter-everything'),
            '_stock_status'      => esc_html__('filter by Product stock status', 'filter-everything'),
            '_sale_price'        => esc_html__('filter by Product sale price', 'filter-everything'),
            '_stock'             => esc_html__('filter by Product stock quantity', 'filter-everything'),
            'total_sales'        => esc_html__('filter by Product total sales', 'filter-everything'),
            '_backorders'        => esc_html__('filter by Product backorders status', 'filter-everything'),
            '_sold_individually' => esc_html__('filter by Product sold individually status', 'filter-everything'),
            '_downloadable'      => esc_html__('filter by Product downloadable status', 'filter-everything'),
            '_virtual'           => esc_html__('filter by Product virtual status', 'filter-everything'),
            '_length'            => esc_html__('filter by Product length', 'filter-everything'),
            '_width'             => esc_html__('filter by Product width', 'filter-everything'),
            '_height'            => esc_html__('filter by Product height', 'filter-everything'),
            '_weight'            => esc_html__('filter by Product weight', 'filter-everything'),
            '_wc_average_rating' => esc_html__('filter by Product average rating', 'filter-everything'),
            '_thumbnail_id'      => esc_html__('filter by Featured image', 'filter-everything')
        );
        $product_post_meta_num = array(
            '_price'             => esc_html__('filter by Product price', 'filter-everything'),
            '_stock'             => esc_html__('filter by Product stock quantity', 'filter-everything'),
            'total_sales'        => esc_html__('filter by Product total sales', 'filter-everything'),
            '_sale_price'        => esc_html__('filter by Product sale price', 'filter-everything'),
            '_stock_status'      => esc_html__('filter by Product stock status', 'filter-everything'),
            '_length'            => esc_html__('filter by Product length', 'filter-everything'),
            '_width'             => esc_html__('filter by Product width', 'filter-everything'),
            '_height'            => esc_html__('filter by Product height', 'filter-everything'),
            '_weight'            => esc_html__('filter by Product weight', 'filter-everything'),
            '_wc_average_rating' => esc_html__('filter by Product average rating', 'filter-everything'),
            '_backorders'        => esc_html__('filter by Product backorders status', 'filter-everything'),
            '_sold_individually' => esc_html__('filter by Product sold individually status', 'filter-everything'),
            '_downloadable'      => esc_html__('filter by Product downloadable status', 'filter-everything'),
            '_virtual'           => esc_html__('filter by Product virtual status', 'filter-everything'),
            '_thumbnail_id'      => esc_html__('filter by Featured image', 'filter-everything'),
        );
        $product_post_meta_exists = array(
            '_sale_price'        => esc_html__('filter by Product on sale status', 'filter-everything'),
            '_price'             => esc_html__('filter by Product price', 'filter-everything'),
            '_stock'             => esc_html__('filter by Product stock quantity', 'filter-everything'),
            'total_sales'        => esc_html__('filter by Product total sales', 'filter-everything'),
            '_stock_status'      => esc_html__('filter by Product stock status', 'filter-everything'),
            '_length'            => esc_html__('filter by Product length', 'filter-everything'),
            '_width'             => esc_html__('filter by Product width', 'filter-everything'),
            '_height'            => esc_html__('filter by Product height', 'filter-everything'),
            '_weight'            => esc_html__('filter by Product weight', 'filter-everything'),
            '_wc_average_rating' => esc_html__('filter by Product average rating', 'filter-everything'),
            '_backorders'        => esc_html__('filter by Product backorders status', 'filter-everything'),
            '_sold_individually' => esc_html__('filter by Product sold individually status', 'filter-everything'),
            '_downloadable'      => esc_html__('filter by Product downloadable status', 'filter-everything'),
            '_virtual'           => esc_html__('filter by Product virtual status', 'filter-everything'),
            '_thumbnail_id'      => esc_html__('filter by Featured image', 'filter-everything'),
        );
        $all_post_types_post_meta_exists = array('_thumbnail_id' => esc_html__('filter by Featured image', 'filter-everything'));
        $input = array(
            'product'        => array(
                'post_meta'        => $normalize_keys($product_post_meta),
                'post_meta_num'    => $normalize_keys($product_post_meta_num),
                'post_meta_exists' => $normalize_keys($product_post_meta_exists)
            ),
            'all_post_types' => array(
                'post_meta_exists' => $normalize_keys($all_post_types_post_meta_exists)
            ),
        );

        return $normalize_keys($input);
    }
}

add_action('wp_ajax_wpc_search_meta_keys', function () {
    check_ajax_referer('wpc-f-set-nonce', '_flrt_nonce');

    if ( ! current_user_can('edit_posts') ) {
        wp_send_json_error(['message' => __('Forbidden', 'textdomain')], 403);
    }

    global $wpdb;

    $q         = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'post';
    $post_meta_type = isset($_GET['post_meta_type']) ? sanitize_key($_GET['post_meta_type']) : '';

    $include_custom_meta_keys = [];

    if (!empty($post_type) && !empty($post_meta_type) && !empty(wpc_default_custom_meta_keys_filter()[$post_type][$post_meta_type])) {
        foreach (wpc_default_custom_meta_keys_filter()[$post_type][$post_meta_type] as $key => $value) {
            $include_custom_meta_keys[$key] = $value;
        }
    }

    if (!empty($post_meta_type) && !empty(wpc_default_custom_meta_keys_filter()['all_post_types'][$post_meta_type])) {
        foreach (wpc_default_custom_meta_keys_filter()['all_post_types'][$post_meta_type] as $key => $value) {
            $include_custom_meta_keys[$key] = $value;
        }
    }

    $default_custom_meta_keys = $include_custom_meta_keys;
    $include_custom_meta_keys = array_values(array_unique(array_filter($include_custom_meta_keys)));
    if(strlen($q) > 0){
        $include_custom_meta_keys = array_values(array_filter(
            $include_custom_meta_keys,
            static fn(string $key) => strncmp($key, $q, strlen($q)) === 0
        ));
    }
    $default_items = array_map(static function ($k) {
        return [
            'id'   => $k,
            'text' => $include_custom_meta_keys[$k],
        ];
    }, array_keys($include_custom_meta_keys) ?: []);

    if (strlen($q) < 1) {
        wp_send_json(['items' => $default_items]);
    }

    $like = '%' . $wpdb->esc_like($q) . '%';
    $limit = 15;

    $sql = $wpdb->prepare(
        "SELECT DISTINCT pm.meta_key
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type = %s
           AND pm.meta_key LIKE %s ORDER BY pm.meta_key ASC
         LIMIT %d",
        $post_type,
        $like,
        $limit
    );

    $keys = $wpdb->get_col($sql);

    $exact_match_index = array_search($q, $keys);
    if ($exact_match_index !== false) {
        unset($keys[$exact_match_index]);
        array_unshift($keys, $q);
    }
    $items = array_map(static function ($k) use ($default_custom_meta_keys) {
        $text = !empty($default_custom_meta_keys[$k]) ? $default_custom_meta_keys[$k] : '';
        return [
            'id'   => $k,
            'text' => $text,
        ];
    }, $keys ?: []);
    if (strlen($q) > 1 && count($items) < 1) {
        $items[] = [
            'id'   => $q,
            'text' => '',
        ];
    }

    wp_send_json(['items' => array_merge($default_items, $items)]);
});
add_action( 'admin_post_wpc_seo_settings', function() {
    check_admin_referer( 'wpc_seo_settings' );

    $filter_permalinks = $_POST['wpc_filter_permalinks'] ?? [];
    $seo_rules_settings = $_POST['wpc_seo_rules_settings'] ?? [];
    $wpc_indexing_deep_settings = $_POST['wpc_indexing_deep_settings'] ?? [];

    $filter_permalinks = array_map( 'sanitize_text_field', $filter_permalinks );
    $seo_rules_settings = array_map( 'sanitize_text_field', $seo_rules_settings );
    $wpc_indexing_deep_settings = array_map( 'sanitize_text_field', $wpc_indexing_deep_settings );

    update_option( 'wpc_filter_permalinks', $filter_permalinks );
    update_option( 'wpc_seo_rules_settings', $seo_rules_settings );
    update_option( 'wpc_indexing_deep_settings', $wpc_indexing_deep_settings );

    $redirect_url = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
    wp_safe_redirect( $redirect_url );
    exit;
});

if (!defined( 'FLRT_FILTERS_PRO' ) ){
    add_action( 'admin_footer', function () {
        if ( ! is_admin() ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! $screen ) {
            return;
        }

        if ( $screen->post_type === 'filter-set' ) {
            flrt_include_admin_view('pro-modal', []);
        }
    });
}



//add_filter( 'stackable/posts/post_query', 'flrt_stackable_block_query_vars', 10, 3 );
//function flrt_stackable_block_query_vars( $post_query, $context, $query_string ){
//    if( is_user_logged_in() ) {
//        var_dump( $context );
//        var_dump( $query_string );
//    }
//
//    return $post_query;
//}