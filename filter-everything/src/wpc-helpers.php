<?php

if (!defined('ABSPATH')) {
    exit;
}

use \FilterEverything\Filter\Container;
use \FilterEverything\Filter\FilterSet;
use \FilterEverything\Filter\FilterFields;
use \FilterEverything\Filter\PostMetaNumEntity;
use \FilterEverything\Filter\PostDateEntity;
use \FilterEverything\Filter\PostMetaDateEntity;
/**
 * Returns user's caps level that allows to use the plugin.
 * Developers can modify this level via hook 'wpc_plugin_user_caps' ot their own risk.
 * @return string
 */
function flrt_plugin_user_caps()
{
    return apply_filters('wpc_plugin_user_caps', 'manage_options');
}

function flrt_the_set($set_id = 0)
{
    if (function_exists('brizy_load')) {
        if (!did_action('wp_print_scripts')) {
            return false;
        }
    }

    return Container::instance()->getFilterContext()->takeSet($set_id);
}

function flrt_print_filters_for($hook = '')
{
    global $wp_filter;
    if (empty($hook) || !isset($wp_filter[$hook]))
        return;
    return $wp_filter[$hook];
}

function flrt_is_filter_request()
{
    $wpManager = Container::instance()->getWpManager();
    return $wpManager->getQueryVar('wpc_is_filter_request');
}

/**
 * Whether filters travel as pretty URL path segments.
 *
 * FLRT_PERMALINKS_ENABLED is the switch; it can be overridden in wp-config.php
 * or a theme's functions.php (Permalink Manager compatibility mode). When it is
 * not defined yet, compute the default it would get: PRO with non-empty rewrite
 * rules. WpManager::init() defines the constant from this on `init`, but code
 * running in a request where the plugin was loaded after `init` — plugin
 * activation — must not fall back to the query-string mode.
 *
 * @return bool
 */
function flrt_permalinks_enabled()
{
    if ( defined( 'FLRT_PERMALINKS_ENABLED' ) ) {
        return (bool) FLRT_PERMALINKS_ENABLED;
    }

    global $wp_rewrite;
    $rewrite = ( $wp_rewrite instanceof \WP_Rewrite ) ? $wp_rewrite->wp_rewrite_rules() : [];

    return defined( 'FLRT_FILTERS_PRO' ) && ! empty( $rewrite );
}

function flrt_include($filename)
{
    $path = flrt_get_path($filename);

    if (file_exists($path)) {
        include_once($path);
    }
}

function flrt_get_path($path = '')
{
    return FLRT_PLUGIN_DIR_PATH . ltrim($path, '/');
}

function flrt_ucfirst($text)
{
    if (!is_string($text)) {
        return $text;
    }
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
}

function flrt_lcfirst( $text )
{
    if( ! is_string( $text ) ){
        return $text;
    }
    return mb_strtolower( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 );
}

function flrt_add_query_arg(...$args)
{
    if (is_array($args[0])) {
        if (count($args) < 2 || false === $args[1]) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = $args[1];
        }
    } else {
        if (count($args) < 3 || false === $args[2]) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = $args[2];
        }
    }

    $frag = strstr($uri, '#');
    if ($frag) {
        $uri = substr($uri, 0, -strlen($frag));
    } else {
        $frag = '';
    }

    if (0 === stripos($uri, 'http://')) {
        $protocol = 'http://';
        $uri = substr($uri, 7);
    } elseif (0 === stripos($uri, 'https://')) {
        $protocol = 'https://';
        $uri = substr($uri, 8);
    } else {
        $protocol = '';
    }

    if (strpos($uri, '?') !== false) {
        list($base, $query) = explode('?', $uri, 2);
        $base .= '?';
    } elseif ($protocol || strpos($uri, '=') === false) {
        $base = $uri . '?';
        $query = '';
    } else {
        $base = '';
        $query = $uri;
    }

    wp_parse_str($query, $qs);

    if (is_array($args[0])) {
        foreach ($args[0] as $k => $v) {
            $qs[$k] = $v;
        }
    } else {
        $qs[$args[0]] = $args[1];
    }

    foreach ($qs as $k => $v) {
        if (false === $v) {
            unset($qs[$k]);
        }
    }

    $ret = build_query($qs);
    $ret = trim($ret, '?');
    $ret = preg_replace('#=(&|$)#', '$1', $ret);
    $ret = $protocol . $base . $ret . $frag;
    $ret = rtrim($ret, '?');
    return $ret;
}

/**
 * @param $terms array
 * @param $keys array
 *
 * @return array Array of objects with required keys
 */
function flrt_extract_objects_vars($terms, $keys = [])
{
    $required = [];

    foreach ($terms as $i => $term) {
        $new_object = new \stdClass();

        foreach ($keys as $key) {
            if (isset($term->$key)) {
                $new_object->$key = $term->$key;
                $required[$term->term_id] = $new_object;
            }
        }
    }

    return $required;
}

add_filter('wpc_check_broken_query_vars', function ($query_vars, $query) {
    if (is_admin()) {
        return $query_vars;
    }

    $detector_class = 'FilterEverything\\Filter\\WP_Query_Source_Detector';
    $is_allowed_method = 'is_allowed';
    $source_var = 'flrt_detected_source';

    if (defined('FLRT_FILTERS_PRO')) {
        if (defined('FLRT_PRO_BUILDER_KEY')) {
            $builder_key = apply_filters('wpc_builder_key_pro', $query->get($source_var), constant('FLRT_PRO_BUILDER_KEY'));

            if ($detector_class::$is_allowed_method($builder_key)) {
                return $query_vars;
            }
        }
    }

    if (!defined('FLRT_FILTERS_PRO')) {
        $builder_key = apply_filters('wpc_builder_key', $query->get($source_var), $detector_class::$builder_key);

        if ($detector_class::$is_allowed_method($builder_key)) {
            return $query_vars;
        }
    }

    return [];
}, 10, 2);

add_filter('wpc_builder_key', function ($source, $builder_id) {
    return (int) sprintf("%u", crc32($source . $builder_id));
}, 10, 2);

/**
 * Compatibility with membership / access-control plugins.
 *
 * Such plugins (e.g. Ultimate Membership Pro — indeed-membership-pro) inject
 * post__in / post__not_in into front-end queries to hide protected content from
 * non-authorised or logged-out visitors. That flips is_post__in / is_post__not_in
 * in the query-identity hash FE uses to match a Filter Set to a page query — so a
 * page that filters correctly for the (unrestricted) admin who saved the set stops
 * matching for restricted visitors, and filtering silently does nothing for them.
 *
 * We normalise those two flags back to their save-time value (false), but ONLY when
 * such a plugin is actually active. This keeps every saved query hash untouched on
 * sites that don't have this conflict. The per-page order counter still distinguishes
 * genuinely different same-shape queries, so filtering stays correct.
 */
add_filter('wpc_check_broken_query_vars', function ($query_vars, $query) {
    // Add markers of other post-hiding plugins here if needed; overridable via the filter.
    $context_hides_posts = defined('IHC_PATH')          // Ultimate Membership Pro
        || defined('WCB2B_PLUGIN_FILE');                // WooCommerce B2B — hides "unallowed" products from guests/other groups via post__not_in

    if ( ! apply_filters('wpc_context_hides_posts', $context_hides_posts, $query) ) {
        return $query_vars;
    }

    // Only touch queries FE still considers filterable (its own callback empties the
    // rest at priority 10); never re-populate an emptied set.
    if ( is_array($query_vars) && array_key_exists('is_post__not_in', $query_vars) ) {
        $query_vars['is_post__in']     = false;
        $query_vars['is_post__not_in'] = false;
    }

    return $query_vars;
}, 20, 2);

function flrt_remove_level_array($array)
{
    /**
     * @feature maybe rewrite this full of shame code
     */
    if (!is_array($array)) {
        return [];
    }

    $flatten = [];

    array_map(function ($a) use (&$flatten) {
        if (is_array($a)) {
            $flatten = array_merge($flatten, $a);
        }
    },
            $array);

    return $flatten;
}

add_filter('wpc_check_errors_ids', function ($error_ids, $query) {
    $source_key = 'flrt_detected_source';
    if (empty($query->query_vars[$source_key])) {
        return $error_ids;
    }

    $source = $query->query_vars[$source_key];
    $detector_class = 'FilterEverything\\Filter\\WP_Query_Source_Detector';
    $is_allowed_method = 'is_allowed';

    if (defined('FLRT_FILTERS_PRO')) {
        if (defined('FLRT_PRO_BUILDER_KEY')) {
            $builder_key = apply_filters('wpc_builder_key_pro', $source, constant('FLRT_PRO_BUILDER_KEY'));
            if ($detector_class::$is_allowed_method($builder_key)) {
                return $error_ids;
            }
        }
    }

    if (!defined('FLRT_FILTERS_PRO')) {
        $builder_key = apply_filters('wpc_builder_key', $source, $detector_class::$builder_key);

        if ($detector_class::$is_allowed_method($builder_key)) {
            return $error_ids;
        }
    }

    return [];
}, 10, 2);

function flrt_get_forbidden_prefixes()
{
    //@todo it seems all existing tax prefixes should be there
    // All them actual only when permalinks off
    $forbidden_prefixes = ['srch'];
    $permalinksEnabled = defined('FLRT_PERMALINKS_ENABLED') ? FLRT_PERMALINKS_ENABLED : false;
    if (!$permalinksEnabled) {
        $forbidden_prefixes = array_merge($forbidden_prefixes, array('cat', 'tag', 'page', 'author'));
    }

    if (flrt_wpml_active()) {
        $wpml_url_format = apply_filters('wpml_setting', 0, 'language_negotiation_type');
        if ($wpml_url_format === '3') {
            $forbidden_prefixes[] = 'lang';
        }
    }

    return apply_filters('wpc_forbidden_prefixes', $forbidden_prefixes);
}

function flrt_get_forbidden_meta_keys()
{
    $forbidden_meta_keys = array('wpc_filter_set_post_type', 'wpc_seo_rule_post_type');
    return apply_filters('wpc_forbidden_meta_keys', $forbidden_meta_keys);
}

function flrt_array_contains_duplicate($array)
{
    return count($array) != count(array_unique($array));
}

function flrt_create_filters_nonce()
{
    return FilterSet::createNonce();
}

function flrt_get_filter_fields_mapping()
{
    return Container::instance()->getFilterFieldsService()->getFieldsMapping();
}

function flrt_get_configured_filters($post_id)
{
    $filterFields = Container::instance()->getFilterFieldsService();
    return $filterFields->getFiltersInputs($post_id);
}

function flrt_get_filter_view_name($view_key)
{
    $view_options = FilterFields::getViewOptions();
    if (isset($view_options[$view_key])) {
        return esc_html($view_options[$view_key]);
    }

    return esc_html($view_key);
}

function flrt_get_filter_entity_name($entity_key)
{
    $em = Container::instance()->getEntityManager();
    $entities = $em->getPossibleEntities();

    foreach ($entities as $key => $entity_array) {
        if (isset($entity_array['entities'][$entity_key])) {
            return esc_html($entity_array['entities'][$entity_key]);
        }
    }

    if ($entity_key === 'post_meta_exists' && !defined('FLRT_FILTERS_PRO')) {
        return esc_html__('Available in PRO', 'filter-everything');
    }

    return esc_html($entity_key);
}

function flrt_get_set_settings_fields($post_id)
{
    $filterSet = Container::instance()->getFilterSetService();
    return $filterSet->getSettingsTypeFields($post_id);
}

function flrt_get_set_settings_location_fields($post_id)
{
    $filterSet = Container::instance()->getFilterSetService();
    return $filterSet->getSettingsLocationTypeFields($post_id);
}

function flrt_extract_vars(&$array, $keys)
{
    $r = [];
    foreach ($keys as $key) {
        $var = flrt_extract_var($array, $key);
        if ($var) {
            $r[$key] = $var;
        }
    }
    return $r;
}

function flrt_extract_var(&$array, $key, $default = null)
{
    // check if exists
    // - uses array_key_exists to extract NULL values (isset will fail)
    if (is_array($array) && array_key_exists($key, $array)) {
        $v = $array[$key];
        unset($array[$key]);
        return $v;
    }
    return $default;
}

function flrt_get_empty_filter($set_id)
{
    $filterFields = Container::instance()->getFilterFieldsService();
    return $filterFields->getEmptyFilterObject($set_id);
}

function flrt_excluded_taxonomies()
{
    $excluded_taxonomies = array(
            'nav_menu',
            'link_category',
            'post_format',
            'template_category',
            'element_category',
            'fusion_tb_category',
            'slide-page',
            'elementor_font_type',
            'post_translations',
            'term_language',
            'term_translations',
            'wp_theme',
            'wp_template_part_area',
            'wp_pattern_category',
            'elementor_library_type',
            'elementor_library_category',
    );

    return apply_filters('wpc_excluded_taxonomies', $excluded_taxonomies);
}

function flrt_force_non_unique_slug($notNull, $originalSlug)
{
    return $originalSlug;
}

function flrt_redirect_to_error($post_id, $errors)
{
    $redirect = get_edit_post_link($post_id, 'url');
    $error_code = 20; // Default error code

    if (!empty($errors) && is_array($errors)) {
        $error_code = reset($errors);
    }

    $redirect = add_query_arg('message', $error_code, $redirect);
    wp_redirect($redirect);
    exit;
}

function flrt_sanitize_int($var)
{
    return preg_replace('/[^\d]+/', '', $var);
}

function flrt_get_query_string_parameters()
{
    $container = Container::instance();
    $get = $container->getTheGet();
    $post = $container->getThePost();

    // For compatibility with some Nginx configurations
    unset($get['q']);

    if (isset($post['flrt_ajax_link'])) {
        $parts = parse_url($post['flrt_ajax_link']);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $output);
            return $output;
        }
    }

    return $get;
}

function flrt_get_option($key, $default = false)
{
    $settings = get_option('wpc_filter_settings');

    if (isset($settings[$key])) {
        return apply_filters('wpc_get_option', $settings[$key], $key);
    }

    if ($default) {
        return $default;
    }

    return false;

}

function flrt_remove_option($key)
{
    $settings = get_option('wpc_filter_settings');

    if (isset($settings[$key]) && $settings[$key]) {
        unset($settings[$key]);
        return update_option('wpc_filter_settings', $settings);
    }

    return false;
}

function flrt_change_option($option_key, $key, $value, $autoload = false)
{
    $settings = get_option($option_key);

    if ($settings[$key] === $value) {
        return true;
    }

    $settings = is_array($settings) ? $settings : [];
    $settings[$key] = $value;

    return update_option($option_key, $settings, $autoload);
}

function flrt_get_experimental_option($key, $default = false)
{
    /**
     * @todo This should be rewritten
     */
    $settings = get_option('wpc_filter_experimental');

    if (isset($settings[$key])) {
        return apply_filters('wpc_get_option', $settings[$key], $key);
    }

    if ($default !== false) {
        return apply_filters('wpc_get_option', $default, $key);
    }

    return apply_filters('wpc_get_option', false, $key);

}

function flrt_term_id($name, $filter, $id, $echo = true)
{
    $attr = esc_attr("wpc-" . $name . "-" . $filter['entity'] . "-" . esc_attr($filter['e_name']) . "-" . $id);
    if ($echo) {
        echo $attr;
    } else {
        return $attr;
    }
}

function flrt_get_plugin_name()
{
    if (defined('FLRT_FILTERS_PRO')) {
        return esc_html__('Filter Everything Pro', 'filter-everything');
    } else {
        return esc_html__('Filter Everything', 'filter-everything');
    }
}

function flrt_get_plugin_url($type = 'about', $full = false)
{
    if ($full) {
        return esc_url($full);
    }

    return esc_url(FLRT_PLUGIN_URL . '/' . $type);
}

function flrt_get_term_by_slug($prefix)
{
    global $wpdb;

    $sql = "SELECT {$wpdb->terms}.slug FROM {$wpdb->terms} WHERE {$wpdb->terms}.slug = '%s'";
    $sql = $wpdb->prepare($sql, $prefix);
    $result = $wpdb->get_row($sql);

    if (isset($result->slug) && $result->slug) {
        return $result->slug;
    }

    return false;
}

function flrt_walk_terms_tree($terms, $args)
{
    _deprecated_function('flrt_walk_terms_tree', '1.7.6', 'flrt_filter_walk_terms_tree()');
    flrt_filter_walk_terms_tree($terms, $args);
}

function flrt_filter_walk_terms_tree($terms, $args)
{
    $walker = new \FilterEverything\Filter\WalkerCheckbox();

    $depth = -1;
    if (isset($args['filter']['hierarchy']) && $args['filter']['hierarchy'] === 'yes') {
        $depth = 10;
    }

    return $walker->walk($terms, $depth, $args);
}

function flrt_get_all_parents($elements, $parent_id, &$ids)
{
    if (isset($elements[$parent_id]->parent) && $elements[$parent_id]->parent > 0) {
        $id = $elements[$parent_id]->parent;
        $ids_flipped = array_flip($ids);

        if (!isset($ids_flipped[$id])) {
            $ids[] = $id;
        }

        flrt_get_all_parents($elements, $id, $ids);
    } else {
        return $ids;
    }
}

function flrt_get_parents_with_not_empty_children($elements, $key = 'cross_count')
{
    $has_posts_in_children = [];

    if (empty($elements) || !is_array($elements)) {
        return $has_posts_in_children;
    }

    $new_elements = [];

    foreach ($elements as $k => $e) {
        $new_elements[$e->term_id] = $e;
    }

    $has_posts_in_children_flipped = array_flip($has_posts_in_children);

    foreach ($new_elements as $e) {
        if (isset($e->parent) && !empty($e->parent) && $e->$key > 0) {
            // Find all parents for term that contains posts
            if (!isset($has_posts_in_children_flipped[$e->parent])) {
                $has_posts_in_children[] = $e->parent;
            }

            flrt_get_all_parents($new_elements, $e->parent, $has_posts_in_children);
        }
    }

    return $has_posts_in_children;
}

function flrt_find_all_descendants($arr)
{
    $all_results = [];

    if (empty($arr) || !is_array($arr)) {
        return $all_results;
    }

    foreach ($arr as $k => $v) {
        $curr_result = [];

        for ($stack = [$k]; count($stack);) {
            $el = array_pop($stack);

            if (array_key_exists($el, $arr) && is_array($arr[$el])) {
                foreach ($arr[$el] as $child) {
                    $curr_result [] = $child;
                    $stack [] = $child;
                }
            }
        }

        if (count($curr_result)) {
            $all_results[$k] = $curr_result;
        }
    }

    return $all_results;
}

function flrt_debug_title()
{

    echo '<div class="wpc-debug-title">' . esc_html__('Filter Everything debug', 'filter-everything');
    echo '&nbsp;' . flrt_help_tip(
                    sprintf(
                            __('Debug messages are visible for logged in administrators only. You can disable them in Filters -> <a href="%s">Settings</a> -> Debug mode.', 'filter-everything'),
                            admin_url('edit.php?post_type=filter-set&page=filters-settings')
                    ), true) . '</div>';
}

function flrt_is_debug_mode()
{
    $debug_mode = false;
    if (flrt_get_option('widget_debug_messages') === 'on') {
        if (current_user_can(flrt_plugin_user_caps())) {
            $debug_mode = true;
        }
    }

    return $debug_mode;
}

function flrt_clean($var)
{
    if (is_array($var)) {
        return array_map('flrt_clean', $var);
    } else {
        return is_scalar($var) ? sanitize_text_field($var) : $var;
    }
}

function flrt_sorting_option_value($order_by_value, $meta_keys, $orders, $i)
{
    $meta_key = isset($meta_keys[$i]) ? $meta_keys[$i] : '';
    $order = isset($orders[$i]) ? $orders[$i] : '';

    $option_value = $order_by_value;

    if (in_array($order_by_value, ['m', 'n'], true)) {
        $option_value .= $meta_key;
    }

    $option_value .= ($order === 'desc') ? '-' . $order : '';

    return $option_value;
}

function flrt_get_active_plugins()
{

    if (is_multisite()) {
        $active_plugins = get_site_option('active_sitewide_plugins');
        if (is_array($active_plugins)) {
            $active_plugins = array_keys($active_plugins);
        }

        $site_active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
        $active_plugins = array_merge($active_plugins, $site_active_plugins);
    } else {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
    }

    return $active_plugins;
}

/**
 * Version tag for FE transient cache NAMES. Bump it whenever the cached data
 * SHAPE changes (v2 = compact aggregated maps instead of raw SQL rows).
 * Versioned key names keep branches/versions with different cache formats
 * from ever reading each other's transients (e.g. when switching git branches
 * on a dev site) - each format lives under its own keys and stale ones simply
 * expire.
 */
if ( ! defined( 'FLRT_CACHE_FORMAT_SUFFIX' ) ) {
    define( 'FLRT_CACHE_FORMAT_SUFFIX', '_v2' );
}

function flrt_remove_empty_terms($checkTerms, $filter, $has_not_empty_children_flipped = [], $use_apply_button = false)
{

    if (!$use_apply_button) {
        foreach ($checkTerms as $index => $term) {
            if ($filter['hierarchy'] === 'yes') {

                if ($term->cross_count === 0
                        && !isset($has_not_empty_children_flipped[$term->term_id])) {
                    unset($checkTerms[$index]);
                }

            } else {
                if ($term->cross_count === 0) {
                    unset($checkTerms[$index]);
                }
            }
        }
    }

    return $checkTerms;
}

function flrt_get_wp_queried_term($terms)
{
    $wp_queried_terms = false;

    foreach ($terms as $term) {
        if ($term->wp_queried === true) {
            $wp_queried_terms = $term;
            break;
        }
    }

    return $wp_queried_terms;
}

function flrt_get_filter_terms($filter, $posType, $em = false)
{
    if (!$em) {
        $em = Container::instance()->getEntityManager();
    }

    $entityObj = $em->getEntityByFilter($filter, $posType);

    // The filter may lack entity/e_name (e.g. a parent_filter reference
    // to a filter that is absent from the rendered set)
    if (!$entityObj) {
        return [];
    }

    // Exclude or include terms
    $isInclude = (isset($filter['include']) && $filter['include'] === 'yes');
    $entityObj->setExcludedTerms($filter['exclude'], $isInclude);

    $terms = $entityObj->getTerms();

    return apply_filters('wpc_items_after_calc_term_count', $terms);
}

function flrt_str_replace($string = '', $search_replace = array())
{
    $ignore = array();
    unset($search_replace['']);

    foreach ($search_replace as $search => $replace) {
        if (in_array($search, $ignore)) {
            continue;
        }
        if (strpos($string, $search) === false) {
            continue;
        }
        $string = str_replace($search, $replace, $string);
        $ignore[] = $replace;
    }

    return $string;
}

function flrt_string_polyfill($data) {
        return map_deep( $data,'flrt_string_polyfill_body');
}

function flrt_string_polyfill_body( $string ){

    if ( ! is_string( $string ) ) {
        return $string;
    }

    $str = preg_replace('/\x00|<[^>]*>?/', '', $string );
    return str_replace( ["'", '"'], ['&#39;', '&#34;'], $str );
}

if (!function_exists('flrt_set_transient')) {
    function flrt_set_transient($transient, $value, $expiration = 0)
    {
        if (defined('FLRT_SET_TRANSIENT_ENABLED') && FLRT_SET_TRANSIENT_ENABLED) {
            set_transient($transient, $value, $expiration);
        }
    }
}

if (!function_exists('flrt_get_transient')) {
    function flrt_get_transient($transient)
    {
        if (defined('FLRT_SET_TRANSIENT_ENABLED') && FLRT_SET_TRANSIENT_ENABLED) {
            return get_transient($transient);
        }
        return false;
    }
}

if (!class_exists('FlrtWooDiscountRules')) {
    class FlrtWooDiscountRules
    {

        protected $rules;

        protected $base;
        protected $rule_helper;
        protected $discount_calculator;
        protected $manage_discount;

        //public $filter;
        public function __construct()
        {
            $this->base = new Wdr\App\Controllers\Base();
            $this->rule_helper = new Wdr\App\Helpers\Rule();
            $this->manage_discount = new Wdr\App\Controllers\ManageDiscount();
            $this->rules = $this->manage_discount->getDiscountRules();
            $this->discount_calculator = new Wdr\App\Controllers\DiscountCalculator($this->rule_helper->getAvailableRules($this->base->getAvailableConditions()));
        }

        public function getProductPriceToDisplay($product)
        {
            return $this->discount_calculator->getProductPriceToDisplay($product, 1);
        }
    }

    function flrt_woo_discount_rules_class()
    {
        return new FlrtWooDiscountRules();
    }
}


if (!function_exists('flrt_is_sitemap_exists')) {
    function flrt_is_sitemap_exists()
    {
        $filepath = rtrim(FLRT_XML_PATH, '/\\') . '/filter-sitemap-index.xml';
        return file_exists($filepath);
    }
}
if (!function_exists('flrt_get_index_sitemap')) {
    function flrt_get_index_sitemap()
    {
        return fltr_get_url_from_absolute_path(FLRT_XML_PATH . '/filter-sitemap-index.xml');
    }
}

if (!function_exists('fltr_get_url_from_absolute_path')) {
    function fltr_get_url_from_absolute_path($absolute_path)
    {
        $wp_root_path = realpath(ABSPATH);
        $wp_url = site_url();

        $file_path = realpath($absolute_path);

        if (!$file_path || strpos($file_path, $wp_root_path) !== 0) {
            return false;
        }


        $relative_path = str_replace($wp_root_path, '', $file_path);
        $relative_path = str_replace('\\', '/', $relative_path);

        return rtrim($wp_url, '/') . $relative_path;
    }
}

if (!function_exists('flrt_has_filter_seo_rules')) {
    function flrt_has_filter_seo_rules()
    {
        $args = [
                'post_type'      => 'filter-seo-rule',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
        ];

        $query = new WP_Query($args);
        return $query->have_posts();
    }
}

if (!function_exists('flrt_get_last_modified_filter_seo_rule')) {
    function flrt_get_last_modified_filter_post_type($post_type)
    {
        global $wpdb;
        $sql = "
        SELECT post_modified
        FROM {$wpdb->posts}
        WHERE post_type = '%s'
          AND post_status IN ('publish', 'trash')
        ORDER BY post_modified DESC
        LIMIT 1";
        $query = $wpdb->prepare($sql, $post_type);
        $last_modified = $wpdb->get_var($query);

        if (!$last_modified) {
            return false;
        }
        return $last_modified;
    }
}

if (!function_exists('flrt_check_to_update_xml')) {
    function flrt_check_to_update_xml()
    {
        $wpc_xml_write_date = get_option('wpc_xml_write_date');
        if (!$wpc_xml_write_date) return false;

        $last_modified_filter_seo_rule = flrt_get_last_modified_filter_post_type(FLRT_SEO_RULES_POST_TYPE);
        $last_modified_filter_set = flrt_get_last_modified_filter_post_type(FLRT_FILTERS_SET_POST_TYPE);

        if (!$last_modified_filter_seo_rule && !$last_modified_filter_set) return false;

        $wpc_xml_write_date = strtotime($wpc_xml_write_date);
        $last_modified_filter_seo_rule = strtotime($last_modified_filter_seo_rule);
        $last_modified_filter_set = strtotime($last_modified_filter_set);

        if ($last_modified_filter_seo_rule > $wpc_xml_write_date || $last_modified_filter_set > $wpc_xml_write_date) {
            return true;
        }
        if ($wpc_xml_write_date !== false && !flrt_has_filter_seo_rules()) {
            return true;
        }
        return false;
    }
}


if (!function_exists('flrt_post_type_underline_transform')) {
    function flrt_post_type_underline_transform($post_type)
    {
        if (mb_strpos($post_type, '-') !== false) {
            return str_replace('-', '_', $post_type);
        }
        return $post_type;
    }
}


if (!function_exists('flrt_generate_unique_copy_title')) {

    /**
     * Generates a unique copy title in the format:
     * "Base Title – {copy_text} {N}".
     *
     * @param string $original_title The original post title to derive the base title from.
     *                               If it already ends with "– {copy_text} N", that suffix is stripped.
     * @param string $post_type The WordPress post type within which to check for duplicate titles.
     *                               By default expects the FLRT_FILTERS_SET_POST_TYPE constant.
     * @param string $copy_text The suffix text for copies (e.g., 'copy', 'duplicate').
     * @param int $start_number Numbering threshold:
     *                               - if 0 (default), the first copy gets number "1";
     *                               - if 1, the first copy has no number; numbering appears only when
     *                                 a "… – {copy_text} 1" already exists.
     *
     * @return string The generated unique copy title.
     */

    function flrt_generate_unique_copy_title($original_title, $post_type = FLRT_FILTERS_SET_POST_TYPE, $copy_text = 'copy', $number_position = true)
    {
        global $wpdb;
        if ($number_position) {
            $preg_match_pattern = '/^(.*) – ' . preg_quote($copy_text, '/') . ' \d+$/';
        }

        if (!$number_position) {
            $preg_match_pattern = '/^(.*) \d+ – ' . preg_quote($copy_text, '/') . '$/';
        }

        if (preg_match($preg_match_pattern, $original_title, $matches)) {
            $base_title = $matches[1];
        } else {
            $base_title = $original_title;
        }

        if ($number_position) {
            $copy_pattern = $wpdb->esc_like($base_title) . ' – ' . $wpdb->esc_like($copy_text) . '%';
            $titles = $wpdb->get_col(
                    $wpdb->prepare(
                            "SELECT post_title FROM $wpdb->posts
             WHERE (post_title = %s OR post_title LIKE %s)
             AND post_type = %s",
                            $base_title,
                            $copy_pattern,
                            $post_type
                    )
            );
        }
        if (!$number_position) {
            $copy_pattern = $wpdb->esc_like($base_title) . ' % – ' . $wpdb->esc_like($copy_text);
            $titles = $wpdb->get_col(
                    $wpdb->prepare(
                            "SELECT post_title FROM $wpdb->posts
             WHERE post_title LIKE %s
             AND post_type = %s",
                            $copy_pattern,
                            $post_type
                    )
            );
        }

        $max_copy_number = 0;

        if ($number_position) {
            $preg_match_pattern_title = '/^' . preg_quote($base_title, '/') . ' – ' . preg_quote($copy_text, '/') . ' (\d+)$/';

        }
        if (!$number_position) {
            $preg_match_pattern_title = '/^' . preg_quote($base_title, '/') . ' (\d+) – ' . preg_quote($copy_text, '/') . '$/';
        }

        foreach ($titles as $title) {
            if (preg_match($preg_match_pattern_title, $title, $m)) {
                if (isset($m[1]) && is_numeric($m[1])) {
                    $num = intval($m[1]);
                    if ($num > $max_copy_number) {
                        $max_copy_number = $num;
                    }
                }
            }
        }

        $new_number = $max_copy_number + 1;

        if ($number_position) {
            $text = $base_title . ' – ' . $copy_text . ' ' . $new_number;
        }
        if (!$number_position) {
            $text = $base_title . ' ' . $new_number . ' – ' . $copy_text;
        }
        return $text;
    }
}

if (!function_exists('flrt_get_delete_set_transient')) {
    function flrt_refresh_temp_transient($set_name, $data)
    {
        if (get_transient($set_name) !== false) {
            delete_transient($set_name);
        }
        set_transient($set_name, $data, 300);
    }
}

if (!function_exists('flrt_view_admin_error')) {
    function flrt_view_admin_error($text)
    {
        $error_str = '<div class="notice notice-error is-dismissible"><p>%s</p></div>';
        printf(
                $error_str,
                $text
        );
    }
}
if (!function_exists('flrt_export_setting_icon')) {
    function flrt_export_setting_icon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="#99a2b2" width="19px" height="19px" viewBox="0 0 24 20"><polyline id="primary" points="15 3 21 3 21 9" style="fill: none; stroke: rgb(153,162,178); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"/><line id="primary-2" data-name="primary" x1="11" y1="13" x2="21" y2="3" style="fill: none; stroke: rgb(153,162,178); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"/><path id="primary-3" data-name="primary" d="M21,13v7a1,1,0,0,1-1,1H4a1,1,0,0,1-1-1V4A1,1,0,0,1,4,3h7" style="fill: none; stroke: rgb(153,162,178); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"/></svg>';
    }
}

if (!function_exists('flrt_import_setting_icon')) {
    function flrt_import_setting_icon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="#000000" width="19px" height="19px" viewBox="0 0 24 23" id="wpc-import-icon"><polyline id="primary" points="17 13 11 13 11 7" style="fill: none; stroke: rgb(153,162,178); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"/><line id="primary-2" data-name="primary" x1="21" y1="3" x2="11" y2="13" style="fill: none; stroke: rgb(153,162,178); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"/><path id="primary-3" data-name="primary" d="M21,13v7a1,1,0,0,1-1,1H4a1,1,0,0,1-1-1V4A1,1,0,0,1,4,3h7" style="fill: none; stroke: rgb(153,162,178); stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"/></svg>';
    }
}

if (!function_exists('flrt_open_in_new_tab_icon')) {
    function flrt_open_in_new_tab_icon()
    {
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 21" width="18" height="18" aria-hidden="true" focusable="false">
        <path d="M19.5 4.5h-7V6h4.44l-5.97 5.97 1.06 1.06L18 7.06v4.44h1.5v-7Zm-13 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3H17v3a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h3V5.5h-3Z"></path></svg>';
        return wp_kses(
                $icon,
                array(
                        'svg'  => array(
                                'xmlns'       => true,
                                'viewbox'     => true,
                                'width'       => true,
                                'height'      => true,
                                'aria-hidden' => true,
                                'focusable'   => true,
                        ),
                        'path' => array(
                                'd' => true
                        ),
                )
        );
    }
}

if (!function_exists('flrt_vailable_in_pro_attr_link')) {
    function flrt_vailable_in_pro_attr_link($target_blank = false): string
    {
        $link = 'edit.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE . '&page=flrt-pro';
        if ($target_blank) {
            $link .= '_target=blank';
        }
        return $link;
    }
}
if (!function_exists('flrt_pro_promo_label')) {
    function flrt_pro_promo_label($replace_class = false): string
    {
        $class = $replace_class ? 'wpc-pro-badge' : 'wpc-pro-badge-transparent';
        $label = ' <span class="' . $class . '">' . esc_html__('PRO', 'filter-everything') . '</span>';

        return wp_kses($label, [
                'span' => [
                        'class' => []
                ]
        ]);
    }
}

if(!function_exists( 'flrt_unlock_icon')){
    function flrt_unlock_icon($width = '20px', $height = '20px', $color = '#3858E9')
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" height="' . $height . '" viewBox="0 -960 960 960" width="' . $width . '" fill="'  . $color . '"><path d="M264-624h336v-96q0-50-35-85t-85-35q-50 0-85 35t-35 85h-72q0-80 56.23-136 56.22-56 136-56Q560-912 616-855.84q56 56.16 56 135.84v96h24q29.7 0 50.85 21.15Q768-581.7 768-552v384q0 29.7-21.16 50.85Q725.68-96 695.96-96H263.72Q234-96 213-117.15T192-168v-384q0-29.7 21.15-50.85Q234.3-624 264-624Zm0 456h432v-384H264v384Zm216.21-120Q510-288 531-309.21t21-51Q552-390 530.79-411t-51-21Q450-432 429-410.79t-21 51Q408-330 429.21-309t51 21ZM264-168v-384 384Z"/></svg>';
    }
}

if(!function_exists( 'flrt_crown_icon')){
    function flrt_crown_icon($width = '16px', $height = '16px', $color = '#FFFFFF')
    {   
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="'  . $color . '" viewBox="0 0 16 16" width="' . $width . '" height="' . $height . '" class="wpc-crown-icon"><path fill="'  . $color . '" d="M8.687 1.932c-.238-.634-1.136-.634-1.374 0l-1.642 4.38-3.167-2.11a.733.733 0 0 0-1.126.753L3.333 12h9.334l1.955-7.045a.733.733 0 0 0-1.126-.754L10.33 6.313zM6.654 7.49 8 3.899l1.346 3.59c.166.442.7.614 1.094.352l2.59-1.727-1.363 4.553H4.333L2.97 6.114 5.56 7.841a.733.733 0 0 0 1.094-.352m6.013 5.844H3.333v1.334h9.334z"></path></svg>';
    }
}

if(!function_exists( 'flrt_unlock_in_pro')){
    function flrt_unlock_in_pro($button_text = '')
    {
        $string = '<a class="wpc-available-in-pro-button" href="' . admin_url(flrt_vailable_in_pro_attr_link()) . '">';
        $string .= !empty($button_text) ? $button_text . ' - ': '';
        $string .= flrt_unlock_icon();
        $string  .=  '<span>' . esc_html__('Unlock with PRO', 'filter-everything') . '</span></a>';
        return $string;
    }
}


add_filter('wpc_filter_default_fields', 'flrt_add_pro_promo_fields', 10, 2);


add_action('wp_footer', function () {
    if (current_user_can(flrt_plugin_user_caps()))
        if (!wp_script_is('wpc-filter-everything')) {
            {
                ?>
                <script>
                    jQuery(function ($) {
                        $(document).on('click', '.wpc-open-close-filters-button', function (e) {
                            e.preventDefault();
                            let openCloseButton = $(this);
                            let wpcButtonFilterSetError = openCloseButton.data('wpcButtonFilterSetError');
                            let wpcButtonWidgetError = openCloseButton.data('wpcButtonWidgetError');

                            if (typeof wpcButtonFilterSetError !== 'undefined') {
                                alert(wpcButtonFilterSetError);
                            }
                            if (typeof wpcButtonWidgetError !== 'undefined') {
                                if (typeof window.wpcFilterWidgetActive === 'undefined') {
                                    alert(wpcButtonWidgetError);
                                }
                            }
                        });
                    });
                </script>
                <?php
            }
        }
});

function flrt_log( $message, $data = null ) {
    if ( ! defined('WP_DEBUG_LOG') || ! WP_DEBUG_LOG ) {
        return;
    }

    $log = '[Filter Everything] ' . $message;

    if ( $data !== null ) {
        $log .= ' | ' . wp_json_encode( $data );
    }

    error_log( $log );
}

function prepare_wpc_filter_set_json_data($postData)
{
    if(!empty($postData['wpc_filter_set_json_data'])){
        $postData = json_decode(stripslashes($postData['wpc_filter_set_json_data']), true);
        $postData['wpc_set_fields']['wp_filter_query_vars'] = wp_slash($postData['wpc_set_fields']['wp_filter_query_vars']);
    }
    return $postData;
}
