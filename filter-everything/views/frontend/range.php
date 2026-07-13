<?php
/**
 * The Template for displaying filter range.
 *
 * This template can be overridden by copying it to yourtheme/filters/range.php
 *
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

$hide_empty = ($set['hide_empty']['value'] === 'yes');
$parent_filter_apply_button_data = flrt_parent_filter_apply_button_data($filter, $view_args);
$parent_filter_apply_class = flrt_parent_filter_apply_class($filter, $view_args, $terms);
$set_id = isset( $set['ID'] ) ? esc_html( $set['ID'] ) : 0;

?>
<div class="<?php echo esc_attr('wpc-range-filter'); ?> <?php echo flrt_filter_class( $filter, [], $terms, $args  ); // Already escaped ?><?php echo $parent_filter_apply_class; ?>" data-fid="<?php echo esc_attr( $filter['ID'] ); ?>" data-filter-e-name="<?php echo esc_attr($filter['e_name']); ?>"<?php echo $parent_filter_apply_button_data; ?>>
    <?php flrt_filter_header( $filter, $terms ); // Safe, escaped ?>
    <div class="<?php echo esc_attr( flrt_filter_content_class( $filter ) ); ?>">
        <div class="wpc-filters-range-inputs">
            <?php if( ! empty( $terms ) || $view_args['ask_to_select_parent'] || ( empty( $terms ) && $view_args['use_apply_button']) ):

                if( $view_args['ask_to_select_parent'] !== false && !$view_args['use_apply_button']) { ?>
                    <div><?php echo esc_html( $view_args['ask_to_select_parent'] ); ?></div>
                <?php } else {
                    if( $view_args['ask_to_select_parent'] !== false && $view_args['use_apply_button'] ) { ?>
                        <div class="wpc-ask-to-parent-display"><?php echo esc_html( $view_args['ask_to_select_parent'] ); ?></div>
                    <?php }
                    $minName = flrt_range_input_name( $filter['slug'] );
                    $maxName = flrt_range_input_name( $filter['slug'], 'max' );
                    $e_name_min = flrt_range_input_name( $filter['e_name'], 'min');
                    $e_name_max = flrt_range_input_name( $filter['e_name'], 'max');
                    $absMin = $absMax = 0;
                    $hasSliderClass = ( $filter['range_slider'] === 'yes' ) ? 'wpc-form-has-slider' : 'wpc-form-without-slider';
                    $allPostsAbsMin = $allPostsAbsMax = 0;

                    foreach ( $terms as $term ) {
                        foreach( $terms as $term ) {
                            if( isset($term->min) ) {
                                $absMin = $term->min;
                            }
                            if( isset( $term->max ) ) {
                                $absMax = $term->max;
                            }

                            if( isset($term->abs_values['abs_min']) ) {
                                $allPostsAbsMin = $term->abs_values['abs_min'];
                            }

                            if( isset($term->abs_values['abs_max']) ) {
                                $allPostsAbsMax = $term->abs_values['abs_max'];
                            }
                        }
                    }

                    if($allPostsAbsMax < $allPostsAbsMin){
                        $allPostsAbsMax = $allPostsAbsMin;
                    }

                    $max = isset( $filter['values']['max'] ) ? $filter['values']['max'] : $absMax;
                    $min = isset( $filter['values']['min'] ) ? $filter['values']['min'] : $absMin;
                    $link = $url_manager->getFormActionOrFullPageUrl();
                    $range_link = $url_manager->getFormActionOrFullPageUrl(true);
                    $checked_min = '';
                    $checked_max = '';
                    if(!empty($filter['values']['min'])){
                        $checked_min = $filter['values']['min'];
                    }
                    if(!empty($filter['values']['max'])){
                        $checked_max = $filter['values']['max'];
                    }

                    $name = $filter['e_name'];

                ?>
                    <?php if (!empty($filter['show_range_list']) && $filter['show_range_list'] === 'yes') { ?>
                        <?php if (!empty($filter['range_list_input'])) { ?>
                            <ul class="wpc-filters-ul-list wpc-filters-radio wpc-filters-list-<?php echo esc_attr($filter['ID']); ?>">
                                <?php foreach ($filter['range_list_input'] as $id => $range_list_input) {
                                    $label_text = $range_list_input['range_list_range_text'];
                                    if(empty($label_text)){
                                        $label_text = $range_list_input['range_list_min_val'] . ' &mdash; ' . $range_list_input['range_list_max_val'];
                                    }

                                    $temp_min = $range_list_input['range_list_min_val'];
                                    $temp_max = $range_list_input['range_list_max_val'];

                                    if(empty($temp_min) && $id == count($filter['range_list_input'])){
                                        $temp_min = round($allPostsAbsMin);
                                    }

                                    if(empty($temp_max) && $id == count($filter['range_list_input'])){
                                        $temp_max = round($allPostsAbsMax);
                                    }


                                    $checked = false;
                                    if(isset($range_list_input['range_list_min_val']) && isset($range_list_input['range_list_max_val'])){
                                        if(empty($range_list_input['range_list_min_val'])){
                                            $range_list_input['range_list_min_val'] = '';
                                        }
                                        if(empty($range_list_input['range_list_max_val'])){
                                            $range_list_input['range_list_max_val'] = '';
                                        }
                                        if($range_list_input['range_list_min_val'] === $checked_min && $range_list_input['range_list_max_val'] === $checked_max){
                                            $checked = true;
                                        }
                                    }
                                    $link_args = [];
                                    if(!empty($range_list_input['range_list_min_val'])){
                                        $link_args[$minName] = $range_list_input['range_list_min_val'];
                                    }

                                    if(!empty($range_list_input['range_list_max_val'])){
                                        $link_args[$maxName] = $range_list_input['range_list_max_val'];
                                    }

                                    $range_link = remove_query_arg( array( $minName, $maxName ), $range_link );

                                    if(!empty($link_args) && !$checked){
                                        $range_link = add_query_arg( $link_args,  $range_link );
                                    }

                                    if(!empty($terms[$e_name_min]->name)){
                                        $terms[$e_name_min]->name = $label_text;
                                    }

                                    if(!empty($terms[$e_name_min]->cross_count)){
                                        $terms[$e_name_min]->cross_count = 0;
                                    }

                                    if(!empty($terms[$e_name_min]->range_list_input[$id])){
                                        $terms[$e_name_min]->cross_count = $terms[$e_name_min]->range_list_input[$id];
                                    }
                                    $link_attributes = 'href="'.esc_url($range_link).'"';
                                    $link_attributes .= ' class="wpc-filter-link"';
                                    $disabled       = 0;
                                    if($terms[$e_name_min]->cross_count <= 0){
                                        $disabled = 1;
                                    }
                                    $disabled_class  = $disabled ? ' wpc-term-disabled' : '';
                                    $active_class    = $checked ? ' wpc-term-selected' : '';
                                    $apply_botton_class = '';
                                    if($hide_empty && $disabled){
                                        $apply_botton_class = ' wpc-term-count-hidden-0';
                                    }
                                    ?>

                                    <li class="wpc-radio-item wpc-term-item<?php echo esc_attr( $active_class ); ?> wpc-term-count-<?php echo esc_attr( $terms[$e_name_min]->cross_count ); ?> wpc-term-id-<?php echo esc_attr($id); ?><?php echo esc_attr($apply_botton_class); ?>" id="<?php flrt_term_id('term', $filter, $id); ?>">
                                        <div class="wpc-term-item-content-wrapper">
                                            <input type="radio" name="<?php echo esc_attr($name . '-' . $set_id); ?>" id="<?php flrt_term_id('radio', $filter, $id); ?>" class="wpc-range-list-item" data-wpc-link="<?php echo esc_url( $range_link ); ?>" data-wpc-e-name="<?php echo esc_attr($filter['e_name']); ?>"
                                                   data-wpc-slug-min="<?php echo esc_attr($terms[$e_name_min]->slug); ?>"
                                                   data-wpc-slug-max="<?php echo esc_attr($terms[$e_name_max]->slug); ?>"
                                                   data-min="<?php echo esc_attr($temp_min); ?>"
                                                   data-max="<?php echo esc_attr($temp_max); ?>"
                                                   data-term-id="<?php echo esc_attr($id); ?>"
                                                    <?php checked( 1, $checked ); ?><?php echo ($checked) ? ' data-wpc-was-checked="true"' : ''; ?>
                                            />
                                            <label for="<?php flrt_term_id('radio', $filter, $id); ?>">
                                                <?php

                                                /**
                                                 * Allow developers to change filter terms html
                                                 */
                                                echo apply_filters( 'wpc_filters_radio_term_html', '<a '.$link_attributes.'>'.$terms[$e_name_min]->name.'</a>', $link_attributes, $terms[$e_name_min], $filter );

                                                ?>
                                                <?php flrt_filter_count($terms[$e_name_min], $set['show_count']['value'] ); ?>
                                            </label>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    <?php }?>
                <form action="<?php echo esc_url( $link ); ?>" method="GET" class="wpc-filter-range-form <?php echo esc_attr( $hasSliderClass ); ?>" id="wpc-filter-range-form-<?php echo esc_attr($filter['ID']); ?>">
                    <div class="wpc-filters-range-wrapper">
                        <div class="wpc-filters-range-column wpc-filters-range-min-column">
                            <?php // if there are value in $_GET we have to put it into field ?>
                            <?php // attr step should be configured in options ?>
                            <input type="number" class="wpc-filters-range-min" name="<?php echo esc_attr( $minName ); ?>" value="<?php echo esc_attr( $min ); ?>" step="<?php echo esc_attr( $filter['step'] ); ?>" data-min="<?php echo esc_attr( $absMin ); ?>" data-abs-min="<?php echo esc_attr( round($allPostsAbsMin) ); ?>" data-wpc-e-name="<?php echo esc_attr($filter['e_name']); ?>" data-wpc-slug="<?php echo esc_attr( $minName ); ?>"
                                   data-wpc-chips-text="<?php echo esc_attr( esc_html__(flrt_ucfirst_term_slug_name('min'), 'filter-everything') ); ?>"
                                   data-wpc-chip-label="<?php echo esc_attr( isset( $filter['min_num_label'] ) ? $filter['min_num_label'] : '' ); ?>"
                                   data-fid="<?php echo esc_attr( $filter['ID'] ); ?>"/>
                            <button class="wpc-range-clear" type="button" title="<?php esc_html_e('Reset', 'filter-everything' ) ?>">
                                <span class="wpc-search-clear-icon">&#215;</span>
                            </button>
                        </div>
                        <div class="wpc-filters-range-column wpc-filters-range-max-column">
                            <input type="number" class="wpc-filters-range-max" name="<?php echo esc_attr( $maxName ); ?>" value="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $filter['step'] ); ?>" data-max="<?php echo esc_attr( $absMax ); ?>"  data-abs-max="<?php echo esc_attr( round($allPostsAbsMax) ); ?>" data-wpc-e-name="<?php echo esc_attr($filter['e_name']); ?>" data-wpc-slug="<?php echo esc_attr( $maxName ); ?>"
                                   data-wpc-chips-text="<?php echo esc_attr( esc_html__(flrt_ucfirst_term_slug_name('max'), 'filter-everything') ); ?>"
                                   data-wpc-chip-label="<?php echo esc_attr( isset( $filter['max_num_label'] ) ? $filter['max_num_label'] : '' ); ?>"
                                   data-fid="<?php echo esc_attr( $filter['ID'] ); ?>"/>
                            <button class="wpc-range-clear" type="button" title="<?php esc_html_e('Reset', 'filter-everything' ) ?>">
                                <span class="wpc-search-clear-icon">&#215;</span>
                            </button>
                        </div>
                    </div>
                    <?php
                    /**
                     * @bug if $absMin === $absMax slider freezes
                     */
                    ?>
                    <?php if( $filter['range_slider'] === 'yes' ): ?>
                        <div class="wpc-filters-range-slider-wrapper">
                            <div class="wpc-filters-range-slider-control wpc-slider-control-<?php echo esc_attr( $filter['ID'] ); ?>" data-fid="<?php echo esc_attr( $filter['ID'] ); ?>"></div>
                        </div>
                    <?php else: ?>
                        <div class="wpc-filters-range-values-wrapper">
                            <p><?php echo esc_html( sprintf( __( '%s: %d &mdash; %d' ), $filter['label'], $absMin, $absMax ) ); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php
                        flrt_query_string_form_fields(
                                flrt_get_query_string_parameters(),
                                [$minName, $maxName]
                        );
                    ?>
                </form>
                <?php } ?>
            <?php  else:  ?>
                <?php esc_html_e('There are no posts with such filtering criteria on this site.', 'filter-everything' ); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

