<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}
class DefaultSettings
{
    public function __construct()
    {

    }

    public function wpc_filter_experimental()
    {
        return array(
            'use_loader'        => 'on',
            'use_wait_cursor'   => 'on',
            'dark_overlay'      => 'on',
            'auto_scroll'       => '',
            'styled_inputs'     => '',
            'select2_dropdowns' => '',
        );
    }
    public function wpc_filter_settings()
    {
        $default_show_terms_in_content  = [];
        $theme_dependencies             = flrt_get_theme_dependencies();

        if( flrt_is_woocommerce() ){
            $default_show_terms_in_content = ['woocommerce_no_products_found', 'woocommerce_archive_description'];
        }

        if ( isset( $theme_dependencies['chips_hook'] ) && is_array( $theme_dependencies['chips_hook'] ) ) {
            foreach ( $theme_dependencies['chips_hook'] as $compat_chips_hook ) {
                $default_show_terms_in_content[] = $compat_chips_hook;
            }
        }

        $defaultOptions = array(
            'primary_color'              => '#0570e2',
            'container_height'           => '550',
            'show_open_close_button'     => '',
            'show_terms_in_content'      => $default_show_terms_in_content,
            'widget_debug_messages'      => 'on'
        );

        // PRO default options
        if( defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO ){
            $defaultOptions['mobile_filter_settings'] = 'show_bottom_widget';
        }

        // Free default options (fresh installs only — activate() seeds this array
        // once and never touches an existing wpc_filter_settings). Filter pages are
        // noindex in free by design, so hiding the filter links from crawlers and
        // keeping crawlers off the filter URLs via robots.txt costs nothing in
        // search visibility and spares new sites the crawler-trap load out of the
        // box. Existing installs keep their behaviour until the options are
        // enabled — the 1.9.6 admin notice points them there.
        if( ! defined('FLRT_FILTERS_PRO') ){
            $defaultOptions['disable_filter_links_for_bots'] = 'on';
            $defaultOptions['robots_txt_block_filters']      = 'on';
        }

        return $defaultOptions;
    }
}