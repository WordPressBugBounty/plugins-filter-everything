<?php

if (!defined('ABSPATH')) {
    exit;
}

use \FilterEverything\Filter\Container;
use \FilterEverything\Filter\FilterSet;
use \FilterEverything\Filter\FilterFields;
use \FilterEverything\Filter\PostMetaNumEntity;
use \FilterEverything\Filter\PostDateEntity;
use \FilterEverything\Filter\PostMetaDateEntity;

/**
 * Settings migrations run on load for sites updated from older versions.
 *
 * Split out of src/wpc-helpers.php on 2026-09-14; function names and bodies are
 * unchanged. Loaded from filter-everything.php right after wpc-helpers.php.
 */

function flrt_check_update_mobile_settings()
{

    $settings = get_option('wpc_filter_settings', false);
    if ($settings !== false) {
        if (!flrt_get_option('mobile_filter_settings')) {

            $mobile_filter_settings = 'nothing';

            if ((flrt_get_option('show_bottom_widget') === 'on' && flrt_get_option('show_open_close_button') === 'on')
                    || (flrt_get_option('show_bottom_widget') === 'on' && !flrt_get_option('show_open_close_button'))) {
                $mobile_filter_settings = 'show_bottom_widget';
            } elseif (flrt_get_option('show_open_close_button') == 'on' && !flrt_get_option('show_bottom_widget')) {
                $mobile_filter_settings = 'show_open_close_button';
            }

            $settings = get_option('wpc_filter_settings');

            if (isset($settings['show_bottom_widget']) && $settings['show_bottom_widget']) {
                unset($settings['show_bottom_widget']);
            }

            if (isset($settings['show_open_close_button']) && $settings['show_open_close_button']) {
                unset($settings['show_open_close_button']);
            }

            $settings['mobile_filter_settings'] = $mobile_filter_settings;
            update_option('wpc_filter_settings', $settings);
        }
    }
}
