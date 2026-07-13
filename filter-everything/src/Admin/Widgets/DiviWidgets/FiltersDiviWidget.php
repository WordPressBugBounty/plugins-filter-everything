<?php
namespace FilterEverything\Filter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('ET_Builder_Module')) {
    return;
}

use ET_Builder_Module;
class FiltersDiviWidget extends ET_Builder_Module
{

    public $slug = 'filter_everything';
    public $vb_support = 'off';

    protected $module_credits = array(
        'module_uri' => FLRT_PLUGIN_URL,
        'author_uri' => FLRT_PLUGIN_URL,
    );


    /*public function __construct()
    {
        parent::__construct();

        add_action('wp_footer', array($this, 'flrt_divi_widget_styles'));
    }

    public function flrt_divi_widget_styles()
    {
        ?>

        <style>
            .et-fb-tabs__panel--filter_everything_settings_tab .et-fb-modal__support-notice {
                display: none;
            }
        </style>
        <?php
    }*/

    public function init()
    {
        $this->icon_path = FLRT_PLUGIN_DIR_PATH . 'assets/img/divi-widget-icon.svg';
        $this->name = esc_html__( 'Filter Everything - Filters', 'filter-everything' );
        $this->advanced_fields = [];

        $this->settings_modal_tabs['css'] = [];
        $this->settings_modal_tabs['advanced'] = [];


        $this->settings_modal_toggles = [
            'general' => array(
                'toggles' => array(
                    'wpc_filter_settings' => array(
                        'priority' => 1,
                        'title' => esc_html__('Filter settings', 'filter-everything'),
                    ),
                ),
            )
        ];
    }


    public function get_fields()
    {
        return array(
            'title'     => array(
                'label'           => esc_html__( 'Title', 'filter-everything' ),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'toggle_slug'     => 'wpc_filter_settings',
                'default' => '',
            ),
            'show_count'     => array(
                'label'           => esc_html__( 'Show the number of posts found', 'filter-everything' ),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => array(
                    'off' => esc_html__('No', 'filter-everything'),
                    'on'  => esc_html__('Yes', 'filter-everything'),
                ),
                'toggle_slug'     => 'wpc_filter_settings',
                'default'       => 'off',
            ),
            'chips'     => array(
                'label'           => esc_html__( 'Show selected terms (Chips)', 'filter-everything' ),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => array(
                    'off' => esc_html__('No', 'filter-everything'),
                    'on'  => esc_html__('Yes', 'filter-everything'),
                ),
                'toggle_slug'     => 'wpc_filter_settings',
                'default'       => 'off',
            ),
        );
    }

    public function render($attrs, $content, $render_slug)
    {
        if(isset($this->props['show_count']) && $this->props['show_count'] === 'on'){
            $this->props['show_count'] = true;
        }else{
            $this->props['show_count'] = false;
        }

        if(isset($this->props['chips']) && $this->props['chips'] === 'on'){
            $this->props['chips'] = true;
        }else{
            $this->props['chips'] = false;
        }
        ob_start();
        the_widget('\FilterEverything\Filter\FiltersWidget', $this->props );
        $html = ob_get_clean();
        return $html;
    }
}

new FiltersDiviWidget;