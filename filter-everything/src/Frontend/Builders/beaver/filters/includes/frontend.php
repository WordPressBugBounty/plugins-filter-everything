<?php
/**
 * Beaver Builder Filters Module - Frontend Template
 *
 * @package FilterEverything
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Check if we're in the builder backend
if ( FLBuilderModel::is_builder_active() ) {
    echo '<h3>' . esc_html__( 'Filter Everything - Filters', 'filter-everything' ) . '</h3>';
    return;
}

// Convert settings to array format expected by FiltersWidget
$widget_settings = array(
    'title'      => ! empty( $settings->title ) ? $settings->title : esc_html__( 'Filter', 'filter-everything' ),
    'show_count' => ! empty( $settings->show_count ) && $settings->show_count === '1' ? 1 : 0,
    'chips'      => ! empty( $settings->chips ) && $settings->chips === '1' ? 1 : 0,
);

// Use the_widget to render the FiltersWidget
ob_start();
the_widget( '\FilterEverything\Filter\FiltersWidget', $widget_settings );
$html = ob_get_clean();
echo $html;
