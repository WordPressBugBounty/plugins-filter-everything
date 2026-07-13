<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ET_Builder_Module')) {
    return;
}

use AllowDynamicProperties;
use ET_Builder_Module;

#[AllowDynamicProperties]
class ChipsDiviWidget extends ET_Builder_Module
{

    public $slug = 'filter_everything_chips';
    public $vb_support = 'off';


    protected $module_credits = array(
        'module_uri' => FLRT_PLUGIN_URL,
        'author_uri' => FLRT_PLUGIN_URL,
    );

    public function init()
    {
        $this->icon_path = FLRT_PLUGIN_DIR_PATH . 'assets/img/divi-widget-icon.svg';
        $this->name = esc_html__('Filter Everything - Chips', 'filter-everything');
        $this->advanced_fields = [];

        $this->settings_modal_tabs['css'] = [];
        $this->settings_modal_tabs['advanced'] = [];


        $this->settings_modal_toggles = [
            'general' => array(
                'toggles' => array(
                    'filter_chips' => array(
                        'priority' => 1,
                        'title'    => esc_html__('Filter settings', 'filter-everything'),
                    )
                ),
            )
        ];
    }

    public function get_fields()
    {
        return array(
            'title'  => array(
                'label'           => esc_html__('Title', 'filter-everything'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'toggle_slug'     => 'filter_chips',
                'default'         => '',
            ),
            'set_id' => array(
                'label'           => esc_html__('Show Chips only for Set with IDs', 'filter-everything'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'placeholder'     => esc_html__('e.g. 2745, 324', 'filter-everything'),
                'toggle_slug'     => 'filter_chips',
            ),
            'mobile' => array(
                'label'           => esc_html__('Show on mobile', 'filter-everything'),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => array(
                    'off' => esc_html__('No', 'filter-everything'),
                    'on'  => esc_html__('Yes', 'filter-everything'),
                ),
                'toggle_slug'     => 'filter_chips',
                'default'         => 'on',
            ),

        );
    }

    public function render($attrs, $content, $render_slug)
    {
        if (isset($this->props['mobile']) && $this->props['mobile'] === 'on') {
            $this->props['mobile'] = true;
        } else {
            $this->props['mobile'] = false;
        }
        ob_start();
        the_widget('\FilterEverything\Filter\ChipsWidget', $this->props);
        $html = ob_get_clean();
        return $html;
    }
}

new ChipsDiviWidget;