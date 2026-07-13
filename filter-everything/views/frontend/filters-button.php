<?php
/**
 * The Template for displaying Filters opening/closing button.
 *
 * This template can be overridden by copying it to yourtheme/filters/filters-button.php.
 *
 * $wpc_found_posts - int|NULL, found posts number
 *
 * $button_error - array, errors
 *
 * @see https://filtereverything.pro/resources/templates-overriding/
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

$error = '';
if (!empty($button_error)) {
    if (!empty($button_error['filter_set_error'])) {
        $error .= ' data-wpc-button-filter-set-error="' . esc_attr($button_error['filter_set_error']) . '" ';
    }
    if (!empty($button_error['filter_widget_error'])) {
        $error .= ' data-wpc-button-widget-error="' . esc_attr($button_error['filter_widget_error']) . '" ';
    }
}

?>
<div class="wpc-filters-open-button-container wpc-open-button-<?php echo esc_attr( $set_id ); ?> <?php echo esc_attr( $wrap_class ); ?>">
    <a class="<?php echo esc_attr( $class ); ?>" href="javascript:void(0);" data-wid="<?php echo esc_attr( $set_id ); ?>"<?php echo $error; ?>><span class="wpc-button-inner"><?php
            // Button icon
            flrt_get_icon_html();

            ?><span class="wpc-filters-button-text"><?php

            if( $wpc_found_posts !== NULL ){
                esc_html_e( sprintf( __('Filtered %s', 'filter-everything'), '('.$wpc_found_posts.')' ) );
            } else {
                esc_html_e('Filters', 'filter-everything');
            }

            ?></span></span></a>
</div>