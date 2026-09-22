<?php
/**
 * Beaver Builder Sorting Module - Frontend Template
 *
 * @package FilterEverything
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( FLBuilderModel::is_builder_active() ) {
    echo '<h3>' . esc_html__( 'Filter Everything - Sorting', 'filter-everything' ) . '</h3>';
    return;
}

$widget_settings = array(
    'title' => ! empty( $settings->title ) ? $settings->title : esc_html__( 'Sorting', 'filter-everything' ),
);


if ( ! empty( $settings->sorting_items ) && is_array( $settings->sorting_items ) ) {
    foreach ( $settings->sorting_items as $key => $item ) {
        $widget_settings['titles'][$key]    = ! empty( $item->titles ) ? $item->titles : '';
        $widget_settings['orderbies'][$key] = ! empty( $item->orderbies ) ? $item->orderbies : 'default';
        $widget_settings['orders'][$key]    = ! empty( $item->orders ) ? $item->orders : 'asc';
        $widget_settings['meta_keys'][$key] = ! empty( $item->meta_keys ) ? $item->meta_keys : '';
    }
}
ob_start();
the_widget( '\FilterEverything\Filter\SortingWidget', $widget_settings );
$html = ob_get_clean();
echo $html;
