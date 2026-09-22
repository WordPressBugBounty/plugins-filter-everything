<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Admin side of the FiltersWidget: the widget form and its sanitisation.
 * Rendering (widget()) lives in src/Frontend/Widgets/FiltersWidget.php.
 *
 * Moved verbatim out of the widget class on 2026-09-14.
 */
trait FiltersWidgetForm
{
    public function form( $instance ) {

        $title      = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
        $show_count = isset( $instance['show_count'] ) ? (bool) $instance['show_count'] : true;
        $chips      = isset( $instance['chips'] ) ? (bool) $instance['chips'] : false;
        $horizontal = isset( $instance['horizontal'] ) ? (bool) $instance['horizontal'] : false;
        $cols_count = isset( $instance['cols_count'] ) ? $instance['cols_count'] : 3;

        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>"<?php checked( $show_count ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_html_e( 'Show the number of posts found', 'filter-everything' ); ?></label>
        </p>
        <p>
            <input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'chips' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'chips' ) ); ?>"<?php checked( $chips ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'chips' ) ); ?>"><?php esc_html_e( 'Show selected terms (Chips)', 'filter-everything' ); ?></label>
        </p>
        <p>
            <input type="checkbox" class="checkbox wpc-horizontal-checkbox" id="<?php echo esc_attr( $this->get_field_id( 'horizontal' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'horizontal' ) ); ?>"<?php checked( $horizontal ); ?> disabled="disabled"/>
            <label for="<?php echo esc_attr( $this->get_field_id( 'horizontal' ) ); ?>" style="color:#b7b7b7;"><?php esc_html_e( 'Horizontal filters', 'filter-everything' ); ?>.</label>
            <span class="wpc-columns-wrapper" style="display: none">
                <label for="<?php echo $this->get_field_id( 'cols_count' ); ?>"><?php _e( 'Columns', 'filter-everything' ); ?>:</label>
                <select id="<?php echo $this->get_field_id( 'cols_count' ); ?>" name="<?php echo $this->get_field_name( 'cols_count' ); ?>" style="display: none" disabled="disabled">
                    <?php for ( $i = 2; $i <= 5; $i++ ) : ?>
                        <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $cols_count, $i ); ?>>
                            <?php echo esc_html( $i ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </span>
            <br>
            <span>(<?php esc_html_e('This setting has moved to the Filter Set', 'filter-everything'); ?>)</span>
        </p>
        <?php
    }

    /**
     * Handles updating settings for the current Filters widget instance.
     * @since 1.0.0
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     * @return array Updated settings to save.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['title']      = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['chips']      = ( !empty( $new_instance['chips'] ) ) ? 1 : 0;
        $instance['show_count'] = ( !empty( $new_instance['show_count'] ) ) ? 1 : 0;
        $instance['horizontal'] = ( !empty( $new_instance['horizontal'] ) ) ? 1 : 0;
        $instance['cols_count'] = ( isset( $new_instance['cols_count'] ) && $new_instance['cols_count'] > 0 ) ? $new_instance['cols_count'] : 3;

        return $instance;
    }

    /**
     * Outputs Filters widget debug messages
     * @since 1.2.2
     */
}
