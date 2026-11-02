<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class PostTypes
{
    public function __construct()
    {
        add_action( 'init', array( $this, 'registerPostType' ) );
    }

    function registerPostType() {
        /**
         * Both post types store their configuration (including serialized data) in
         * post_content / post_excerpt and are managed only through the plugin's own
         * admin screens, which are already gated by flrt_plugin_user_caps(). The
         * default 'post' capability_type would let any edit_posts-level user
         * (Contributor/Author) create or edit these posts and smuggle crafted
         * serialized data into post_content. Only the primitive caps are overridden
         * so only privileged users can create, edit or delete them. The meta caps
         * edit_post/read_post/delete_post are left to WordPress' own map_meta_cap():
         * reusing a shared cap like manage_options for those would make
         * _post_type_meta_capabilities() register it as a global meta cap and break
         * current_user_can('manage_options') everywhere.
         */
        $pluginCap = flrt_plugin_user_caps();
        $capabilities = array(
            'create_posts'           => $pluginCap,
            'edit_posts'             => $pluginCap,
            'edit_others_posts'      => $pluginCap,
            'edit_private_posts'     => $pluginCap,
            'edit_published_posts'   => $pluginCap,
            'publish_posts'          => $pluginCap,
            'read_private_posts'     => $pluginCap,
            'delete_posts'           => $pluginCap,
            'delete_private_posts'   => $pluginCap,
            'delete_published_posts' => $pluginCap,
            'delete_others_posts'    => $pluginCap,
        );

        // No need to escape
        register_post_type( FLRT_FILTERS_SET_POST_TYPE, array(
            'label'    => esc_html__( 'Filter Sets', 'filter-everything' ),
            'labels'			=> array(
                'name'					=> esc_html__( 'Filter Sets', 'filter-everything' ),
                'singular_name'			=> esc_html__( 'Filter Set', 'filter-everything' ),
                'add_new'				=> esc_html__( 'Add Filter Set' , 'filter-everything' ),
                'add_new_item'			=> esc_html__( 'Add Filter Set' , 'filter-everything' ),
                'edit_item'				=> esc_html__( 'Edit Filter Set' , 'filter-everything' ),
                'new_item'				=> esc_html__( 'New Filter Set' , 'filter-everything' ),
                'view_item'				=> esc_html__( 'View Filter Set', 'filter-everything' ),
                'search_items'			=> esc_html__( 'Search Filter Sets', 'filter-everything' ),
                'not_found'				=> esc_html__( 'Filter Sets are Filters grouped together. Create your first Filter Set.', 'filter-everything' ),
                'not_found_in_trash'	=> esc_html__( 'No Filter Sets were found in Trash', 'filter-everything' ),
            ),
            'has_archive'       => false,
            'public'			=> false,
            'show_ui'			=> true,
            '_builtin'			=> false,
            'capability_type'	=> 'post',
            'map_meta_cap'      => true,
            'capabilities'      => $capabilities,
            'hierarchical'		=> true,
            'rewrite'			=> false,
            'query_var'			=> false,
            'supports' 			=> array('title'),
            'show_in_menu'		=> false,
        ) );

        register_post_type(FLRT_FILTERS_POST_TYPE, array(
            'labels'			=> array(
                'name'					=> esc_html__( 'Filters', 'filter-everything' ),
                'singular_name'			=> esc_html__( 'Filter', 'filter-everything' ),
                'add_new'				=> esc_html__( 'Add New' , 'filter-everything' ),
                'add_new_item'			=> esc_html__( 'Add New Filter' , 'filter-everything' ),
                'edit_item'				=> esc_html__( 'Edit Filter' , 'filter-everything' ),
                'new_item'				=> esc_html__( 'New Filter' , 'filter-everything' ),
                'view_item'				=> esc_html__( 'View Filter', 'filter-everything' ),
                'search_items'			=> esc_html__( 'Search Filters', 'filter-everything' ),
                'not_found'				=> esc_html__( 'No Filters found', 'filter-everything' ),
                'not_found_in_trash'	=> esc_html__( 'No Filters were found in Trash', 'filter-everything' ),
            ),
            'public'			=> false,
            'show_ui'			=> false,
            '_builtin'			=> false,
            'capability_type'	=> 'post',
            'map_meta_cap'      => true,
            'capabilities'      => $capabilities,
            'hierarchical'		=> true,
            'rewrite'			=> false,
            'query_var'			=> false,
            'supports' 			=> array('title'),
            'show_in_menu'		=> false,
        ) );
    }
}

new PostTypes();