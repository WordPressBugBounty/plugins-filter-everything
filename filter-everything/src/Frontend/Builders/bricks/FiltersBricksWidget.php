<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FiltersBricksWidget extends \Bricks\Element {
    // Element properties
    public $category     = 'filter-everything';
    public $name         = 'filter-everything-filter';
    public $icon         = 'wpc-fe-icon';
    public $css_selector = '';
    public $scripts      = [];
    public $nestable     = false; // true || @since 1.5
    public function get_label() {
        return esc_html__( 'Filters', 'filter-everything' );
    }
    public function get_keywords() {
        return [ 'filter', 'everything' ];
    }
    public function set_control_groups() {

        $this->control_groups['settings'] = [
            'title' => esc_html__( 'Settings', 'filter-everything' ),
            'tab' => 'content',
        ];
    }
    public function set_controls() {
        $this->controls['title'] = [
            'tab' => 'content',
            'group' => 'settings',
            'label' => esc_html__( 'Title', 'filter-everything' ),
            'type' => 'text',
            'pasteStyles' => false,
            'default' => esc_html__( 'Filter', 'filter-everything' ),
        ];


        $this->controls['show_count'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Show the number of posts found', 'filter-everything' ),
            'group' => 'settings',
            'type' => 'checkbox',
            'inline' => true,
            'pasteStyles' => false,
            'small' => true,
            'default' => false,
        ];

        $this->controls['chips'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Show selected terms (Chips)', 'filter-everything' ),
            'group' => 'settings',
            'type' => 'checkbox',
            'inline' => true,
            'pasteStyles' => false,
            'small' => true,
            'default' => false,
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
            $settings = $this->settings;
            ob_start();
            the_widget('\FilterEverything\Filter\FiltersWidget', $settings );
            $html = ob_get_clean();
            echo $html;
        }
    }
}