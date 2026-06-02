<?php
namespace FilterEverything\Filter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use FilterEverything\Filter\Shortcodes;
use FilterEverything\Filter\Sorting;



class SortingElementorWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'filter-everything-sorting';
    }

    public function get_title() {
        return esc_html__( 'Sorting', 'filter-everything' );
    }

    public function get_icon() {
        return 'wpc-fe-icon';
    }

    public function get_custom_help_url() {
        return 'https://filtereverything.pro/';
    }

    public function get_categories() {
        return [ 'filter-everything' ];
    }

    public function get_keywords() {
        return [ 'filter', 'sorting', 'everything' ];
    }

    protected function register_controls(): void {

        $filterSorting = new Sorting();

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__( 'Settings', 'filter-everything' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );


        $this->add_control(
            'title',
            [
                'type' => \Elementor\Controls_Manager::TEXT,
                'label' => esc_html__( 'Title', 'filter-everything' ),
                'label_block' => true,
                'placeholder' => esc_html__( 'Enter sorting label', 'filter-everything' ),
                'default' => esc_html__( 'Sorting', 'filter-everything' ),
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'titles',
            [
                'label' => esc_html__( 'Title', 'filter-everything' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__( 'Item' , 'filter-everything' ),
                'label_block' => true,
            ]
        );



        $repeater->add_control(
            'orderbies',
            [
                'label' => esc_html__( 'Order', 'filter-everything' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'default',
                'label_block' => true,
                'options' => $filterSorting->getSortingOptions()
            ]
        );

        $repeater->add_control(
                'meta_keys',
                [
                        'label' => esc_html__( 'Meta key', 'filter-everything' ),
                        'type' => \Elementor\Controls_Manager::TEXT,
                        'default' => esc_html__( '' , 'filter-everything' ),
                        'label_block' => true,
                        'condition' => [
                                'orderbies' => ['m', 'n'],
                        ],
                ]
        );

        $repeater->add_control(
            'orders',
            [
                'label' => esc_html__( 'Order', 'filter-everything' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'asc',
                'label_block' => true,
                'options' => [
                    'asc' => esc_html__( 'ASC', 'filter-everything' ),
                    'desc'  => esc_html__( 'DESC', 'filter-everything' ),
                ]
            ]
        );




        $default = [];

        if(!empty($filterSorting->getSortingDefaults())){
            foreach ($filterSorting->getSortingDefaults()['titles'] as $key => $value) {
                $default[] = [
                    'titles' => $value,
                    'orderbies' => $filterSorting->getSortingDefaults()['orderbies'][$key],
                    'orders' => $filterSorting->getSortingDefaults()['orders'][$key],
                ];
            }
        }

        $this->add_control(
            'widget_wpc_sorting_widget',
            [
                'label' => esc_html__( 'Sorting options:', 'filter-everything' ),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $default,
                'title_field' => '{{{ titles }}}',
            ]
        );

        $this->end_controls_section();
    }

    public function get_style_depends(): array {
        return [ 'filter-everything-elementor' ];
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $arguments['title'] = $settings['title'];
        foreach ($settings['widget_wpc_sorting_widget'] as $key => $setting) {
            $arguments['titles'][$key]    = $setting['titles'];
            $arguments['orderbies'][$key] = $setting['orderbies'];
            $arguments['orders'][$key]    = $setting['orders'];
            $arguments['meta_keys'][$key] = $setting['meta_keys'];
        }
        ob_start();
        the_widget('\FilterEverything\Filter\SortingWidget', $arguments );
        $html = ob_get_clean();
        echo $html;
    }
    protected function content_template(): void {
        ?>
        <h3><?php echo $this->get_title() ?></h3>
        <?php
    }
}
