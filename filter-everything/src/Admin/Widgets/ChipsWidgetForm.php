<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Admin side of the ChipsWidget: the widget form and its sanitisation.
 * Rendering (widget()) lives in src/Frontend/Widgets/ChipsWidget.php.
 *
 * Moved verbatim out of the widget class on 2026-09-14.
 */
trait ChipsWidgetForm
{
    public function form( $instance ) {

        $title  = isset( $instance['title'] ) ? $instance['title'] : '';
        $set_id = isset( $instance['set_id'] ) ? $instance['set_id'] : '';
        $mobile = isset( $instance['mobile'] ) ? (bool) $instance['mobile'] : true;

        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'set_id' ) ); ?>"><?php esc_html_e( 'Show Chips only for Set with IDs:', 'filter-everything' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'set_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'set_id' ) ); ?>" type="text" value="<?php echo esc_attr( $set_id ); ?>" placeholder="<?php esc_html_e( 'e.g. 2745, 324', 'filter-everything' ); ?>"/>
        </p>
        <p>
            <input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'mobile' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mobile' ) ); ?>"<?php checked( $mobile ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'mobile' ) ); ?>"><?php esc_html_e( 'Show on mobile', 'filter-everything' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['title']  = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['set_id'] = ( !empty( $new_instance['set_id'] ) ) ? $new_instance['set_id'] : '';
        $instance['mobile'] = ( !empty( $new_instance['mobile'] ) ) ? 1 : 0;

        return $instance;
    }
}
