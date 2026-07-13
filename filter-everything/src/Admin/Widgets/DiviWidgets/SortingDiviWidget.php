<?php
namespace FilterEverything\Filter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('ET_Builder_Module')) {
    return;
}

use FilterEverything\Filter\Sorting;
use ET_Builder_Module;

class SortingDiviWidget extends ET_Builder_Module
{
    public $slug = 'filter_everything_sorting_items';
    public $vb_support = 'off';
    public $child_slug = 'filter_everything_sorting_item';

    protected $module_credits = array(
        'module_uri' => FLRT_PLUGIN_URL,
        'author_uri' => FLRT_PLUGIN_URL,
    );

    public function init()
    {
        $this->icon_path = FLRT_PLUGIN_DIR_PATH . 'assets/img/divi-widget-icon.svg';
        $this->name = esc_html__('Filter Everything - Sorting', 'filter-everything');
        $this->child_item_text = esc_html__('Sorting Item', 'filter-everything');

        $this->settings_modal_tabs['css'] = [];
        $this->settings_modal_tabs['advanced'] = [];

        $this->main_css_element = '%%order_class%%.flrt_et_pb_sorting_widget';


        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'filter_sorting' => array(
                        'priority' => 0,
                        'title'    => esc_html__('Filter sorting title', 'filter-everything'),
                    ),
                    'sorting_items' => array(
                        'priority' => 1,
                        'title'    => esc_html__('Sorting Item', 'filter-everything'),
                    )
                ),
            )
        );
    }

    public function get_fields()
    {
        $filterSorting = new Sorting();
        return array(
            'title' => array(
                'label'           => esc_html__('Title', 'filter-everything'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'toggle_slug'     => 'filter_sorting',
                'default'         => '',
            ),

        );
    }

    public function render($attrs, $content, $render_slug)
    {
        ob_start();
        if ( empty( $content ) ) {
            return '';
        }

        $children = [];

        preg_match_all(
            '/\[filter_everything_sorting_item\s+([^\]]+)\]/',
            $content,
            $matches
        );

        if ( ! empty( $matches[1] ) ) {
            foreach ( $matches[1] as $attr_string ) {

                $props = shortcode_parse_atts( $attr_string );

                $children[] = $props;
            }
        }


        foreach ($children as $key => $setting) {
            $this->props['titles'][$key]    = !empty($setting['wpc_title']) ? $setting['wpc_title'] : '';
            $this->props['orderbies'][$key] = !empty($setting['wpc_sorting']) ? $setting['wpc_sorting'] : 'default';
            $this->props['orders'][$key]    = !empty($setting['wpc_sort_order']) ? $setting['wpc_sort_order'] : 'asc';
            $this->props['meta_keys'][$key] = !empty($setting['wpc_meta_key']) ? $setting['wpc_meta_key'] : '';
        }
        ob_start();
        the_widget('\FilterEverything\Filter\SortingWidget', $this->props );
        $html = ob_get_clean();
        return $html;
    }
}

class SortingDiviWidgetChild extends ET_Builder_Module
{

    protected $module_credits = array(
        'module_uri' => FLRT_PLUGIN_URL,
        'author_uri' => FLRT_PLUGIN_URL,
    );

    public function init()
    {
        $this->slug = 'filter_everything_sorting_item';
        $this->vb_support = 'off';
        $this->type = 'child';
        $this->child_title_var = 'wpc_title';
        $this->child_title_fallback_var = 'wpc_sorting';

        $this->name = esc_html__('Sorting Item', 'filter-everything');
        $this->advanced_setting_title_text = esc_html__('Add sorting option', 'filter-everything');
        $this->settings_text = esc_html__('Sorting Item', 'filter-everything');

        $this->settings_modal_tabs['css'] = [];
        $this->settings_modal_tabs['advanced'] = [];

    }

    public function get_fields()
    {
        $filterSorting = new Sorting();

        return array(
                'wpc_title' => array(
                        'label'           => esc_html__('Title', 'filter-everything'),
                        'type'            => 'text',
                        'option_category' => 'configuration',
                        'toggle_slug'     => 'sorting_items',
                        'default'         => '',
                ),
                'wpc_sorting'  => array(
                        'label'           => esc_html__('Order By', 'filter-everything'),
                        'type'            => 'select',
                        'option_category' => 'configuration',
                        'toggle_slug'     => 'sorting_items',
                        'default'         => 'default',
                        'options'         => $filterSorting->getSortingOptions(),
                ),
                'wpc_meta_key'  => array(
                    'label'           => esc_html__('Meta key', 'filter-everything'),
                    'type'            => 'text',
                    'option_category' => 'configuration',
                    'toggle_slug'     => 'sorting_items',
                    'default'         => '',
                    'show_if'         => array(
                        'wpc_sorting' => array('m', 'n'),
                    ),
                ),
                'wpc_sort_order'  => array(
                        'label'           => esc_html__('Order', 'filter-everything'),
                        'type'            => 'select',
                        'option_category' => 'configuration',
                        'toggle_slug'     => 'sorting_items',
                        'default'         => 'asc',
                        'options'         => [
                                'asc'  => 'ASC',
                                'desc'  => 'DESC',
                        ],
                ),
        );
    }

    public function render($attrs, $content, $render_slug)
    {
    }
}

new SortingDiviWidget;
new SortingDiviWidgetChild;