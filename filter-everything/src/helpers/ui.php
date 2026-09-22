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
 * Markup helpers used by admin views and frontend templates: tooltips, field
 * rendering, filter/widget CSS classes, buttons, counters, icons. The pluggable
 * functions (wrapped in function_exists) may be overridden by a theme.
 *
 * Split out of src/wpc-helpers.php on 2026-09-14; function names and bodies are
 * unchanged. Loaded from filter-everything.php right after wpc-helpers.php.
 */

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

function flrt_render_input($atts)
{
    $className = isset($atts['type']) ? '\FilterEverything\Filter\\' . $atts['type'] : '\FilterEverything\Filter\Text';

    if (class_exists($className)) {
        $input = new $className($atts);
        return $input->render();
    }

    return false;
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
            $isParentFilter
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


function flrt_default_posts_container()
{
    return apply_filters('wpc_theme_posts_container', '#primary');
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

function flrt_rating_star()
{
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25 25">
             <polygon class="cls-1" points="19.89 24.5 12.48 19.8 5.06 24.48 7.03 15.62 0.5 9.64 9.12 8.87 12.51 0.5 15.88 8.88 24.5 9.68 17.96 15.63 19.89 24.5"/>
            </svg>';
}
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
