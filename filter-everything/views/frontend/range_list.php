<?php
/**
 * The Template for displaying filter radio buttons.
 *
 * This template can be overridden by copying it to yourtheme/filters/range-list.php
 *
 * $set - array, with the Filter Set parameters
 * $filter - array, with the Filter parameters
 * $url_manager - object, of the UrlManager PHP class
 * $terms - array, with objects of all filter terms except excluded
 *
 * @see https://filtereverything.pro/resources/templates-overriding/
 */

if (!defined('ABSPATH')) {
    exit;
}
$args = [
        'hide' => $view_args['ask_to_select_parent'],
        'use_apply_button' => $view_args['use_apply_button'],
        'hide_empty' => $set['hide_empty']['value'],
        'hide_empty_filter' => isset($set['hide_empty_filter']['value']) ? $set['hide_empty_filter']['value'] : '',
];
$checked_min_value = (!empty($_GET['min_price']) && is_numeric($_GET['min_price'])) ? $_GET['min_price'] : false;
$checked_max_value = (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) ? $_GET['max_price'] : false;
?>
<div class="<?php echo flrt_filter_class($filter, [], $terms, $args); // Already escaped ?>"
     data-fid="<?php echo esc_attr($filter['ID']); ?>">
    <?php flrt_filter_header($filter, $terms); // Safe, escaped ?>
    <div class="<?php echo esc_attr(flrt_filter_content_class($filter)); ?>">
        <?php flrt_filter_search_field($filter, $view_args, $terms); ?>
        <ul class="wpc-filters-ul-list wpc-filters-radio wpc-filters-list-<?php echo esc_attr($filter['ID']); ?>">
            <?php if (!empty($terms) || $view_args['ask_to_select_parent']):

                if ($view_args['ask_to_select_parent'] !== false) { ?>
                    <li><?php echo esc_html($view_args['ask_to_select_parent']); ?></li>
                <?php } else {

                    if (!empty($filter['range_list_input'])) {
                        foreach ($filter['range_list_input'] as $key => $range_values) { ?>
                            <?php
                            $min_val = $range_values['range_list_min_val'];
                            $max_val = $range_values['range_list_max_val'];
                            $label   = $range_values['range_list_range_text'];
                            $link    = $url_manager->getFormActionOrFullPageUrl();
                            $checked = false;
                            if($checked_min_value == $min_val && $checked_max_value == $max_val){
                                $checked = true;
                            }
                            var_dump($link);
                            var_dump($slug);
                            ?>
                            <li class="wpc-radio-item wpc-term-item">
                                <div class="wpc-term-item-content-wrapper">
                                    <input <?php checked( 1, $checked ); disabled( 1, $disabled ) ?> type="radio" data-wpc-link="<?php echo esc_url( $link ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php flrt_term_id('radio', $filter, $id); ?>"/>
                                    <label for="<?php flrt_term_id('radio', $filter, $id); ?>"><?php
                                        /**
                                         * Allow developers to change filter terms html
                                         */
                                        echo apply_filters( 'wpc_filters_radio_term_html', '<a '.$link_attributes.'>'.$term_object->name.'</a>', $link_attributes, $term_object, $filter );

                                        ?><?php flrt_filter_count( $term_object, $set['show_count']['value'] ); ?></label>

                                </div>

                            </li>
                        <?php } /* end foreach */
                    } ?>
                <?php } /* end if ask to select parent */ ?>
            <?php else:
                flrt_filter_no_terms_message();
            endif;
            ?>      </ul>
        <?php flrt_filter_more_less($filter); ?>
    </div>
</div>
