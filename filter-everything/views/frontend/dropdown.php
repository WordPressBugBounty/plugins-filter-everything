<?php
/**
 * The Template for displaying filter dropdown.
 *
 * This template can be overridden by copying it to yourtheme/filters/dropdown.php.
 *
 * $set - array, with the Filter Set parameters
 * $filter - array, with the Filter parameters
 * $url_manager - object, of the UrlManager PHP class
 * $terms - array, with objects of all filter terms except excluded
 * $noSelectUrl - string, URL for default option without selected term
 *
 * @see https://filtereverything.pro/resources/templates-overriding/
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

$noSelectUrl    = ( empty( $filter['values'] ) ) ? $url_manager->getResetUrl() : $url_manager->getTermUrl( reset( $filter['values'] ), $filter['e_name'], $filter['entity'] );
$show_term_name = true;
$is_swatch      = false;
$is_brand       = ( in_array( $filter['e_name'], flrt_brand_filter_entities() ) );
$use_select2    = false;
$data_default   = '';
$preload_html   = '';
$data_color     = '';
$hide_for_apply_button_mode = flrt_check_apply_buttom_mode($set);
$is_hide_empty_terms = (isset($set['hide_empty']['value']) && $set['hide_empty']['value'] === 'yes');
$empty_parent_filter = !empty($view_args['empty_parent_filter']) && $view_args['empty_parent_filter'] === true;

$args = [
        'hide_empty' => $set['hide_empty']['value'],
        'use_apply_button' => $view_args['use_apply_button'],
        'hide_empty_filter' => isset($set['hide_empty_filter']['value']) ? $set['hide_empty_filter']['value'] : '',
];

if ( flrt_get_experimental_option( 'select2_dropdowns' ) === 'on' ){
    $use_select2 = true;
}

if ( flrt_get_experimental_option('use_color_swatches') === 'on' ) {
    $swatch_taxes   = flrt_get_experimental_option( 'color_swatches_taxonomies', [] );
    $is_swatch      = ( in_array( $filter['e_name'], $swatch_taxes ) );
}

if ( $is_brand && ! $is_swatch ) {
    $data_default = ' data-brand='.FLRT_PLUGIN_DIR_URL.'assets/img/no-image.png';
}

if ( $is_swatch ){
    $data_default = ' data-image='.FLRT_PLUGIN_DIR_URL.'assets/img/no-image.png';
}
$rating_slugs = flrt_rating_slugs();
$parent_filter_apply_button_data = flrt_parent_filter_apply_button_data($filter, $view_args);
$parent_filter_apply_class = flrt_parent_filter_apply_class($filter, $view_args, $terms);

$is_parent_has_terms = false;
if(!empty($terms) && $view_args['use_apply_button']){

    $first_term = reset($terms);
    if(!empty($first_term->show_with_parent)){
        $filter['hide_until_parent_class'] = false;
    }
}
?>
<div class="<?php echo flrt_filter_class( $filter, [], $terms, $args ); // Already escaped ?><?php echo $parent_filter_apply_class; ?>" data-fid="<?php echo esc_attr( $filter['ID'] ); ?>" data-filter-e-name="<?php echo esc_attr($filter['e_name']); ?>"<?php echo $parent_filter_apply_button_data; ?>>
    <?php flrt_filter_header( $filter, $terms ); // Safe, escaped ?>
    <div class="<?php echo esc_attr( flrt_filter_content_class( $filter ) ); ?>">
        <?php if( ! empty( $terms ) || $view_args['ask_to_select_parent'] ): ?>
            <select id="wpc-<?php echo esc_attr( $filter['entity'] ); ?>-<?php echo esc_attr( $filter['e_name'] ); ?>-<?php echo esc_attr( $filter['ID'] ); ?>"
                    aria-label="wpc-<?php echo esc_attr( $filter['entity'] ); ?>-<?php echo esc_attr( $filter['e_name'] ); ?>-<?php echo esc_attr( $filter['ID'] ); ?>"
                    class="wpc-filters-widget-select" style="width: 100%">
                <?php if( $view_args['ask_to_select_parent'] !== false && !$view_args['use_apply_button'] ) : ?>
                    <option class="wpc-dropdown-default" value="0" data-wpc-link="<?php echo esc_attr( $noSelectUrl ); ?>" id="wpc-option-<?php echo esc_attr( $filter['entity'] ); ?>-<?php echo esc_attr( $filter['e_name'] ); ?>-0"><?php
                    echo esc_html( $view_args['ask_to_select_parent'] );
                    ?></option>
                <?php else: ?>
                        <?php
                        $default_option_text = esc_html( flrt_dropdown_default_option( $filter ) );
                       if( $view_args['ask_to_select_parent'] !== false && $empty_parent_filter && $view_args['use_apply_button'] ){
                           $default_option_text = esc_html( $view_args['ask_to_select_parent'] );
                        }?>

                        <option<?php echo esc_html( $data_default ); ?> class="wpc-dropdown-default wpc-dropdown-default-<?php echo esc_attr( $filter['e_name'] ); ?>" value="0" data-wpc-link="<?php echo esc_attr( $noSelectUrl ); ?>" id="wpc-option-<?php echo esc_attr( $filter['entity'] ); ?>-<?php echo esc_attr( $filter['e_name'] ); ?>-0" data-wpc-select-parent-text="<?php echo esc_attr($view_args['ask_to_select_parent']);?>" data-wpc-default-option-text="<?php echo esc_attr(flrt_dropdown_default_option( $filter )); ?>"><?php
                              echo $default_option_text;
                            ?></option>
                        <?php

                        foreach ( $terms as $id => $term_object ) {
                            $disabled          = 0;
                            $data_image        = '';
                            $data_color        = '';
                            $selected          = ( in_array( $term_object->slug, $filter['values'] ) ) ? 1 : 0;
                            $term_hidden_class = '';
                            $data_count        = '';
                            $data_rating       = '';

                            if( isset( $term_object->wp_queried ) && $term_object->wp_queried ){
                                $disabled   = 1;
                            }

                            if ( $is_brand && ! $is_swatch ) {
                                $src = flrt_get_term_brand_image( $term_object->term_id, $filter );
                                if ( $src ) {
                                    $data_image = ' data-brand="' . esc_url( $src ) . '"';
                                    $preload_html .= '<img class="wpc-preload-img" src="'. esc_url( $src ) .'" />'."\r\n";
                                }

                                if ( $filter['show_term_names'] === 'no' ) {
                                    $term_hidden_class = ' wpc-hidden-term-name';
                                }
                            }

                            if ( $is_swatch ) {
                                $src = flrt_get_term_swatch_image( $term_object->term_id, $filter );

                                if ( $src ) {
                                    $data_image = ' data-image="' . esc_url( $src ) . '"';
                                    $preload_html .= '<img class="wpc-preload-img" src="'. esc_url( $src ) .'" />'."\r\n";
                                } else {
                                    $maybe_color = flrt_get_term_swatch_color( $term_object->term_id, $filter );
                                    if ( $maybe_color ) {
                                        $data_color = ' data-color="' . esc_attr( $maybe_color ) . '"';
                                    } else {
                                        $data_color = ' data-color="none"';
                                    }
                                }

                                if ( $filter['show_term_names'] === 'no' ) {
                                    $term_hidden_class = ' wpc-hidden-term-name';
                                }
                            }

                            if( $set['show_count']['value'] === 'yes' && $use_select2 ) {
                                $data_count        = ' data-count="' . esc_attr( $term_object->cross_count ) . '"';
                            }

                            $hidden_class_for_apply_button_mode = '';
                            if($hide_for_apply_button_mode){
                                $hidden_class_for_apply_button_mode = ($term_object->cross_count <= 0 && $is_hide_empty_terms) ? esc_attr(' wpc-term-count-hidden-0') : '';
                                if($term_object->cross_count > 0){
                                    $hidden_class_for_apply_button_mode .= esc_attr(' wpc-has-terms');
                                }
                                if($selected){
                                    $hidden_class_for_apply_button_mode .= esc_attr(' wpc-term-count-hidden-checked-0');
                                }
                            }

                            $rating = 0;
                            if( mb_strpos( $term_object->slug, 'rated-' ) !== false){
                                $pieces = explode("-", $term_object->slug);
                                $rating = (int) isset( $pieces[1] ) ? $pieces[1] : 0;
                                if ($rating < 1 || $rating > 5) {
                                    $rating = 0;
                                }
                                $data_rating = ' data-star-rating="' . (int) $rating . '"';
                            }
                            $rating_data = (isset($rating_slugs[$term_object->slug])) ? ' data-rating-num="' . esc_attr($rating_slugs[$term_object->slug]) . '" ' : '';
                            $show_with_parent_class = '';
                            if(isset($term_object->show_with_parent)) {
                                if($term_object->show_with_parent === true){
                                    $show_with_parent_class = ' wpc-show-with-parent-true';
                                }else{
                                    $show_with_parent_class = ' wpc-show-with-parent-false';
                                }
                            }

                            if (!isset($term_object->show_with_parent)) {
                                if ($view_args['ask_to_select_parent'] !== false && $view_args['use_apply_button']) {
                                    if ((!$selected)) {
                                        $show_with_parent_class = ' wpc-show-with-parent-false';
                                    }
                                }
                            }

                            ?>
                            <option<?php echo $data_image . $data_color . $data_count . $data_rating; // data-* values are esc_attr/esc_url-escaped and quoted above ?> data-wpc-chip="<?php echo esc_attr( $term_object->name ); ?>" class="wpc-term-item wpc-term-count-<?php echo esc_attr( $term_object->cross_count ); ?> wpc-term-id-<?php echo esc_attr($term_object->term_id); echo esc_attr( $term_hidden_class ); ?><?php echo $hidden_class_for_apply_button_mode; ?><?php echo esc_attr($show_with_parent_class); ?>" value="<?php echo esc_attr( $term_object->term_id ); ?>" <?php selected( 1, $selected ); ?> <?php disabled( 1, $disabled ); ?> data-wpc-link="<?php echo esc_attr( $url_manager->getTermUrl( $term_object->slug, $filter['e_name'], $filter['entity'] ) ); ?>" id="wpc-option-<?php echo esc_attr( $filter['entity'] ); ?>-<?php echo esc_attr($filter['e_name']); ?>-<?php echo esc_attr( $id ); ?>" data-wpc-e-name="<?php echo esc_attr($filter['e_name']); ?>" data-wpc-slug="<?php echo esc_attr( $term_object->slug ); ?>" data-term-id="<?php echo esc_attr($id); ?>"<?php echo $rating_data; ?>>
                                <?php
                                if ($rating > 0){
                                    if($use_select2){
                                        $rating_html = '';
                                        $i = 1;
                                        $label_star = '<span class="flrt-star-label %s">' . flrt_rating_star() . '</span>';
                                        while($i <=5){
                                            $star_class = '';
                                            if($i <= $rating){
                                                $star_class = 'flrt-star-label-hover';
                                            }
                                            $rating_html .= sprintf($label_star, $star_class);
                                            $i++;
                                        }
                                        echo $rating_html;
                                    }else{
                                        echo sprintf(
                                                _n('%d star', '%d stars', $rating, 'filter-everything'),
                                                $rating
                                        );
                                    }
                                }else{
                                    echo esc_html( $term_object->name );
                                }

                                if( $set['show_count']['value'] === 'yes' && ! $use_select2 ) {
                                    echo esc_html( ' ('.$term_object->cross_count.')' );
                                }
                                ?></option>
                        <?php } ?><!-- end foreach -->

                <?php endif; ?>
            </select>
            <?php echo $preload_html; ?>
        <?php else:
            flrt_filter_no_terms_message( 'p' );
        endif; ?>
    </div>
</div>