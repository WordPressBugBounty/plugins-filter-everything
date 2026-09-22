<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Check if we're in the builder backend
if ( FLBuilderModel::is_builder_active() ) {
    echo '<h3>' . esc_html__( 'Filter Everything - Chips', 'filter-everything' ) . '</h3>';
    return;
}

// Get settings
$settings->mobile = ! empty( $settings->mobile ) && $settings->mobile === '1';
ob_start();
the_widget('\FilterEverything\Filter\ChipsWidget', $settings );
$html = ob_get_clean();
echo $html;
