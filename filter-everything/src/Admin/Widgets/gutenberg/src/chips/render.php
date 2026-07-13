<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
ob_start();
the_widget( '\FilterEverything\Filter\ChipsWidget', $attributes, array(
	// In block-based widget areas core already wraps every block in
	// <div class="widget widget_block">, so the default the_widget()
	// wrapper (<div class="widget %s">) would nest .widget inside
	// .widget and themes' em-based .widget font sizes would compound.
	'before_widget' => '<div class="%s">',
	'after_widget'  => '</div>',
) );
$html = ob_get_clean();
echo $html;
