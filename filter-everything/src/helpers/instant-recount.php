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
 * Apply-button / instant-recount helpers: "query on the page" set detection and
 * the data passed to the frontend recount engine.
 *
 * Split out of src/wpc-helpers.php on 2026-09-14; function names and bodies are
 * unchanged. Loaded from filter-everything.php right after wpc-helpers.php.
 */

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
    if($args['use_apply_button'] && (int) $filter['parent_filter'] > 0){
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
    // '-1', the legacy 'no' placeholder and anything non-numeric all mean "no parent"
    if( (int) $filter['parent_filter'] <= 0 ){
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


    if($args['use_apply_button'] && $is_parent_has_terms === false){
        $css_class .= ' ' . esc_attr('wpc-parent-filter-terms-unselected');
    }

    if($args['use_apply_button'] && $is_parent_has_terms === false && $checked){
        $css_class .= ' ' . esc_attr('wpc-child-selected-no-parent');
    }

    if($args['use_apply_button'] && $is_parent_has_terms === true){
        $css_class .= ' ' . esc_attr('wpc-parent-filter-terms-selected');
    }

    if(empty($filter['hide_until_parent']) || $filter['hide_until_parent'] !== 'yes'){
        return $css_class;
    }

    if($args['use_apply_button'] && !empty($filter['hide_until_parent']) && $filter['hide_until_parent'] === 'yes' && $is_parent_has_terms === false){
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
    // Positive filter ID only. '-1' means no parent, and single-filter sets used
    // to save the 'no' select placeholder — on PHP 8 the string comparison
    // 'no' > 0 is true, so a plain numeric check would treat it as a parent
    return !empty($filter['parent_filter']) && (int) $filter['parent_filter'] > 0;
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
