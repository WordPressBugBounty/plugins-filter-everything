<?php
/**
 * Beaver Builder Filters Module
 *
 * @package FilterEverything
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class FiltersBeaverWidget extends FLBuilderModule
{

    /**
     * Constructor function for the module.
     */
    public function __construct()
    {
        parent::__construct(array(
            'name'            => esc_html__('Filters', 'filter-everything'),
            'description'     => '',
            'category'        => esc_html__('Filter Everything', 'filter-everything'),
            'icon'            => '',
            'dir'             => __DIR__,
            'url'             => plugins_url(__DIR__, __FILE__),
            'partial_refresh' => true,
        ));
    }

    public function get_icon($icon = '')
    {
        return flrt_get_icon_logo_svg(20, 20);
    }
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('FiltersBeaverWidget', array(
    'general' => array(
        'title'    => esc_html__('General', 'filter-everything'),
        'sections' => array(
            'general' => array(
                'title'  => esc_html__('Settings', 'filter-everything'),
                'fields' => array(
                    'title'      => array(
                        'type'        => 'text',
                        'label'       => esc_html__('Title', 'filter-everything'),
                        'default'     => esc_html__('Filter', 'filter-everything'),
                        'connections' => array('string'),
                    ),
                    'show_count' => array(
                        'type'    => 'select',
                        'label'   => esc_html__('Show the number of posts found', 'filter-everything'),
                        'default' => '0',
                        'options' => array(
                            '1' => esc_html__('Yes', 'filter-everything'),
                            '0' => esc_html__('No', 'filter-everything'),
                        ),
                    ),
                    'chips'      => array(
                        'type'    => 'select',
                        'label'   => esc_html__('Show selected terms (Chips)', 'filter-everything'),
                        'default' => '0',
                        'options' => array(
                            '1' => esc_html__('Yes', 'filter-everything'),
                            '0' => esc_html__('No', 'filter-everything'),
                        ),
                    ),
                ),
            ),
        ),
    ),
));
