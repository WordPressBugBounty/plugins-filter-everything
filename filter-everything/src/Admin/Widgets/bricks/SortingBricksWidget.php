<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use FilterEverything\Filter\Sorting;
class SortingBricksWidget extends \Bricks\Element {
    // Element properties
    public $category     = 'filter-everything';
    public $name         = 'filter-everything-sorting';
    public $icon         = 'wpc-fe-icon';
    public $css_selector = '';
    public $scripts      = [];
    public $nestable     = false;
    public function get_label() {
        return esc_html__( 'Sorting', 'filter-everything' );
    }
    public function get_keywords() {
        return [ 'filter', 'sorting', 'everything' ];
    }
    public function set_control_groups() {

        $this->control_groups['settings'] = [
            'title' => esc_html__( 'Settings', 'filter-everything' ),
            'tab' => 'content',
        ];
    }
    public function set_controls() {

        $filterSorting = new Sorting();

        $this->controls['title'] = [
            'tab' => 'content',
            'group' => 'settings',
            'label' => esc_html__( 'Title', 'filter-everything' ),
            'type' => 'text',
            'pasteStyles' => false,
            'default' => '',
        ];

        $this->controls['sorting_options'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Sorting options:', 'filter-everything' ),
            'type' => 'repeater',
            'group' => 'settings',
            'titleProperty' => 'titles',
            'placeholder' => esc_html__( 'Sorting option', 'filter-everything' ),
            'default' => $filterSorting->prepareForPageBuilder(),
            'fields' => [
                'titles' => [
                    'label' => esc_html__( 'Title', 'filter-everything' ),
                    'type' => 'text',
                ],
                'orderbies' => [
                    'label' => esc_html__( 'Order by', 'filter-everything' ),
                    'type' => 'select',
                    'options' => $filterSorting->getSortingOptions(),
                    'inline' => false,
                    'clearable' => false,
                    'pasteStyles' => false,
                    'default' => 'default',
                ],
                'meta_keys' => [
                    'label' => esc_html__( 'Meta key', 'filter-everything' ),
                    'type' => 'text',
                    'required' => ['orderbies', '=', ['m', 'n']]
                ],
                'orders' => [
                    'label' => esc_html__( 'Order', 'filter-everything' ),
                    'type' => 'select',
                    'options' => [
                        'asc' => esc_html__( 'ASC', 'filter-everything' ),
                        'desc'  => esc_html__( 'DESC', 'filter-everything' ),
                    ],
                    'inline' => false,
                    'clearable' => false,
                    'pasteStyles' => false,
                    'default' => 'asc',
                ],
            ],
        ];
    }
    public function enqueue_scripts() {}
    public function render() {
        $is_builder_call  = bricks_is_builder() || bricks_is_builder_call();
        if ( $is_builder_call ) {
            echo '<h3>' . $this->get_label() . '</h3>';
            return;
        }

        if(!empty($this->settings)){
            $arguments['title'] = $this->settings['title'];
            foreach ($this->settings['sorting_options'] as $key => $setting) {
                $arguments['titles'][$key]    = $setting['titles'];
                $arguments['orderbies'][$key] = $setting['orderbies'];
                $arguments['orders'][$key]    = $setting['orders'];
                $arguments['meta_keys'][$key] = (!empty($setting['meta_keys'])) ? $setting['meta_keys'] : '';;
            }
            ob_start();
            the_widget('\FilterEverything\Filter\SortingWidget', $arguments );
            $html = ob_get_clean();
            echo $html;
        }
    }
}