<?php
/**
 * Beaver Builder Sorting Module
 *
 * @package FilterEverything
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SortingBeaverWidget extends FLBuilderModule {

    /**
     * Constructor function for the module.
     */
    public function __construct() {
        parent::__construct(array(
            'name'            => esc_html__( 'Sorting', 'filter-everything' ),
            'description'     => '',
            'category'        => esc_html__( 'Filter Everything', 'filter-everything' ),
            'icon'            => '',
            'dir'             => __DIR__,
            'url'             => plugins_url( __DIR__, __FILE__ ),
            'partial_refresh' => true,
        ));
    }

    public function get_icon( $icon = '' ) {
        return flrt_get_icon_logo_svg(20, 20);
    }
}

/**
 * Register the module and its form settings.
 */
$sorting = new \FilterEverything\Filter\Sorting();

FLBuilder::register_module('SortingBeaverWidget', array(
    'general' => array(
        'title'    => esc_html__( 'General', 'filter-everything' ),
        'sections' => array(
            'general' => array(
                'title'  => esc_html__( 'Settings', 'filter-everything' ),
                'fields' => array(
                    'title' => array(
                        'type'        => 'text',
                        'label'       => esc_html__( 'Title', 'filter-everything' ),
                        'default'     => esc_html__( 'Sorting', 'filter-everything' ),
                        'connections' => array( 'string' ),
                    ),
                ),
            ),
            'sorting_options' => array(
                'title'  => esc_html__( 'Sorting Options', 'filter-everything' ),
                'fields' => array(
                    'sorting_items' => array(
                        'type'         => 'form',
                        'label'        => esc_html__( 'Sorting Item', 'filter-everything' ),
                        'form'         => 'sorting_items_form',
                        'preview_text' => 'titles',
                        'multiple'     => true,
                        'default'      => $sorting->prepareForPageBuilder(),
                    ),
                ),
            ),
        ),
    ),
));

FLBuilder::register_settings_form('sorting_items_form', array(
    'title' => esc_html__( 'Sorting Item', 'filter-everything' ),
    'tabs'  => array(
        'general' => array(
            'title'    => esc_html__( 'General', 'filter-everything' ),
            'sections' => array(
                'general' => array(
                    'title'  => '',
                    'fields' => array(
                        'titles' => array(
                            'type'    => 'text',
                            'label'   => esc_html__( 'Title', 'filter-everything' ),
                            'default' => '',
                        ),
                        'orderbies' => array(
                            'type'    => 'select',
                            'label'   => esc_html__( 'Order by', 'filter-everything' ),
                            'default' => 'default',
                            'options' => $sorting->getSortingOptions(),
                            'toggle'  => array(
                                'm' => array(
                                    'fields' => array( 'meta_keys' ),
                                ),
                                'n' => array(
                                    'fields' => array( 'meta_keys' ),
                                ),
                            ),
                        ),
                        'meta_keys' => array(
                            'type'    => 'text',
                            'label'   => esc_html__( 'Meta key', 'filter-everything' ),
                            'default' => '',
                            'help'    => esc_html__( 'When ordering by Meta Key', 'filter-everything' ),
                        ),
                        'orders' => array(
                            'type'    => 'select',
                            'label'   => esc_html__( 'Order', 'filter-everything' ),
                            'default' => 'asc',
                            'options' => array(
                                'asc'  => esc_html__( 'ASC', 'filter-everything' ),
                                'desc' => esc_html__( 'DESC', 'filter-everything' ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
));
