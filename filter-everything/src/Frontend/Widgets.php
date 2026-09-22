<?php


namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Widgets
{
    function __construct(){
        add_action( 'widgets_init', array( $this, 'register' ) );
        add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( $this, 'hideFromLegacyWidgetBlock' ) );
    }

    public function register(){
        register_widget( '\FilterEverything\Filter\FiltersWidget' );
        register_widget( '\FilterEverything\Filter\ChipsWidget' );
        register_widget( '\FilterEverything\Filter\SortingWidget' );
    }

    /**
     * Hides legacy widgets from the block-based widgets editor inserter and search,
     * because they are replaced with Gutenberg-style blocks there.
     * Already placed widget instances keep working and remain editable.
     */
    public function hideFromLegacyWidgetBlock( $widget_types ){
        $widget_types[] = 'wpc_filters_widget';
        $widget_types[] = 'wpc_chips_widget';
        $widget_types[] = 'wpc_sorting_widget';

        return $widget_types;
    }
}

new Widgets();