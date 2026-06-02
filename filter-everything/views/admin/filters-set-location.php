<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

?><div class="wpc-filters-set-settings-wrapper">
    <table class="wpc-form-fields-table">
        <?php
            $set_settings_fields = flrt_get_set_settings_location_fields( $post->ID );

            // Allows you to manipulate fields before show them
            do_action_ref_array( 'wpc_before_filter_set_settings_location_fields', array( &$set_settings_fields ) );

            foreach ( $set_settings_fields as $key => $attributes ) {
                flrt_include_admin_view('filter-field', array(
                                'field_key'  => $key,
                                'attributes' => $attributes
                        )
                );
            }
        ?>
        <tr>
            <td></td>
            <td><?php
                printf(
                        wp_kses_post(esc_html__('%sTo display filters, add the %sFilter Everything — Filters%s widget to a widget area or sidebar,%sor use the %s[fe_widget]%s shortcode to place them anywhere on your site.%s', 'filter-everything')),
                        '<div class="description wpc-text-center"><p>',
                        '<strong>',
                        '</strong>',
                        '<br />',
                        '<code>',
                        '</code>',
                        '</p></div>',
                );
                ?></td>
        </tr>
    </table>
</div>