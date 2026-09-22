<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class SortingWidget extends \WP_Widget
{
    use SortingWidgetForm;

    public function __construct() {
        parent::__construct(
            'wpc_sorting_widget', // Base ID
            esc_html__( 'Filter Everything &mdash; Sorting', 'filter-everything'),
            array( 'description' => esc_html__( 'Displays a dropdown with sort options', 'filter-everything' ), )
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

        // Display nothing if preview mode
        if( isset( $_GET['legacy-widget-preview'] ) || isset( $_GET['_locale'] ) ){
            return;
        }

        if( isset( $_POST['action'] ) && $_POST['action'] === 'elementor_ajax' ){
            echo '<strong>'.esc_html__( 'Filter Everything &mdash; Sorting', 'filter-everything' ).'</strong>';
            return;
        }

        if( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ){
            echo '<strong>'.esc_html__( 'Filter Everything &mdash; Sorting', 'filter-everything' ).'</strong>';
            return;
        }

        $debug_mode = flrt_is_debug_mode();
        $container  = Container::instance();
        $wpManager  = $container->getWpManager();
        $sets       = $wpManager->getQueryVar( 'wpc_page_related_set_ids', [] );

        if( empty( $sets ) ) {
            if ( $debug_mode ) {
                $this->_debug_messages();
//                flrt_debug_title();
            }
            return false;
        }

        $container       = Container::instance();
        $templateManager = $container->getTemplateManager();
        $url_manager     = Container::instance()->getUrlManager();
        $sorting         = new Sorting();
        // @todo values (keys) shouldn't be meta, meta_num or can?
        $orderby = isset( $_GET['ordr'] ) ? flrt_clean( wp_unslash( $_GET['ordr'] ) ) : 'default';

        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Include front template
        $templateManager->includeFrontView(
            'orderby',
            array(
                'action'    => $url_manager->getFormActionOrFullPageUrl(),
                'selected_orderby'   => $orderby,
                'titles'    => $instance['titles'],
                'orderbies' => $instance['orderbies'],
                'orders'    => $instance['orders'],
                'meta_keys' => $instance['meta_keys']
            )
        );

        echo $args['after_widget'];
    }

    private function _debug_messages() {
        echo '<p class="wpc-debug-message">';
        echo esc_html__( 'Sorting is unavailable on this page. It works only with content filtered by Filter Everything. To enable sorting, assign a Filter Set to this page.', 'filter-everything' );
        echo '</p>';
    }
}