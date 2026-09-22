<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
// Only this block's own attributes (block.json). Other plugins add attributes
// to every registered block type — e.g. WP User Manager's "wpum_restrict_*" —
// and the_widget() passes the instance to "widget_display_callback", where
// WP User Manager reads them as legacy-widget restrictions and hides the widget.
$instance = array_intersect_key( $attributes, array_flip( array( 'title', 'set_id', 'mobile' ) ) );

ob_start();
the_widget( '\FilterEverything\Filter\ChipsWidget', $instance, array(
	// In block-based widget areas core already wraps every block in
	// <div class="widget widget_block">, so the default the_widget()
	// wrapper (<div class="widget %s">) would nest .widget inside
	// .widget and themes' em-based .widget font sizes would compound.
	'before_widget' => '<div class="%s">',
	'after_widget'  => '</div>',
) );
$html = ob_get_clean();
echo $html;
