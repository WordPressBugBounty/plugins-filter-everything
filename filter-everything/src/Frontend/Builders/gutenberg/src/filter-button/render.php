<?php

if ( ! defined( 'ABSPATH' ) ) exit;

$id_attr = ! empty( $attributes['id'] ) ? ' id="' . esc_attr( $attributes['id'] ) . '"' : '';

echo do_shortcode( "[fe_open_button{$id_attr}]" );
