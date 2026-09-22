<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ChipsBricksWidget extends \Bricks\Element {
    // Element properties
    public $category     = 'filter-everything';
    public $name         = 'filter-everything-chips';
    public $icon         = 'wpc-fe-icon';
    public $css_selector = '';
    public $scripts      = [];
    public $nestable     = false; // true || @since 1.5
    public function get_label() {
        return esc_html__( 'Chips', 'filter-everything' );
    }
    public function get_keywords() {
        return [ 'filter', 'chips', 'everything' ];
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
            'default' => '',
        ];

        $this->controls['set_id'] = [
            'tab' => 'content',
            'group' => 'settings',
            'label' => esc_html__( 'Show Chips only for Set with IDs:', 'filter-everything' ),
            'type' => 'text',
            'description' => esc_html__( 'e.g. 2745, 324', 'filter-everything' ),
            'pasteStyles' => false,
            'default' => '',
        ];

        $this->controls['mobile'] = [
            'tab' => 'content',
            'label' => esc_html__( 'Show on mobile', 'filter-everything' ),
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
            ob_start();
            the_widget('\FilterEverything\Filter\ChipsWidget', $this->settings );
            $html = ob_get_clean();
            echo $html;
        }
    }
}