<?php

if ( ! defined('ABSPATH') ) {
	exit;
}

$arguments['title'] = $attributes['title'];
foreach ($attributes['sorting_options'] as $key => $setting) {
	$arguments['titles'][$key]    = $setting['title'];
	$arguments['orderbies'][$key] = $setting['order_by'];
	$arguments['orders'][$key]    = $setting['order'];
	$arguments['meta_keys'][$key] = (!empty($setting['meta_key'])) ? $setting['meta_key'] : '';
}
ob_start();
the_widget( '\FilterEverything\Filter\SortingWidget', $arguments, array(
	// In block-based widget areas core already wraps every block in
	// <div class="widget widget_block">, so the default the_widget()
	// wrapper (<div class="widget %s">) would nest .widget inside
	// .widget and themes' em-based .widget font sizes would compound.
	'before_widget' => '<div class="%s">',
	'after_widget'  => '</div>',
) );
$html = ob_get_clean();
echo $html;
