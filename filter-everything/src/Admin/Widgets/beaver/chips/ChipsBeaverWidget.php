<?php
/**
 * Beaver Builder Chips Module
 *
 * @package FilterEverything
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChipsBeaverWidget extends FLBuilderModule
{

    /**
     * Constructor function for the module.
     */
    public function __construct()
    {
        parent::__construct(array(
            'name'            => esc_html__('Chips', 'filter-everything'),
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
FLBuilder::register_module('ChipsBeaverWidget', array(
    'general' => array(
        'title'    => esc_html__('General', 'filter-everything'),
        'sections' => array(
            'general' => array(
                'title'  => esc_html__('Settings', 'filter-everything'),
                'fields' => array(
                    'title'  => array(
                        'type'        => 'text',
                        'label'       => esc_html__('Title', 'filter-everything'),
                        'default'     => '',
                        'connections' => array('string'),
                    ),
                    'set_id' => array(
                        'type'        => 'text',
                        'label'       => esc_html__('Show Chips only for Set with IDs', 'filter-everything'),
                        'default'     => '',
                        'placeholder' => esc_html__('e.g. 2745, 324', 'filter-everything'),
                        'help'        => esc_html__('Comma-separated list of Set IDs', 'filter-everything'),
                        'connections' => array('string'),
                    ),
                    'mobile' => array(
                        'type'    => 'select',
                        'label'   => esc_html__('Show on mobile', 'filter-everything'),
                        'default' => '1',
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
