<?php
/**
 * The Template for displaying filter radio buttons.
 *
 * This template can be overridden by copying it to yourtheme/filters/radio.php
 *
 * $set - array, with the Filter Set parameters
 * $filter - array, with the Filter parameters
 * $url_manager - object, of the UrlManager PHP class
 * $terms - array, with objects of all filter terms except excluded
 *
 * @see https://filtereverything.pro/resources/templates-overriding/
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

$args = [
    'hide' => $view_args['ask_to_select_parent'],
    'use_apply_button' => $view_args['use_apply_button'],
    'hide_empty' => $set['hide_empty']['value'],
    'hide_empty_filter' => isset($set['hide_empty_filter']['value']) ? $set['hide_empty_filter']['value'] : '',
];
$rating_slugs = flrt_rating_slugs();
$hide_for_apply_button_mode = flrt_check_apply_buttom_mode($set);
$is_hide_empty_terms = (isset($set['hide_empty']['value']) && $set['hide_empty']['value'] === 'yes');
$parent_filter_apply_button_data = flrt_parent_filter_apply_button_data($filter, $view_args);
$parent_filter_apply_class = flrt_parent_filter_apply_class($filter, $view_args, $terms);
$isMoreLess = flrtIsMoreLess($filter);
$flrt_more_less_count = flrt_more_less_count();
$isParentFilter = flrtParentFilter($filter);
?>
<div class="<?php echo flrt_filter_class( $filter, [], $terms, $args ); // Already escaped ?><?php echo $parent_filter_apply_class; ?>" data-fid="<?php echo esc_attr( $filter['ID'] ); ?>" data-filter-e-name="<?php echo esc_attr($filter['e_name']); ?>"<?php echo $parent_filter_apply_button_data; ?>>
    <?php flrt_filter_header( $filter, $terms ); // Safe, escaped ?>
    <div class="<?php echo esc_attr( flrt_filter_content_class( $filter ) ); ?>">
        <?php flrt_filter_search_field( $filter, $view_args, $terms ); ?>
        <ul class="wpc-filters-ul-list wpc-filters-radio wpc-filters-list-<?php echo esc_attr( $filter['ID'] ); ?>">
            <?php if( ! empty( $terms ) || $view_args['ask_to_select_parent'] ):
                if( $view_args['ask_to_select_parent'] !== false && !$view_args['use_apply_button']) { ?>
                    <li class="wpc-ask-to-parent-display"><?php echo esc_html( $view_args['ask_to_select_parent'] ); ?></li>
                <?php } else {
                    if( $view_args['ask_to_select_parent'] !== false && $view_args['use_apply_button'] ) { ?>
                        <li class="wpc-ask-to-parent-display"><?php echo esc_html( $view_args['ask_to_select_parent'] ); ?></li>
                    <?php }
                    $hidden_terms_count = 1;
                    foreach ( $terms as $id => $term_object ) {
                        $disabled       = 0;
                        $checked        = ( in_array( $term_object->slug, $filter['values'] ) ) ? 1 : 0;
                        if ( isset( $term_object->wp_queried ) && $term_object->wp_queried ) {
                            $checked    = 1;
                            $disabled   = 1;
                        }

                        $active_class    = $checked ? ' wpc-term-selected' : '';
                        $disabled_class  = $disabled ? ' wpc-term-disabled' : '';
                        $link = flrt_get_filtered_term_url($term_object, $filter, $url_manager);
                        $link_attributes = 'href="'.esc_url($link).'"';
                        $link_attributes .= ' class="wpc-filter-link"';
                        $name            = ( $disabled > 0 ) ? $filter['e_name'] . '-disabled' : $filter['e_name'];
                        $rating_data = (isset($rating_slugs[$term_object->slug])) ? ' data-rating-num="' . esc_attr($rating_slugs[$term_object->slug]) . '" ' : '';
                        $hidden_class_for_apply_button_mode = '';
                        if($hide_for_apply_button_mode){
                            $hidden_class_for_apply_button_mode = ($term_object->cross_count <= 0 && $is_hide_empty_terms) ? esc_attr(' wpc-term-count-hidden-0') : '';
                            if($term_object->cross_count > 0){
                                $hidden_class_for_apply_button_mode .= esc_attr(' wpc-has-terms');
                            }
                            if($checked){
                                $hidden_class_for_apply_button_mode .= esc_attr(' wpc-term-count-hidden-checked-0');
                            }
                        }
                        $checked_data = ($checked) ? ' data-wpc-was-checked=true' : ' data-wpc-was-checked=false';
                        $show_with_parent_class = '';
                        if (isset($term_object->show_with_parent)) {
                            if ($term_object->show_with_parent === true) {
                                $show_with_parent_class = ' wpc-show-with-parent-true';
                            } else {
                                $show_with_parent_class = ' wpc-show-with-parent-false';
                            }
                        }

                        if (!isset($term_object->show_with_parent)) {
                            if ($view_args['ask_to_select_parent'] !== false && $view_args['use_apply_button']) {
                                if ((!$checked)) {
                                    $show_with_parent_class = ' wpc-show-with-parent-false';
                                }
                            }
                        }
                        $more_less_hidden_class = '';
                        if ( $isMoreLess ) {
                            $is_visible   = ! $isParentFilter || ( isset( $term_object->show_with_parent ) && $term_object->show_with_parent === true ) || !isset( $term_object->show_with_parent );
                            $has_results  = ! $is_hide_empty_terms || $term_object->cross_count > 0;

                            if ( $is_visible && $has_results ) {
                                if ( $hidden_terms_count <= $flrt_more_less_count ) {
                                    $more_less_hidden_class = ' wpc-not-hidden-term';
                                }
                                $hidden_terms_count++;
                            }
                        }
                    ?>
                    <li class="wpc-radio-item wpc-term-item<?php echo esc_attr( $active_class ); ?><?php echo esc_attr( $disabled_class ); ?> wpc-term-count-<?php echo esc_attr( $term_object->cross_count ); ?> wpc-term-id-<?php echo esc_attr($id); ?><?php echo $hidden_class_for_apply_button_mode; ?><?php echo esc_attr($show_with_parent_class); ?><?php echo esc_attr( $more_less_hidden_class ); ?>" id="<?php flrt_term_id('term', $filter, $id); ?>">
                        <div class="wpc-term-item-content-wrapper">
                            <input <?php checked( 1, $checked ); disabled( 1, $disabled ) ?> type="radio" data-wpc-link="<?php echo esc_url( $link ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php flrt_term_id('radio', $filter, $id); ?>" data-wpc-e-name="<?php echo esc_attr($filter['e_name']); ?>" data-wpc-slug="<?php echo esc_attr($term_object->slug); ?>" data-term-id="<?php echo esc_attr($id); ?>"<?php echo $rating_data;?><?php echo esc_attr($checked_data);?> />
                            <label for="<?php flrt_term_id('radio', $filter, $id); ?>">
                                <?php

                                /**
                                 * Allow developers to change filter terms html
                                 */
                                echo apply_filters( 'wpc_filters_radio_term_html', '<a '.$link_attributes.'>'.$term_object->name.'</a>', $link_attributes, $term_object, $filter );

                                ?><?php flrt_filter_count( $term_object, $set['show_count']['value'] ); ?></label></div></li>
                    <?php } /* end foreach */ ?>
                <?php } /* end if ask to select parent */ ?>
            <?php  else:
                flrt_filter_no_terms_message();
            endif;
?>      </ul>
        <?php flrt_filter_more_less( $filter ); ?>
    </div>
</div>