<?php

if ( ! defined( 'ABSPATH' ) ) exit;


if ( !defined('FLRT_FILTERS_PRO') ) exit;

$id_attr = ! empty( $attributes['id'] ) ? ' id="' . esc_attr( $attributes['id'] ) . '"' : '';

echo do_shortcode( "[fe_open_button{$id_attr}]" );
