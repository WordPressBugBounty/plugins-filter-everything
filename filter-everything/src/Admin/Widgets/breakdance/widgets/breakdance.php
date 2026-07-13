<?php

namespace FilterEverything\Filter;

if (!defined('ABSPATH')) {
    exit;
}

use function Breakdance\Util\getDirectoryPathRelativeToPluginFolder;
use function Breakdance\Elements\registerCategory;
/**
 * Load plugin text domain for translations
 */
function filter_everything_load_breakdance_textdomain() {
    load_plugin_textdomain(
        'filter-everything',
        false,
        dirname(plugin_basename(__FILE__)) . '/lang'
    );
}

/**
 * Add custom category to Breakdance elements panel
 */

add_action('plugins_loaded', 'FilterEverything\Filter\filter_everything_load_breakdance_textdomain');

add_action('breakdance_loaded', function () {



    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/elements',
        'FilterEverything',
        'element',
        'Filter Everything',
        false
    );



    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/macros',
        'FilterEverything',
        'macro',
        'Filter Everything Macros',
        false,
    );

    \Breakdance\ElementStudio\registerSaveLocation(
        getDirectoryPathRelativeToPluginFolder(__DIR__) . '/presets',
        'FilterEverything',
        'preset',
        'Filter Everything Presets',
        false,
    );
}, 9 );

add_action('init', function () {
    \Breakdance\Elements\registerCategory(
        'filter-everything',
        esc_html__( 'Filter Everything', 'filter-everything' )
    );
});

