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
    global $flrt_sets;

    if (function_exists('brizy_load')) {
        if (!did_action('wp_print_scripts')) {
            return false;
        }
    }

    if ($set_id) {
        foreach ($flrt_sets as $k => $set) {
            if ($set['ID'] === $set_id) {
                unset($flrt_sets[$k]);
                return $set;
            }
        }
    }

    return array_shift($flrt_sets);
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

function flrt_sanitize_tooltip($var )
{
    return htmlspecialchars(
            wp_kses(
                    html_entity_decode($var),
                    array(
                            'br'     => array(),
                            'em'     => array(),
                            'strong' => array(),
                            'small'  => array(),
                            'span'   => array(),
                            'ul'     => array(),
                            'li'     => array(),
                            'ol'     => array(),
                            'p'      => array(),
                            'a'      => array('href' => true)
                    )
            )
    );
}

function flrt_help_tip($tip, $allow_html = false)
{
    if ($allow_html) {
        $tip = flrt_sanitize_tooltip($tip);
    } else {
        $tip = esc_attr($tip);
    }

    return '<span class="wpc-help-tip" data-tip="' . $tip . '"></span>';
}

function flrt_tooltip($attr)
{
    if (!isset($attr['tooltip']) || !$attr['tooltip']) {
        return false;
    }

    return flrt_help_tip($attr['tooltip'], true);
}

function flrt_field_instructions($attr)
{
    if (!isset($attr['instructions']) || !$attr['instructions']) {
        return false;
    }
    $instructions = wp_kses(
            $attr['instructions'],
            array(
                    'br'     => array(),
                    'span'   => array('class' => true),
                    'strong' => array(),
                    'a'      => array('href' => true, 'title' => true)
            )
    );
    return '<p class="wpc-field-description">' . $instructions . '</p>';
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
    $context_hides_posts = defined('IHC_PATH'); // Ultimate Membership Pro

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

function flrt_maybe_hide_row($atts)
{
    if ($atts['type'] === 'Hidden') {
        echo ' style="display:none;"';
    }
}

function flrt_filter_row_class($field_atts)
{
    $classes = ['wpc-filter-tr'];

    if (isset($field_atts['class'])) {
        $classes[] = $field_atts['class'] . '-tr';
    }

    if (isset($field_atts['additional_class'])) {
        $classes[] = $field_atts['additional_class'];
    }

    return implode(" ", $classes);
}


function flrt_include_admin_view($path, $args = [])
{
    $templateManager = Container::instance()->getTemplateManager();
    $templateManager->includeAdminView($path, $args);
}

function flrt_include_front_view($path, $args = [])
{
    $templateManager = Container::instance()->getTemplateManager();
    $templateManager->includeFrontView($path, $args);
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

function flrt_render_input($atts)
{
    $className = isset($atts['type']) ? '\FilterEverything\Filter\\' . $atts['type'] : '\FilterEverything\Filter\Text';

    if (class_exists($className)) {
        $input = new $className($atts);
        return $input->render();
    }

    return false;
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

function flrt_range_input_name($slug, $edge = 'min', $type = 'num')
{
    if ($type === 'date') {
        return PostDateEntity::inputName($slug, $edge);
    }

    return PostMetaNumEntity::inputName($slug, $edge);
}

function flrt_query_string_form_fields($values = null, $exclude = [], $current_key = '', $return = false)
{

    $filter_everything_exclude = array_keys(apply_filters('wpc_unnecessary_get_parameters', []));
    $exclude = array_merge($exclude, $filter_everything_exclude);

    if (is_null($values)) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $values = Container::instance()->getTheGet();
        // For compatibility with some Nginx configurations
        unset($values['q']);
    } elseif (is_string($values)) {
        $url_parts = wp_parse_url($values);
        $values = [];

        if (!empty($url_parts['query'])) {
            // This is to preserve full-stops, pluses and spaces in the query string when ran through parse_str.
            $replace_chars = array(
                    '.' => '{dot}',
                    '+' => '{plus}',
            );

            $query_string = str_replace(array_keys($replace_chars), array_values($replace_chars), $url_parts['query']);

            // Parse the string.
            parse_str($query_string, $parsed_query_string);

            // Convert the full-stops, pluses and spaces back and add to values array.
            foreach ($parsed_query_string as $key => $value) {
                $new_key = str_replace(array_values($replace_chars), array_keys($replace_chars), $key);
                $new_value = str_replace(array_values($replace_chars), array_keys($replace_chars), $value);
                $values[$new_key] = $new_value;
            }
        }
    }
    $html = '';

    foreach ($values as $key => $value) {
        if (in_array($key, $exclude, true)) {
            continue;
        }
        if ($current_key) {
            $key = $current_key . '[' . $key . ']';
        }
        if (is_array($value)) {
            $html .= flrt_query_string_form_fields($value, $exclude, $key, true);
        } else {
            $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(wp_unslash($value)) . '" />';
        }
    }

    if ($return) {
        return $html;
    }

    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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


function flrt_count($term, $show = 'yes')
{
    _deprecated_function('flrt_count', '1.7.6', 'flrt_filter_count()');
    flrt_filter_count($term, $show);
}

if (!function_exists('flrt_filter_count')) {
    function flrt_filter_count($term, $show = 'yes')
    {
        if ($show === 'yes') :
            echo flrt_filter_get_count($term);
        endif;
    }
}

/**
 * @param $term
 * @return string
 * @since 1.0.5
 */
function flrt_filter_get_count($term)
{
    return '<span class="wpc-term-count"><span class="wpc-term-count-brackets-open">(</span><span class="wpc-term-count-value">' . esc_html($term->cross_count) . '</span><span class="wpc-term-count-brackets-close">)</span></span>&nbsp;';
}

if (!function_exists('flrt_spinner_html')) {
    function flrt_spinner_html()
    {
        return '<div class="wpc-spinner"></div>';
    }
}

function flrt_filters_widget_content_class($setId)
{
    if (isset($_COOKIE[FLRT_OPEN_CLOSE_BUTTON_COOKIE_NAME])) {

        if ($_COOKIE[FLRT_OPEN_CLOSE_BUTTON_COOKIE_NAME] === $setId) {
            return ' wpc-opened';
        } else {
            return ' wpc-closed';
        }
    }
}

function flrt_filters_button($setId = 0, $class = '', $wrap_class = '')
{
    /**
     * @feature add nice wrapper to this functions to allow users put it into themes.
     */
    $classes = [];
    $sets = [];
    $wpManager = \FilterEverything\Filter\Container::instance()->getWpManager();
    $templateManager = \FilterEverything\Filter\Container::instance()->getTemplateManager();

    $draft_sets = $wpManager->getQueryVar('wpc_page_related_set_ids');

    if ( ! is_array( $draft_sets ) ) {
        $draft_sets = [];
    }

    foreach ($draft_sets as $set) {
        if (isset($set['show_on_the_page']) && $set['show_on_the_page']) {
            $sets[] = $set;
        }
    }

    if (!$setId && isset($sets[0]['ID'])) {
        $setId = $sets[0]['ID'];
    }

    foreach ($sets as $set) {
        if ($set['ID'] === $setId) {
            $theSet = $set;
            break;
        }
    }

    if (flrt_get_option('mobile_filter_settings') === 'show_bottom_widget') {
        $classes[] = 'wpc-filters-open-widget';
    } else {
        $classes[] = 'wpc-open-close-filters-button';
    }

    if ($class) {
        $classes[] = trim($class);
    }

    $attrClass = implode(" ", $classes);
    $setId = preg_replace('/[^\d]+/', '', $setId);

    $wpc_found_posts = NULL;
    $srch = isset($_GET['srch']) ? filter_input(INPUT_GET, 'srch', FILTER_SANITIZE_SPECIAL_CHARS) : '';
    $all = false;
    if ($srch) {
        $all = true;
    }

    if ($wpManager->getQueryVar('wpc_is_filter_request') || $srch) {
        $wpc_found_posts = flrt_posts_found_quantity($setId, $all);
    }

    $button_error = [];

    if (current_user_can(flrt_plugin_user_caps())) {
        $button_error = [
                'filter_set_error'    => esc_html__('There are no filter sets on this page.', 'filter-everything'),
                'filter_widget_error' => esc_html__('There is no filter widget on this page.', 'filter-everything'),
        ];

        if (!empty($theSet)) {
            unset($button_error['filter_set_error']);
        }
    }

    $templateManager->includeFrontView('filters-button', array('wpc_found_posts' => $wpc_found_posts, 'class' => $attrClass, 'set_id' => $setId, 'wrap_class' => $wrap_class, 'button_error' => $button_error));
}

function flrt_posts_found($setid = 0, $all = false, $hidden = false )
{
    $templateManager = \FilterEverything\Filter\Container::instance()->getTemplateManager();
    $fss = \FilterEverything\Filter\Container::instance()->getFilterSetService();

    if (isset($_GET['srch']) && $_GET['srch']) {
        $all = true;
    }
    $count = flrt_posts_found_quantity($setid, $all);

    $theSet = $fss->getSet($setid);
    $postType = isset($theSet['post_type']['value']) ? $theSet['post_type']['value'] : '';

    $obj = get_post_type_object($postType);
    $pluralLabel = isset($obj->label) ? apply_filters('wpc_label_singular_posts_found_msg', $obj->label) : esc_html__('items', 'filter-everything');
    $singularLabel = isset($obj->labels->singular_name) ? apply_filters('wpc_label_plural_posts_found_msg', $obj->labels->singular_name) : esc_html__('item', 'filter-everything');

    $templateManager->includeFrontView('posts-found', array('posts_found_count' => $count, 'singular_label' => $singularLabel, 'plural_label' => $pluralLabel, 'hidden' => $hidden));
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

function flrt_get_status_css_class($id, $cookieName, $classes = ['opened' => 'wpc-opened', 'closed' => 'wpc-closed'])
{

    if (isset($_COOKIE[$cookieName])) {
        $openediDs = explode(",", $_COOKIE[$cookieName]);

        if (in_array($id, $openediDs)) {
            return $classes['opened'];
        } elseif (in_array(-$id, $openediDs)) {
            return $classes['closed'];
        } else {
            return '';
        }
    }

    return '';
}

if (!function_exists('flrt_filter_header')) {
    function flrt_filter_header($filter, $terms)
    {
        $openButton = ($filter['collapse'] === 'yes') ? '<button><span class="wpc-wrap-icons">' : '';
        $closeButton = ($filter['collapse'] === 'yes') ? '</span><span class="wpc-open-icon"></span></button>' : '';
        $tooltip = '';

        if ($filter['collapse'] === 'yes' && !empty($filter['values']) && !empty($terms)) {
            $selected = [];
            $list = '<span class="wpc-filter-selected-values">&mdash; ';
            // Does not work for numeric filters
            // @todo
            foreach ($terms as $id => $term_object) {

                if (in_array($term_object->slug, $filter['values'])) {
                    $selected[] = $term_object->name;
                }
            }

            $list .= implode(", ", $selected) . '</span>';

            $closeButton = $list . $closeButton;
        }

        if (isset($filter['tooltip']) && $filter['tooltip']) {
            $tooltip = flrt_help_tip($filter['tooltip'], true);
        }

        $filter_label = apply_filters('wpc_filter_title', $filter['label'], $filter);

        ?>
        <div class="wpc-filter-header">
        <div class="widget-title wpc-filter-title"><?php
            echo $openButton . esc_html($filter_label) . $tooltip . $closeButton;
            ?></div></div><?php
    }
}

function flrt_filter_class($filter, $default_classes = [], $terms = [], $args = [])
{
    $digits = [];
    $length = 1;
    $isHideEmpty = (!empty($args['hide_empty']) && $args['hide_empty'] === 'yes');
    $isParentFilter = flrtParentFilter($filter);
    $count_terms_greater_than_zero = 0;
    $total_terms_count = 0;

    if ( ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $digits[] = $term->cross_count;

            if ( $term->cross_count > 0 ) {
                $total_terms_count += $term->cross_count;
            }

            $is_visible = ! $isParentFilter || ( isset( $term->show_with_parent ) && $term->show_with_parent === true ) || !isset($term->show_with_parent);
            $has_results = ! $isHideEmpty || $term->cross_count > 0;

            if ( $is_visible && $has_results ) {
                $count_terms_greater_than_zero++;
            }
        }
    }

    if (!empty($digits)) {
        $length = strlen((string)max($digits));
    }

    $classes = array(
            'wpc-filters-section',
            'wpc-filters-section-' . esc_attr($filter['ID']),
            'wpc-filter-' . esc_attr($filter['e_name']),
            'wpc-filter-' . esc_attr($filter['entity']),
            'wpc-filter-layout-' . esc_attr($filter['view']),
            'wpc-counter-length-' . esc_attr($length)
    );

    if ((isset($args['hide_empty_filter']) && $args['hide_empty_filter'] === 'yes')) {
        if ($total_terms_count <= 0) {

            $classes[] = 'wpc-filters-section-0';
        }
    }

    if (
            (!empty($filter['parent_filter']) && $filter['parent_filter'] !== '-1')
            && (!empty($filter['hide_until_parent']) && $filter['hide_until_parent'] === 'yes')
            && (isset($args['hide_until_parent_class']) && $filter['hide_until_parent_class'] !== true)
    ) {
        $classes[] = 'wpc-filters-hide-until-parent-section';
    }

    if (isset($filter['values']) && !empty($filter['values'])) {
        $classes[] = 'wpc-filter-has-selected';
    }

    // Set correct more/less class for specific views
    if (in_array($filter['view'], ['checkboxes', 'radio', 'labels'])) {
        if (isset($filter['more_less']) && $filter['more_less'] === 'yes') {

            $classes[] = 'wpc-filter-more-less';

            if (in_array($filter['ID'], flrt_more_less_opened())) {
                $classes[] = 'wpc-show-more-reverse';
            }

            $classes[] = flrt_get_status_css_class($filter['ID'], FLRT_MORELESS_COOKIE_NAME, ['opened' => 'wpc-show-more', 'closed' => 'wpc-show-less']);

            // We have to count only first-level terms if hierarchy is enabled
            if (isset($filter['hierarchy']) && $filter['hierarchy'] === 'yes') {
                if (!empty($terms)) {
                    $only_parents = [];
                    foreach ($terms as $term_id => $term) {
                        if ($term->parent == 0) {
                            $only_parents[$term_id] = $term;
                        }
                    }

                    $terms = $only_parents;
                    unset($only_parents);
                }
            }
            if (count($terms) <= flrt_more_less_count() || ($args['hide'] && !$args['use_apply_button'])) {
                $classes[] = 'wpc-filter-few-terms';
            }

            if ($count_terms_greater_than_zero <= flrt_more_less_count()) {
                $classes[] = 'wpc-filter-few-terms';
            }



        } else {
            $classes[] = 'wpc-filter-full-height';
        }
    }

    if (isset($filter['collapse']) && $filter['collapse'] === 'yes') {
        if (in_array($filter['ID'], flrt_folding_opened())) {
            $classes[] = 'wpc-filter-collapsible-reverse';
        }

        $classes[] = 'wpc-filter-collapsible';

        $classes[] = flrt_get_status_css_class($filter['ID'], FLRT_FOLDING_COOKIE_NAME);
    }

    if (in_array($filter['ID'], flrt_hierarchy_opened())) {
        if (isset($filter['hierarchy']) && $filter['hierarchy'] === 'yes') {
            $classes[] = 'wpc-filter-hierarchy-reverse';
        }
    }

    if (in_array($filter['entity'], ['post_date', 'post_meta_date'])) {
        $classes[] = 'wpc-datetype-' . $filter['date_type'];
    }

    if (!empty($default_classes)) {
        $classes = array_merge($classes, $default_classes);
    }

    $classes[] = 'wpc-filter-terms-count-' . count($terms);

    $classes = apply_filters('wpc_filter_classes', $classes, $filter, $default_classes, $terms, $args);

    return implode(" ", $classes);
}

function flrt_filter_content_class($filter, $default_classes = [])
{
    $classes = array(
            'wpc-filter-content'
    );

    if (isset($filter['e_name'])) {
        $classes[] = 'wpc-filter-' . $filter['e_name'];
    }

    if (isset($filter['hierarchy']) && $filter['hierarchy'] === 'yes') {
        $classes[] = 'wpc-filter-has-hierarchy';
    }

    if (!empty($default_classes)) {
        $classes = array_merge($classes, $default_classes);
    }

    $classes = apply_filters('wpc_filter_content_classes', $classes, $default_classes);

    return implode(" ", $classes);

}

if (!function_exists('flrt_filter_no_terms_message')) {
    /**
     * Outputs "No terms" message
     * @param string $tag HTML tag name for the message wrapper
     * @since 1.7.6
     */
    function flrt_filter_no_terms_message($tag = 'li')
    {
        if (!$tag || $tag === '') {
            $tag = 'li';
        }

        $srch = isset($_GET['srch']) ? filter_input(INPUT_GET, 'srch', FILTER_SANITIZE_SPECIAL_CHARS) : '';

        echo '<' . $tag . ' class="wpc-no-filter-terms">';
        if (!flrt_is_filter_request() && !$srch) {
            esc_html_e('There are no filter terms yet', 'filter-everything');
            if (flrt_is_debug_mode()) {
                echo '&nbsp;' . flrt_help_tip(
                                esc_html__('Possible reasons: 1) Filter\'s criterion doesn\'t contain any terms yet, and you have to add them 2) Terms may be created, but no one post that should be filtered attached to these terms 3) You excluded all possible terms in Filter\'s options.', 'filter-everything')
                        );
            }
        } else {
            esc_html_e('N/A', 'filter-everything');
        }
        echo '</' . $tag . '>';
    }
}

if (!function_exists('flrt_filter_more_less')) {
    /**
     * Outputs More/Less toggle link
     * @param array $filter Filter array
     * @since 1.7.6
     */
    function flrt_filter_more_less($filter)
    {
        if (isset($filter['more_less']) && $filter['more_less'] === 'yes'): ?>
            <a class="wpc-see-more-control wpc-toggle-a" href="javascript:void(0);"
               data-fid="<?php echo esc_attr($filter['ID']); ?>"><?php esc_html_e('See more', 'filter-everything'); ?></a>
            <a class="wpc-see-less-control wpc-toggle-a" href="javascript:void(0);"
               data-fid="<?php echo esc_attr($filter['ID']); ?>"><?php esc_html_e('See less', 'filter-everything'); ?></a>
        <?php endif;
    }
}

if (!function_exists('flrt_filter_search_field')) {
    /**
     * Outputs filter search field
     * @since 1.7.6
     */
    function flrt_filter_search_field($filter, $view_args, $terms)
    {
        if (empty($terms)) {
            return false;
        }

        if ($filter['search'] === 'yes' && $view_args['ask_to_select_parent'] === false): ?>
            <div class="wpc-filter-search-wrapper wpc-filter-search-wrapper-<?php echo esc_attr($filter['ID']); ?>">
                <span class="wpc-search-icon"></span>
                <input class="wpc-filter-search-field" type="text" value=""
                       placeholder="<?php esc_html_e('Search', 'filter-everything') ?>"/>
                <button class="wpc-search-clear" type="button"
                        title="<?php esc_html_e('Clear search', 'filter-everything') ?>"><span
                            class="wpc-search-clear-icon">&#215;</span></button>
            </div>
        <?php endif;
    }
}

function flrt_get_contrast_ratio($hexColor)
{
    // hexColor RGB
    $R1 = hexdec(substr($hexColor, 1, 2));
    $G1 = hexdec(substr($hexColor, 3, 2));
    $B1 = hexdec(substr($hexColor, 5, 2));

    // Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 1, 2));
    $G2BlackColor = hexdec(substr($blackColor, 3, 2));
    $B2BlackColor = hexdec(substr($blackColor, 5, 2));

    // Calc contrast ratio
    $L1 = 0.2126 * pow($R1 / 255, 2.2) +
            0.7152 * pow($G1 / 255, 2.2) +
            0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
            0.7152 * pow($G2BlackColor / 255, 2.2) +
            0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
        $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
        $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }
    return round($contrastRatio);
}

function flrt_get_contrast_color($hexColor)
{

    $contrastRatio = flrt_get_contrast_ratio($hexColor);
    // If contrast is more than 5, return black color
    if ($contrastRatio > 10) {
        return '#333333';
    } else {
        // if not, return white color.
        return '#f5f5f5';
    }
}

function flrt_hex_to_rgb($hexColor, $opacity = 100)
{
    $hexColor = ltrim($hexColor, '#');

    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    $opacity = $opacity / 100;
    return "rgb($r $g $b / $opacity)";
}

function flrt_add_color_opacity($hexColor, $opacity = 50)
{
    $contrastRatio = flrt_get_contrast_ratio($hexColor);

    if ($contrastRatio <= 15) {
        return flrt_hex_to_rgb($hexColor, $opacity);
    } else {
        return $hexColor;
    }
}


function flrt_default_posts_container()
{
    return apply_filters('wpc_theme_posts_container', '#primary');
}

function flrt_default_theme_color()
{
    return apply_filters('wpc_theme_color', '#0570e2');
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

function flrt_get_icon_logo_svg($width = 24, $height = 24, $color = 'currentColor')
{
    return '<svg viewBox="0 0 53 53" fill="' .  $color . '" width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
	<g id="Group 1">
		<g id="Layer_1_00000162333103265806981530000017146624247591674556_">
			<g id="Group">
				<g id="Clip path group">
					<mask id="mask0_5_4" maskUnits="userSpaceOnUse" x="0" y="0" width="53" height="53">
						<g id="SVGID_00000112608340804245442460000013986219178199086244_">
							<path id="Vector" fill="white" d="M0 0H53V53H0V0ZM23.3 37.2C24.7 37.2 25.8 36.1 25.8 34.7C25.8 33.3 24.7 32.2 23.3 32.2C21.9 32.2 20.8 33.3 20.8 34.7C20.8 36.1 21.9 37.2 23.3 37.2ZM33.4 27.2C34.8 27.2 35.9 26.1 35.9 24.7C35.9 23.3 34.8 22.2 33.4 22.2C32 22.2 30.9 23.3 30.9 24.7C30.9 26.1 32 27.2 33.4 27.2ZM23 22.8C24.6 22.8 25.9 21.5 25.9 19.9C25.9 18.3 24.6 17 23 17C21.4 17 20.1 18.3 20.1 19.9C20.2 21.5 21.5 22.8 23 22.8Z" />
						</g>
					</mask>
					<g mask="url(#mask0_5_4)">
						<g id="BarsClipped">
							<path id="Vector_2" d="M39.9 31.5L18 37.3C17.4 37.5 16.8 37.1 16.6 36.5C16.4 35.9 16.8 35.3 17.4 35.1L39.3 29.2C39.9 29 40.5 29.4 40.7 30C40.8 30.7 40.5 31.3 39.9 31.5Z" fill="currentColor"/>
							<path id="Vector_3" d="M38.1 24.6L16.2 30.5C15.6 30.7 15 30.3 14.8 29.7C14.6 29.1 15 28.5 15.6 28.3L37.5 22.4C38.1 22.2 38.7 22.6 38.9 23.2C39 23.8 38.7 24.5 38.1 24.6Z" fill="currentColor"/>
							<path id="Vector_4" d="M36.2 17.9L14.3 23.8C13.7 24 13.1 23.6 12.9 23C12.7 22.4 13.1 21.8 13.7 21.6L35.6 15.7C36.2 15.5 36.8 15.9 37 16.5C37.2 17.1 36.8 17.7 36.2 17.9Z" fill="currentColor"/>
						</g>
					</g>
				</g>
			</g>
			<path id="Vector (Stroke)" d="M50 26.5C50 13.5284 39.4716 3 26.5 3C13.5284 3 3 13.5284 3 26.5C3 39.4716 13.5284 50 26.5 50C39.4716 50 50 39.4716 50 26.5ZM53 26.5C53 41.1284 41.1284 53 26.5 53C11.8716 53 0 41.1284 0 26.5C0 11.8716 11.8716 0 26.5 0C41.1284 0 53 11.8716 53 26.5Z" fill="currentColor"/>
		</g>
		<path id="Vector (Stroke)_2" d="M24.8 20.1C24.8 19.2716 24.1284 18.6 23.3 18.6C22.4716 18.6 21.8 19.2716 21.8 20.1C21.8 20.9284 22.4716 21.6 23.3 21.6C24.1284 21.6 24.8 20.9284 24.8 20.1ZM26.8 20.1C26.8 22.033 25.233 23.6 23.3 23.6C21.367 23.6 19.8 22.033 19.8 20.1C19.8 18.167 21.367 16.6 23.3 16.6C25.233 16.6 26.8 18.167 26.8 20.1Z" fill="currentColor"/>
		<path id="Vector (Stroke)_3" d="M34.7 24.8C34.7 23.9716 34.0284 23.3 33.2 23.3C32.3716 23.3 31.7 23.9716 31.7 24.8C31.7 25.6284 32.3716 26.3 33.2 26.3C34.0284 26.3 34.7 25.6284 34.7 24.8ZM36.7 24.8C36.7 26.733 35.133 28.3 33.2 28.3C31.267 28.3 29.7 26.733 29.7 24.8C29.7 22.867 31.267 21.3 33.2 21.3C35.133 21.3 36.7 22.867 36.7 24.8Z" fill="currentColor"/>
		<path id="Vector (Stroke)_4" d="M24.5 34.8C24.5 33.9716 23.8284 33.3 23 33.3C22.1716 33.3 21.5 33.9716 21.5 34.8C21.5 35.6284 22.1716 36.3 23 36.3C23.8284 36.3 24.5 35.6284 24.5 34.8ZM26.5 34.8C26.5 36.733 24.933 38.3 23 38.3C21.067 38.3 19.5 36.733 19.5 34.8C19.5 32.867 21.067 31.3 23 31.3C24.933 31.3 26.5 32.867 26.5 34.8Z" fill="currentColor"/>
	</g>
</svg>';
}
function flrt_get_icon_logo_svg_css($width = 24, $height = 24)
{
    return 'data:image/svg+xml;base64,' . base64_encode(flrt_get_icon_logo_svg($width, $height));
}
function flrt_get_icon_svg($color = '#ffffff')
{
    $svg = flrt_get_icon_logo_svg(null, null, $color);
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function flrt_get_icon_html()
{
    ?>
    <span class="wpc-icon-html-wrapper">
    <span class="wpc-icon-line-1"></span>
    <span class="wpc-icon-line-2"></span>
    <span class="wpc-icon-line-3"></span>
</span>
    <?php
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

/**
 * Combines all filter sets for the same WP_Query
 *
 * @param array $all_sets - list of all page related sets
 * @param $current_set
 * @return array $queryRelatedSets IDs of all query related sets
 */
function flrt_get_sets_with_the_same_query($all_sets, $current_set)
{
    $queryRelatedSets = [];
    // First detect desired query index;
    $query = '';
    $post_type = '';
    $location = '';
    $set_id = $current_set['ID'];

    foreach ($all_sets as $set) {
        if ($set['ID'] === $set_id) {
            // Current Set values
            $query = $set['query'];
            $post_type = $set['filtered_post_type'];
            $location = $set['query_location'];
            break;
        }
    }

    // Then find all sets with such query
    foreach ($all_sets as $set) {
        if ($set['query'] === $query && $post_type === $set['filtered_post_type'] && $location === $set['query_location']) {
            $queryRelatedSets[] = $set['ID'];
        }
    }

    if (empty($queryRelatedSets)) {
        $queryRelatedSets[] = $set_id;
    }

    return $queryRelatedSets;
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

function flrt_get_terms_transient_key( $salt, $include_lang = true ){
    $key = 'wpc_terms_' . $salt . FLRT_CACHE_FORMAT_SUFFIX;
    if ( flrt_wpml_active() && defined( 'ICL_LANGUAGE_CODE' ) && $include_lang ) {
        $key .= '_'.ICL_LANGUAGE_CODE;
    }

    if (function_exists('pll_current_language') && $include_lang) {
        $pll_lang = pll_current_language();
        if ($pll_lang) {
            $key .= '_' . $pll_lang;
        }
    }

    return $key;
}

function flrt_get_post_ids_transient_key( $salt ){
    $key = 'wpc_posts_' . $salt . FLRT_CACHE_FORMAT_SUFFIX;
    if (flrt_wpml_active() && defined('ICL_LANGUAGE_CODE')) {
        $key .= '_' . ICL_LANGUAGE_CODE;
    }

    if (function_exists('pll_current_language')) {
        $pll_lang = pll_current_language();
        if ($pll_lang) {
            $key .= '_' . $pll_lang;
        }
    }

    return $key;
}

function flrt_get_variations_transient_key( $salt ){
    $key = 'wpc_variations_' . $salt . FLRT_CACHE_FORMAT_SUFFIX;
    if (flrt_wpml_active() && defined('ICL_LANGUAGE_CODE')) {
        $key .= '_' . ICL_LANGUAGE_CODE;
    }

    if (function_exists('pll_current_language')) {
        $pll_lang = pll_current_language();
        if ($pll_lang) {
            $key .= '_' . $pll_lang;
        }
    }

    return $key;
}

function flrt_is_query_on_page($setPosts, $searchKey)
{
    $filterSet = Container::instance()->getFilterSetService();
    $sets = [];
    if (!is_array($setPosts)) {
        return $sets;
    }

    foreach ($setPosts as $set) {

        $parameters = maybe_unserialize($set->post_content);
        $query = isset($parameters['wp_filter_query']) ? $parameters['wp_filter_query'] : '-1';
        if ($filterSet->under_limit_filter_set($set->ID)) {
            continue;
        }

        if (isset($parameters['use_apply_button']) && $parameters['use_apply_button'] === 'yes') {

            $query_on_the_page = false;
            $show_on_the_page = false;

            if (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) {
                if (isset($parameters['apply_button_post_name'])) {

                    if ($parameters['apply_button_post_name'] === $set->post_name ||
                            $parameters['apply_button_post_name'] === 'no_page___no_page') {
                        $query_on_the_page = true;
                    }

                    if (in_array($parameters['apply_button_post_name'], $searchKey) || ($parameters['apply_button_post_name'] === 'no_page___no_page')) {
                        $show_on_the_page = true;
                    }
                }

            } else {
                $query_on_the_page = true;
                $show_on_the_page = true;
            }

            $sets[] = array(
                    'ID'                 => (string)$set->ID,
                    'filtered_post_type' => $set->post_excerpt,
                    'query'              => $query, // query hash
                    'query_location'     => $set->post_name,
                    'query_on_the_page'  => $query_on_the_page,
                    'page_search_keys'   => $searchKey,
                    'show_on_the_page'   => $show_on_the_page
            );

        } else {
            if (in_array($set->post_name, $searchKey)) {
                $sets[] = array(
                        'ID'                 => (string)$set->ID,
                        'filtered_post_type' => $set->post_excerpt,
                        'query'              => $query, // query hash
                        'query_location'     => $set->post_name,
                        'query_on_the_page'  => true,
                        'page_search_keys'   => $searchKey,
                        'show_on_the_page'   => true
                );
            } else {
                // This set is for another page and was selected by Apply button location but the button disabled
                continue;
            }
        }

    }

    return $sets;
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

function flrt_get_term_brand_image($term_id, $filter)
{
    $src = false;

    if ($filter['e_name'] === 'pwb-brand') {
        $attachment_id = get_term_meta($term_id, 'pwb_brand_image', true);
        $attachment_props = wp_get_attachment_image_src($attachment_id, 'small');
        $src = isset($attachment_props[0]) ? $attachment_props[0] : false;
    } elseif (in_array($filter['e_name'], ['yith_product_brand', 'product_brand'])) {
        $attachment_id = get_term_meta($term_id, 'thumbnail_id', true);
        $attachment_props = wp_get_attachment_image_src($attachment_id, 'small');
        $src = isset($attachment_props[0]) ? $attachment_props[0] : false;
    } else {
        // pa_brand and other custom brand taxonomies. The plugin's own term
        // Image field saves 'product_attribute_image' for pa_* taxonomies
        // (see Swatches::save_image_field) — read the same key it writes.
        $image_meta_key = ( strpos( $filter['e_name'], 'pa_' ) === 0 ) ? 'product_attribute_image' : 'image';
        $src = get_term_meta($term_id, $image_meta_key, true);

        if ( ! $src && $image_meta_key !== 'image' ) {
            $src = get_term_meta($term_id, 'image', true);
        }

        if (intval($src) > 0) {
            $src = wp_get_attachment_image_url($src, 'full');
        }

        if (isset($src['id']) && $src['id']) {
            $src = wp_get_attachment_image_url($src['id'], 'full');
        }
    }

    return $src;
}

function flrt_get_term_swatch_image($term_id, $filter)
{
    $src = false;
    $image_key = 'image';

    if (strpos($filter['e_name'], 'pa_') === 0) {
        $image_key = 'product_attribute_' . $image_key;
    }

    if ($filter['e_name'] === 'product_cat') {
        $image_key = 'thumbnail_id';
    }

    $image_key = apply_filters('wpc_image_term_meta_key', $image_key, $filter);

    $image_id = get_term_meta($term_id, $image_key, true);
    $swatch_image_size = apply_filters('wpc_swatch_image_size', 'thumbnail');

    if ($image_id) {
        $src = wp_get_attachment_image_url($image_id, $swatch_image_size);
    }

    return $src;
}

function flrt_get_term_swatch_color($term_id, $filter)
{
    $color = false;
    $color_key = 'color';

    if (strpos($filter['e_name'], 'pa_') === 0) {
        $color_key = 'product_attribute_' . $color_key;
    }

    $color_key = apply_filters('wpc_color_term_meta_key', $color_key, $filter);
    $color = get_term_meta($term_id, $color_key, true);

    return $color;
}

/**
 * Checks and returns date format.
 * Does not check if date is valid
 * @param $date
 * @return string|false date, time format or false
 */
function flrt_detect_date_type($date_or_time)
{
    if (!$date_or_time) {
        return false;
    }
    $format = false;
    $date = false;
    $time = false;

    $date_or_time = str_replace(FLRT_DATE_TIME_SEPARATOR, ' ', $date_or_time);

    $pcs = date_parse($date_or_time);
    if ($pcs['year'] !== false && $pcs['month'] !== false && $pcs['day'] !== false) {
        $date = true;
    }

    if ($pcs['hour'] !== false && $pcs['minute'] !== false && $pcs['second'] !== false) {
        $time = true;
    }

    if ($date && $time) {
        $format = 'datetime';
    } else {
        if ($date) {
            $format = 'date';
        }
        if ($time) {
            $format = 'time';
        }
    }

    return $format;
}

/**
 * Modifies datetime to the human format
 * @param $datetime
 * @param $date_type
 * @param string $sep
 * @return mixed|string
 */
function flrt_clean_date_time($datetime, $date_type, $sep = " ")
{
    if ($date_type === 'date' || $date_type === 'DATE') {
        $pieces = explode($sep, $datetime);
        return $pieces[0]; //date e.g. 2021-05-14
    } else if ($date_type === 'time' || $date_type === 'TIME') {
        $pieces = explode($sep, $datetime);
        if (isset($pieces[1])) {
            return $pieces[1]; //time e.g. 14:15:47
        }
    } else {
        return $datetime; // str_replace( $sep, ' ', $datetime ); //datetime e.g. 2021-05-14 14:15:47
    }
}

function flrt_apply_date_format($income_date, $format = "Y-m-d H:i:s")
{
    $timestamp = strtotime($income_date);
    return flrt_date($format, $timestamp);
}

function flrt_date($format, $timestamp = null)
{
    global $wp_locale;

    if (null === $timestamp) {
        $timestamp = time();
    } elseif (!is_numeric($timestamp)) {
        return false;
    }

    $datetime = date_create('@' . $timestamp);

    if (empty($wp_locale->month) || empty($wp_locale->weekday)) {
        $date = $datetime->format($format);
    } else {
        // We need to unpack shorthand `r` format because it has parts that might be localized.
        $format = preg_replace('/(?<!\\\\)r/', DATE_RFC2822, $format);

        $new_format = '';
        $format_length = strlen($format);
        $month = $wp_locale->get_month($datetime->format('m'));
        $weekday = $wp_locale->get_weekday($datetime->format('w'));

        for ($i = 0; $i < $format_length; $i++) {
            switch ($format[$i]) {
                case 'D':
                    $new_format .= addcslashes($wp_locale->get_weekday_abbrev($weekday), '\\A..Za..z');
                    break;
                case 'F':
                    $new_format .= addcslashes($month, '\\A..Za..z');
                    break;
                case 'l':
                    $new_format .= addcslashes($weekday, '\\A..Za..z');
                    break;
                case 'M':
                    $new_format .= addcslashes($wp_locale->get_month_abbrev($month), '\\A..Za..z');
                    break;
                case 'a':
                    $new_format .= addcslashes($wp_locale->get_meridiem($datetime->format('a')), '\\A..Za..z');
                    break;
                case 'A':
                    $new_format .= addcslashes($wp_locale->get_meridiem($datetime->format('A')), '\\A..Za..z');
                    break;
                case '\\':
                    $new_format .= $format[$i];

                    // If character follows a slash, we add it without translating.
                    if ($i < $format_length) {
                        $new_format .= $format[++$i];
                    }
                    break;
                default:
                    $new_format .= $format[$i];
                    break;
            }
        }

        $date = date_format($datetime, $new_format);
    }

    return $date;
}

function flrt_default_date_format($date_type = 'date')
{
    /**
     * @todo date format depend from localization and geo settings
     * we have to relate them here
     */
    $date_format = __('F j, Y');

    switch ($date_type) {
        case 'date':
            $date_format = __('F j, Y'); //'d-m-Y';
            break;
        case 'datetime':
            $date_format = __('F j, Y g:i a'); //'d-m-Y H:i:s';
            break;
        case 'time':
            $date_format = __('g:i a'); // 'H:i:s';
            break;
    }

    return $date_format;
}

function flrt_convert_date_to_js($date_or_time)
{
    $date_php_to_js = Container::instance()->getParam('php_to_js_date_formats');
    return flrt_str_replace($date_or_time, $date_php_to_js);
}

function flrt_convert_time_to_js($date_or_time)
{
    $time_php_to_js = Container::instance()->getParam('php_to_js_time_formats');
    return flrt_str_replace($date_or_time, $time_php_to_js);
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

function flrt_split_date_time($date_time = '')
{
    $php_date = Container::instance()->getParam('php_to_js_date_formats');
    $php_time = Container::instance()->getParam('php_to_js_time_formats');
    $chars = str_split($date_time);
    $type = 'date';

    $data = array(
            'date' => '',
            'time' => '',
    );

    foreach ($chars as $i => $c) {
        if (isset($php_date[$c])) {
            $type = 'date';
        } elseif (isset($php_time[$c])) {
            $type = 'time';
        }
        $data[$type] .= $c;
    }

    $data['date'] = trim($data['date']);
    $data['time'] = trim($data['time']);

    return $data;
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

function flrt_rating_star()
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25">
             <polygon class="cls-1" points="19.89 24.5 12.48 19.8 5.06 24.48 7.03 15.62 0.5 9.64 9.12 8.87 12.51 0.5 15.88 8.88 24.5 9.68 17.96 15.63 19.89 24.5"/>
            </svg>';
}

function flrt_check_update_mobile_settings()
{

    $settings = get_option('wpc_filter_settings', false);
    if ($settings !== false) {
        if (!flrt_get_option('mobile_filter_settings')) {

            $mobile_filter_settings = 'nothing';

            if ((flrt_get_option('show_bottom_widget') === 'on' && flrt_get_option('show_open_close_button') === 'on')
                    || (flrt_get_option('show_bottom_widget') === 'on' && !flrt_get_option('show_open_close_button'))) {
                $mobile_filter_settings = 'show_bottom_widget';
            } elseif (flrt_get_option('show_open_close_button') == 'on' && !flrt_get_option('show_bottom_widget')) {
                $mobile_filter_settings = 'show_open_close_button';
            }

            $settings = get_option('wpc_filter_settings');

            if (isset($settings['show_bottom_widget']) && $settings['show_bottom_widget']) {
                unset($settings['show_bottom_widget']);
            }

            if (isset($settings['show_open_close_button']) && $settings['show_open_close_button']) {
                unset($settings['show_open_close_button']);
            }

            $settings['mobile_filter_settings'] = $mobile_filter_settings;
            update_option('wpc_filter_settings', $settings);
        }
    }
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
function flrt_add_pro_promo_fields($defaultFields, $filterFields)
{

    if (!defined('FLRT_FILTERS_PRO')) {
        if (flrt_is_woocommerce()) {
            $updatedFields = [];
            foreach ($defaultFields as $key => $field) {
                $updatedFields[$key] = $field;

                if ($key === 'hierarchy') {
                    $updatedFields['used_for_variations'] = array(
                            'type'         => 'inProButton',
                            'pro_label'    => flrt_pro_promo_label(),
                            'label'        => esc_html__('Use for Variations', 'filter-everything'),
                            'class'        => 'wpc-field-for-variations',
                            'default'      => 'no',
                            'instructions' => esc_html__('If checked, filtering will take into account variations with this attribute or meta key', 'filter-everything'),
                    );
                }
            }
            return $updatedFields;
        }
    }

    return $defaultFields;

}

function flrt_pro_features_link()
{
    return 'https://filtereverything.pro/?utm_source=free_plugin&utm_medium=internal&utm_campaign=free_plugin_upgrade&utm_content=popup_features_lnk#why-choose-pro';
}

function flrt_unlock_pro_link( $utm_content = '' )
{
    return 'https://filtereverything.pro/pricing/?utm_source=free_plugin&utm_medium=internal&utm_campaign=free_plugin_upgrade&utm_content=' . $utm_content;
}

/**
 * Link to the refund policy. Currently a FAQ anchor on the main landing page;
 * will point to a dedicated refund-policy page once that exists.
 */
function flrt_refund_policy_link()
{
    return 'https://filtereverything.pro/#refund-policy';
}


function wpc_clear_folder($directory)
{

    if (!is_dir($directory)) {
        return false;
    }

    $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*');

    if(!empty($files)){
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

function flrt_diamond_icon($svg_fill = "var(--wpc-pro-color, #3858E9)")
{
    return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="' . $svg_fill. '" version="1.1" id="Layer_1" width="16px" height="16px" viewBox="0 0 70 70" enable-background="new 0 0 70 70" xml:space="preserve">
                <g>
                    <path d="M67.142,23.641L55.405,10.456c-0.379-0.423-0.92-0.873-1.488-0.873h-37.98c-0.568,0-1.109,0.45-1.489,0.874L2.711,23.752   c-0.691,0.771-0.68,1.94,0.025,2.697L33.462,59.46c0.378,0.407,0.909,0.638,1.464,0.638s1.086-0.257,1.464-0.664l30.728-33.042   C67.822,25.634,67.833,24.411,67.142,23.641z M46.555,25.583L34.902,53.414L22.608,25.583H46.555z M21.725,23.583l-4.417-10h34.272   l-4.188,10H21.725z M32.231,52.152L7.586,25.583h12.879L32.231,52.152z M48.702,25.583H62c0.094,0,0.179-0.029,0.265-0.054   L37.462,52.318L48.702,25.583z M61.871,23.583H49.543l3.971-9.447L61.871,23.583z M15.714,14.851l3.867,8.732H8.027L15.714,14.851z   "/>
                <path d="M35,14.583H23c-0.552,0-1,0.447-1,1s0.448,1,1,1h12c0.552,0,1-0.447,1-1S35.552,14.583,35,14.583z"/>
                <path d="M45,14.583h-5c-0.552,0-1,0.447-1,1s0.448,1,1,1h5c0.552,0,1-0.447,1-1S45.552,14.583,45,14.583z"/>
            </g>
            </svg>';
}


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

function flrt_rating_slugs()
{
    return array(
            'rated-1' => 1,
            'rated-2' => 2,
            'rated-3' => 3,
            'rated-4' => 4,
            'rated-5' => 5
    );
}

/**
 * Whether Apply button Sets recalculate counters instantly in the browser
 * (the 1.9.3 client-side recount) instead of the legacy per-click AJAX
 * request. Disabled by default, so existing Apply button users keep the
 * pre-1.9.3 behaviour after the update until they opt in.
 *
 * The option is PRO-only: in the free version the preserved DB value stays
 * inert and Apply button Sets always use the legacy AJAX recount. The whole
 * client-side engine works in free too — this gate is the only thing to
 * relax if instant recount ever ships in the free version.
 */
function flrt_instant_recount()
{
    return defined('FLRT_FILTERS_PRO') && flrt_get_option('apply_button_instant_recount') === 'on';
}

function flrt_check_apply_buttom_mode($set)
{
    return ((isset($set['use_apply_button']['value']) && $set['use_apply_button']['value'] === 'yes') && flrt_instant_recount());
}


function flrt_parent_filter_apply_button_data($filter, $args = [])
{
    $data = '';
    if($args['use_apply_button'] && $filter['parent_filter'] > 0){
        $hide_until_parent = 0;
        if($filter['hide_until_parent'] === 'yes'){
            $hide_until_parent = 1;
        }
        $data .= ' data-parent-filter-id="' . esc_attr($filter['parent_filter']) . '" data-hide-until-parent="' . esc_attr($hide_until_parent) .'"';
    }
    return $data;
}

function flrt_parent_filter_apply_class($filter, $args = [], $terms = [])
{
    $css_class = '';
    if($filter['parent_filter'] < 0){
        return $css_class;
    }

    $checked        = false;

    $css_class = ' ' . esc_attr('wpc-has-parent-filter');

    $is_parent_has_terms = false;
    if(!empty($terms)){
        foreach ($terms as $term){
            if(isset($term->show_with_parent)){
                $is_parent_has_terms = true;
            }
             if( in_array( $term->slug, $filter['values'] ) ){
                 $checked = true;
             }
             if($is_parent_has_terms && $checked){
                 break;
             }
        }
    }


    if($args['use_apply_button'] && $filter['parent_filter'] > 0 && $is_parent_has_terms === false){
        $css_class .= ' ' . esc_attr('wpc-parent-filter-terms-unselected');
    }

    if($args['use_apply_button'] && $filter['parent_filter'] > 0  && $is_parent_has_terms === false && $checked){
        $css_class .= ' ' . esc_attr('wpc-child-selected-no-parent');
    }

    if($args['use_apply_button'] && $filter['parent_filter'] > 0 && $is_parent_has_terms === true){
        $css_class .= ' ' . esc_attr('wpc-parent-filter-terms-selected');
    }

    if(empty($filter['hide_until_parent']) || $filter['hide_until_parent'] !== 'yes'){
        return $css_class;
    }

    if($args['use_apply_button'] && !empty($filter['hide_until_parent']) && $filter['hide_until_parent'] === 'yes' && $filter['parent_filter'] > 0 && $is_parent_has_terms === false){
        $css_class .= ' ' . esc_attr('wpc-hide-terms-until-parent-unselected');
    }

    return $css_class;
}

function flrtIsMoreLess($filter)
{
    return (!empty($filter['more_less']) && $filter['more_less'] === 'yes') ? true : false;
}

function flrtParentFilter($filter)
{
    return (!empty($filter['parent_filter']) && $filter['parent_filter'] !== '-1');
}

function flrt_get_filtered_term_url( $term, $filter, $url_manager ) {
    $exclude_from_url = [];

    if ( ! empty( $filter['has_child_filter'] ) ) {
        if ( $filter['has_child_filter'] === true && count( $filter['values'] ) === 1 ) {
            if ( $term->slug === $filter['values'][0] ) {
                foreach ( $filter['child_values'] as $child_value ) {
                    $exclude_from_url[ $child_value ] = true;
                }
            }
        }
    }

    return $url_manager->getTermUrl(
            $term->slug,
            $filter['e_name'],
            $filter['entity'],
            '',
            $exclude_from_url
    );
}
function flrtDetectTimeComponents(string $format): array
{
    $hasHours   = false;
    $hasMinutes = false;
    $hasSeconds = false;

    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $char = $format[$i];

        if ($char === '\\') {
            $i++;
            continue;
        }

        if (in_array($char, ['g', 'G', 'h', 'H'], true)) {
            $hasHours = true;
        } elseif ($char === 'i') {
            $hasMinutes = true;
        } elseif ($char === 's') {
            $hasSeconds = true;
        }
    }

    return [
            'hours'   => $hasHours,
            'minutes' => $hasMinutes,
            'seconds' => $hasSeconds,
    ];
}

function flrtBuildDateTimeString(string $value, string $format): ?string
{
    $date = DateTime::createFromFormat($format, $value);
    if ($date === false) {
        return null;
    }

    $components = flrtDetectTimeComponents($format);

    $hours   = $components['hours']   ? $date->format('H') : '00';
    $minutes = $components['minutes'] ? $date->format('i') : '00';
    $seconds = $components['seconds'] ? $date->format('s') : '00';

    return $date->format('Y-m-d') . " {$hours}:{$minutes}:{$seconds}";
}
