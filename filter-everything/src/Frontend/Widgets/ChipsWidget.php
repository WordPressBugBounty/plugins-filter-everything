<?php


namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}
class ChipsWidget extends \WP_Widget
{
    use ChipsWidgetForm;

    public function __construct() {
        parent::__construct(
            'wpc_chips_widget', // Base ID
            esc_html__( 'Filter Everything &mdash; Chips', 'filter-everything'),
            array( 'description' => esc_html__( 'Displays selected terms', 'filter-everything' ), )
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        $title  = isset( $instance['title'] ) ? $instance['title'] : '';
        $title  = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $set_id = isset( $instance['set_id'] ) ? preg_replace('/[^\d\,]?/', '', $instance['set_id'] ) : '';
        $mobile = ( !empty( $instance['mobile'] ) ) ? $instance['mobile'] : '';
        $setIds = $classes = [];

        if( isset( $_POST['action'] ) && $_POST['action'] === 'elementor_ajax' ){
            echo '<strong>'.esc_html__( 'Filter Everything &mdash; Chips', 'filter-everything' ).'</strong>';
            return;
        }

        if( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ){
            echo '<strong>'.esc_html__( 'Filter Everything &mdash; Chips', 'filter-everything' ).'</strong>';
            return;
        }

        if( $mobile ){
            $classes[] = 'wpc-show-on-mobile';
            if( strpos($args['before_title'], 'widget-title') !== false ){
                $args['before_title'] = str_replace('widget-title', 'widget-title wpc-show-on-mobile-widget-title', $args['before_title']);
            } elseif( strpos($args['before_title'], 'widgettitle') !== false ){
                $args['before_title'] = str_replace('widgettitle', 'widgettitle wpc-show-on-mobile-widget-title', $args['before_title']);
            }
        }

        if( $set_id ){
            $setIds = explode( ",", $set_id );
        }

        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        flrt_show_selected_terms(true, $setIds, $classes);

        echo $args['after_widget'];
    }

}