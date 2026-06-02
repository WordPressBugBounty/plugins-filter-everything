<?php
namespace FilterEverything\Filter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class FiltersElementorWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'filter-everything-filters';
    }

    public function get_title() {
        return esc_html__( 'Filters', 'filter-everything' );
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
        return [ 'filter', 'everything' ];
    }

    protected function register_controls(): void {

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
                'placeholder' => esc_html__( 'Enter filters title', 'filter-everything' ),
                'default' => esc_html__( 'Filter', 'filter-everything' ),
            ]
        );


        $this->add_control(
            'show_count',
            [
                'label' => __( 'Show the number of posts found', 'filter-everything' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Yes', 'filter-everything' ),
                'label_off' => __( 'No', 'filter-everything' ),
                'return_value' => 'yes',
                'default' => 'no'
            ]
        );

        $this->add_control(
            'chips',
            [
                'label' => __( 'Show selected terms (Chips)', 'filter-everything' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Yes', 'filter-everything' ),
                'label_off' => __( 'No', 'filter-everything' ),
                'return_value' => 'yes',
                'default' => 'no'
            ]
        );

        $this->end_controls_section();
    }

    public function get_style_depends(): array {
        return [ 'filter-everything-elementor' ];
    }
    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $settings['show_count'] = (!empty($settings['show_count']) && $settings['show_count'] === 'yes');
        $settings['chips']      = (!empty($settings['chips']) && $settings['chips'] === 'yes');

        ob_start();
        the_widget('\FilterEverything\Filter\FiltersWidget', $settings );
        $html = ob_get_clean();
        echo $html;
    }

    protected function content_template(): void {
        ?>
        <h3><?php echo $this->get_title() ?></h3>
        <?php
    }
}
