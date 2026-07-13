<?php
// This file is generated. Do not modify it manually.
return array(
	'chips' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wpc-filter-everything/chips',
		'category' => 'wpc-filter-everything',
		'icon' => 'smiley',
		'description' => '',
		'keywords' => array(
			'filter',
			'chips'
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'set_id' => array(
				'type' => 'string',
				'default' => ''
			),
			'mobile' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'filter-everything',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'filter-button' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wpc-filter-everything/filter-button',
		'category' => 'wpc-filter-everything',
		'icon' => 'smiley',
		'description' => '',
		'keywords' => array(
			'filter',
			'button'
		),
		'attributes' => array(
			'id' => array(
				'type' => 'number',
				'default' => ''
			)
		),
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'filter-everything',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'filters' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wpc-filter-everything/filter',
		'category' => 'wpc-filter-everything',
		'icon' => '',
		'description' => '',
		'keywords' => array(
			'filter'
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'show_count' => array(
				'type' => 'boolean',
				'default' => false
			),
			'chips' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'filter-everything',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'sorting' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wpc-filter-everything/sorting',
		'category' => 'wpc-filter-everything',
		'icon' => 'smiley',
		'description' => '',
		'keywords' => array(
			'filter',
			'sorting'
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'sorting_options' => array(
				'type' => 'array',
				'default' => array(
					
				),
				'items' => array(
					'type' => 'object',
					'properties' => array(
						'id' => array(
							'type' => 'number'
						),
						'title' => array(
							'type' => 'string'
						),
						'order_by' => array(
							'type' => 'string'
						),
						'meta_keys' => array(
							'type' => 'string'
						),
						'order' => array(
							'type' => 'string'
						)
					)
				)
			)
		),
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'filter-everything',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
