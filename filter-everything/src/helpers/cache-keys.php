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
 * Cache helpers: transient key builders for terms / post IDs / variations and
 * cache folder cleanup.
 *
 * Split out of src/wpc-helpers.php on 2026-09-14; function names and bodies are
 * unchanged. Loaded from filter-everything.php right after wpc-helpers.php.
 */

function flrt_get_terms_transient_key( $salt, $include_lang = true ){
    $key = 'wpc_terms_' . $salt . FLRT_CACHE_FORMAT_SUFFIX;
    if ( flrt_wpml_active() && defined( 'ICL_LANGUAGE_CODE' ) && $include_lang ) {
        $key .= '_'.ICL_LANGUAGE_CODE;
    }

    if (function_exists('pll_current_language') && $include_lang) {
        $pll_lang = pll_current_language();
        if ($pll_lang) {
            $key .= '_' . $pll_lang;
        }
    }

    return $key;
}

function flrt_get_post_ids_transient_key( $salt ){
    $key = 'wpc_posts_' . $salt . FLRT_CACHE_FORMAT_SUFFIX;
    if (flrt_wpml_active() && defined('ICL_LANGUAGE_CODE')) {
        $key .= '_' . ICL_LANGUAGE_CODE;
    }

    if (function_exists('pll_current_language')) {
        $pll_lang = pll_current_language();
        if ($pll_lang) {
            $key .= '_' . $pll_lang;
        }
    }

    return $key;
}

function flrt_get_variations_transient_key( $salt ){
    $key = 'wpc_variations_' . $salt . FLRT_CACHE_FORMAT_SUFFIX;
    if (flrt_wpml_active() && defined('ICL_LANGUAGE_CODE')) {
        $key .= '_' . ICL_LANGUAGE_CODE;
    }

    if (function_exists('pll_current_language')) {
        $pll_lang = pll_current_language();
        if ($pll_lang) {
            $key .= '_' . $pll_lang;
        }
    }

    return $key;
}


function wpc_clear_folder($directory)
{

    if (!is_dir($directory)) {
        return false;
    }

    $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*');

    if(!empty($files)){
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
