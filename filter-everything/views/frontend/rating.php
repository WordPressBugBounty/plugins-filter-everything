<?php
/**
 * The Template for displaying filter radio buttons.
 *
 * This template can be overridden by copying it to yourtheme/filters/rating.php
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
    'hide' => $view_args['ask_to_select_parent']
];
$selected_and_above_status = (isset($filter['selected_and_above']) && $filter['selected_and_above'] === 'yes') ? true : false;

?>
<div class="<?php echo flrt_filter_class( $filter, [], $terms, $args ); // Already escaped ?>" data-fid="<?php echo esc_attr( $filter['ID'] ); ?>">
    <?php flrt_filter_header( $filter, $terms ); // Safe, escaped ?>
    <div class="flrt-stars-wpc-filter-content <?php echo esc_attr( flrt_filter_content_class( $filter ) ); ?>" data-selected-and-above="<?php echo $selected_and_above_status; ?>"  data-show-term-count="<?php echo ( $set['show_count']['value'] === 'yes' ) ? true : false; ?>">
        <ul class="flrt-stars-filter wpc-filters-ul-list wpc-filters-radio wpc-filters-list-<?php echo esc_attr( $filter['ID'] ); ?>">
            <?php if( ! empty( $terms ) || $view_args['ask_to_select_parent']) :

            if( $view_args['ask_to_select_parent'] !== false ) { ?>
            <li><?php echo esc_html( $view_args['ask_to_select_parent'] ); ?></li>
            <?php } else {
                    $flrt_rating_slug = [];
                    if($selected_and_above_status){
                        $i = 1;
                        foreach ($terms as $id => $term_object){
                            $flrt_rating_slug[$i] = $term_object->slug;
                            $i++;
                        }
                    }
                    $cross_count = 0;
                    $rating_num = 1;//rating stars
                    $products_checked_sum = [];
                    $star_switch = ($selected_and_above_status) ?  false : true;
                    $switch_engaged = false;
                    foreach ( $terms as $id => $term_object ) {
                        if(mb_strpos($term_object->slug, 'rated') !== false){
                        $disabled       = 0;
                        $checked        = ( in_array( $term_object->slug, $filter['values'] ) ) ? 1 : 0;

                        if ($checked){
                            $products_checked_sum[] = $term_object->cross_count;
                        }
                        if ( isset( $term_object->wp_queried ) && $term_object->wp_queried ) {
                            $checked    = 1;
                            $disabled   = 1;
                        }

                        if(empty($filter['values'])){
                            $star_switch = false;
                        }
                        if(!$switch_engaged){
                            if($checked){
                                $switch_engaged = true;
                                $star_switch = ($selected_and_above_status) ?  true : false;
                            }
                        }

                        $slug = $term_object->slug;

                        $link = $url_manager->getTermUrl($slug, $filter['e_name'], $filter['entity'] );
                        if($selected_and_above_status){
                            if(isset($filter['values'][0])){
                                if($filter['values'][0] === $slug)  $link = $url_manager->getTermUrl($slug, $filter['e_name'], $filter['entity'], '', ['rating_slug' => $slug]);
                            }
                        }

                        $link_attributes = 'href="'.esc_url($link).'"';
                        $link_attributes .= ' class="wpc-filter-link"';
                        if ($selected_and_above_status){
                            $link_title = ($rating_num < 5) ?
                                sprintf(esc_html__("Show products rated %d+ stars", 'filter-everything' ), $rating_num) :
                                sprintf(esc_html__("Show products rated %d stars", 'filter-everything' ), $rating_num);
                        } else {
                            $link_title  = sprintf(esc_html__("Show products with %d star rating", 'filter-everything' ), $rating_num);
                        }


                        $link_attributes .= ' title="' . esc_attr($link_title) . '"';
                        $name            = ( $disabled > 0 ) ? $filter['e_name'] . '-disabled' : $filter['e_name'];
                        $data_wpc_term_count = ( $set['show_count']['value'] === 'yes' ) ? $term_object->cross_count : 0;

                        ?>
                        <li class="wpc-radio-item wpc-term-item wpc-term-count-<?php echo esc_attr( $term_object->cross_count ); ?> wpc-term-id-<?php echo esc_attr($id); ?>" id="<?php flrt_term_id('term', $filter, $id); ?>">
                            <div class="wpc-term-item-content-wrapper">
                                <input <?php checked( 1, $checked ); disabled( 1, $disabled ) ?> type="radio" data-wpc-link="<?php echo esc_url( $link ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php flrt_term_id('radio', $filter, $id); ?>" class="flrt-star-input"/>
                                <label class="flrt-star-label flrt-rating-numb-<?php echo $rating_num;?> <?php echo ($star_switch) ? ' flrt-star-label-hover' : '';?><?php echo ($checked) ? ' flrt-star-label-checked flrt-star-label-hover' : '';?><?php echo ($selected_and_above_status && !$checked) ? ' flrt-star-label-not-checked flrt-remove-star-check' : '';?>" data-rating-num="<?php echo $rating_num;?>" for="<?php flrt_term_id('radio', $filter, $id); ?>"  data-wpc-term-count="<?php echo $data_wpc_term_count; ?>" data-star-rating-index="<?php echo $rating_num;?>">
                                    <a <?php echo $link_attributes; ?>>
                                        <?php echo flrt_rating_star();?>
                                    </a>
                                </label></div></li>
                    <?php $rating_num++;
                        }
                    } /* end foreach */

            } ?>
            <?php  else :
                flrt_filter_no_terms_message();
            endif;
            ?></ul>
        <span id="flrt-wpc-term-count" class="wpc-term-count-value wpc-term-count-value wpc-term-count">
            <?php echo (!empty($products_checked_sum) && $set['show_count']['value'] === 'yes') ? array_sum($products_checked_sum) : ''; ?>
        </span>
    </div>
</div>