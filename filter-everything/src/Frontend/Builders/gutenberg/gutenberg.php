<?php
if (!defined('ABSPATH')) {
	exit;
}

use FilterEverything\Filter\Sorting;

function flrt_gutenberg_block_init() {
	$build_dir     = __DIR__ . '/build';
	$manifest_file = $build_dir . '/blocks-manifest.php';

	if ( ! is_dir($build_dir) || ! file_exists($manifest_file) ) {
		return;
	}

	$manifest_data = include $manifest_file;

	if ( ! is_array($manifest_data) ) {
		return;
	}


	// WP 6.8+
	if (function_exists('wp_register_block_types_from_metadata_collection')) {
		wp_register_block_types_from_metadata_collection($build_dir, $manifest_file);
		return;
	}

	// WP 6.7
	if (function_exists('wp_register_block_metadata_collection')) {
		wp_register_block_metadata_collection($build_dir, $manifest_file);
		return;
	}

	foreach (array_keys($manifest_data) as $block_type) {
		register_block_type("$build_dir/$block_type");
	}
}

add_action('init', 'flrt_gutenberg_block_init');

/**
 * Enqueue block editor assets and localize sorting data
 */
function flrt_gutenberg_block_editor_assets() {

	$filterSorting = new Sorting();

	$soring_options = $filterSorting->getSortingOptions();
	$sorting_options = [];
	if (!empty($soring_options)) {
		foreach ($soring_options as $key => $val) {
			$sorting_options[] = array(
				'label' => $val,
				'value' => $key
			);
		}
	}

	$sorting_default_options = [];
	$default_fields = $filterSorting->getSortingDefaults();
	if(!empty($default_fields)){
		foreach ($default_fields['titles'] as $key => $value) {
			$sorting_default_options[] = [
				'title' => $value,
				'order_by' => $default_fields['orderbies'][$key],
				'order' => $default_fields['orders'][$key],
			];
		}
	}

	wp_localize_script(
		'wpc-filter-everything-sorting-editor-script',
		'wpcSortingData',
		array(
			'sortingOptions' => $sorting_options,
			'sortingDefaultOptions' => $sorting_default_options,
			'ajaxUrl'        => admin_url('admin-ajax.php'),
			'nonce'          => wp_create_nonce('wpc_sorting_nonce')
		)
	);
}
add_action('enqueue_block_editor_assets', 'flrt_gutenberg_block_editor_assets');

function flrt_filter_everything_block_categories($categories)
{
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'wpc-filter-everything',
				'title' => __('Filter Everything', 'filter-everything'),
				'icon'  => '',
			),
		)
	);
}

add_filter('block_categories_all', 'flrt_filter_everything_block_categories', 10, 1);

add_filter('register_block_type_args', function($args, $block_type) {
	if (!defined('FLRT_FILTERS_PRO') && strpos($block_type, 'filter-button') !== false) {
		return [];
	}
	return $args;
}, 15, 2);

