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
 * Color & term-visual helpers: contrast, hex/rgb, opacity, and swatch/brand
 * images and colors stored in term meta.
 *
 * Split out of src/wpc-helpers.php on 2026-09-14; function names and bodies are
 * unchanged. Loaded from filter-everything.php right after wpc-helpers.php.
 */

function flrt_get_contrast_ratio($hexColor)
{
    // hexColor RGB
    $R1 = hexdec(substr($hexColor, 1, 2));
    $G1 = hexdec(substr($hexColor, 3, 2));
    $B1 = hexdec(substr($hexColor, 5, 2));

    // Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 1, 2));
    $G2BlackColor = hexdec(substr($blackColor, 3, 2));
    $B2BlackColor = hexdec(substr($blackColor, 5, 2));

    // Calc contrast ratio
    $L1 = 0.2126 * pow($R1 / 255, 2.2) +
            0.7152 * pow($G1 / 255, 2.2) +
            0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
            0.7152 * pow($G2BlackColor / 255, 2.2) +
            0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
        $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
        $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }
    return round($contrastRatio);
}

function flrt_get_contrast_color($hexColor)
{

    $contrastRatio = flrt_get_contrast_ratio($hexColor);
    // If contrast is more than 5, return black color
    if ($contrastRatio > 10) {
        return '#333333';
    } else {
        // if not, return white color.
        return '#f5f5f5';
    }
}

function flrt_hex_to_rgb($hexColor, $opacity = 100)
{
    $hexColor = ltrim($hexColor, '#');

    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    $opacity = $opacity / 100;
    return "rgb($r $g $b / $opacity)";
}

function flrt_add_color_opacity($hexColor, $opacity = 50)
{
    $contrastRatio = flrt_get_contrast_ratio($hexColor);

    if ($contrastRatio <= 15) {
        return flrt_hex_to_rgb($hexColor, $opacity);
    } else {
        return $hexColor;
    }
}

function flrt_default_theme_color()
{
    return apply_filters('wpc_theme_color', '#0570e2');
}

function flrt_get_term_brand_image($term_id, $filter)
{
    $src = false;

    if ($filter['e_name'] === 'pwb-brand') {
        $attachment_id = get_term_meta($term_id, 'pwb_brand_image', true);
        $attachment_props = wp_get_attachment_image_src($attachment_id, 'small');
        $src = isset($attachment_props[0]) ? $attachment_props[0] : false;
    } elseif (in_array($filter['e_name'], ['yith_product_brand', 'product_brand'])) {
        $attachment_id = get_term_meta($term_id, 'thumbnail_id', true);
        $attachment_props = wp_get_attachment_image_src($attachment_id, 'small');
        $src = isset($attachment_props[0]) ? $attachment_props[0] : false;
    } else {
        // pa_brand and other custom brand taxonomies. The plugin's own term
        // Image field saves 'product_attribute_image' for pa_* taxonomies
        // (see Swatches::save_image_field) — read the same key it writes.
        $image_meta_key = ( strpos( $filter['e_name'], 'pa_' ) === 0 ) ? 'product_attribute_image' : 'image';
        $src = get_term_meta($term_id, $image_meta_key, true);

        if ( ! $src && $image_meta_key !== 'image' ) {
            $src = get_term_meta($term_id, 'image', true);
        }

        if (intval($src) > 0) {
            $src = wp_get_attachment_image_url($src, 'full');
        }

        if (isset($src['id']) && $src['id']) {
            $src = wp_get_attachment_image_url($src['id'], 'full');
        }
    }

    return $src;
}

function flrt_get_term_swatch_image($term_id, $filter)
{
    $src = false;
    $image_key = 'image';

    if (strpos($filter['e_name'], 'pa_') === 0) {
        $image_key = 'product_attribute_' . $image_key;
    }

    if ($filter['e_name'] === 'product_cat') {
        $image_key = 'thumbnail_id';
    }

    $image_key = apply_filters('wpc_image_term_meta_key', $image_key, $filter);

    $image_id = get_term_meta($term_id, $image_key, true);
    $swatch_image_size = apply_filters('wpc_swatch_image_size', 'thumbnail');

    if ($image_id) {
        $src = wp_get_attachment_image_url($image_id, $swatch_image_size);
    }

    return $src;
}

function flrt_get_term_swatch_color($term_id, $filter)
{
    $color = false;
    $color_key = 'color';

    if (strpos($filter['e_name'], 'pa_') === 0) {
        $color_key = 'product_attribute_' . $color_key;
    }

    $color_key = apply_filters('wpc_color_term_meta_key', $color_key, $filter);
    $color = get_term_meta($term_id, $color_key, true);

    return $color;
}
