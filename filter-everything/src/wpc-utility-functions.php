<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

use FilterEverything\Filter\Container;


function flrt_get_set_location_groups( $no_selection = false ){
    if( ! is_admin() ){
        return array();
    }

    $fields = [];

    if( $no_selection ){
        $fields['empty'] = array(
            'group_label' => esc_html__('No page selected', 'filter-everything'),
            'entities' => array(
                // This should be renamed as it looks like WP Page post type
                'no_page___no_page' => esc_html__('The same page as for filtered posts', 'filter-everything'),
            )
        );
    }

    // Common WP pages
    $fields['common'] = array(
        'group_label' => esc_html__('Common', 'filter-everything'),
        'entities' => array(
            // This should be renamed as it looks like WP Page post type
            'common___common' => esc_html__('Common WordPress pages', 'filter-everything'),
        )
    );

    // Get Taxonomies
    $excludedTaxes  = flrt_excluded_taxonomies();
    $args           = array( 'public' => true, 'rewrite' => true );
    $taxonomies     = get_taxonomies( $args, 'objects' );
    $tax_entitites  = [];

    foreach ( $taxonomies as $t => $taxonomy ) {
        if ( ! in_array( $taxonomy->name, $excludedTaxes ) ) {
            $label = ucwords( flrt_ucfirst( mb_strtolower( $taxonomy->label ) ) );
            $tax_entitites[ 'taxonomy___' .$taxonomy->name] = $label;
        }
    }

    if( ! empty( $tax_entitites ) ){
        $fields['taxonomies'] = array(
            'group_label' => esc_html__('Taxonomies', 'filter-everything'),
            'entities' => $tax_entitites
        );
    }

    // Get Post types
    $filterSet  = Container::instance()->getFilterSetService();
    $post_types = $filterSet->getPostTypes();

    if( ! empty( $post_types ) ){
        $new_post_types = [];
        foreach ($post_types as $post_type_key => $post_type_label ){
            $new_post_types[ 'post_type___' .$post_type_key ] = $post_type_label;
        }

        $fields['post_types'] = array(
            'group_label' => esc_html__('Post types', 'filter-everything'),
            'entities' => $new_post_types
        );
    }

    $fields['author'] = array(
        'group_label' => esc_html__( 'Author', 'filter-everything' ),
        'entities'    => array(
            'author___author' => esc_html__( 'Author', 'filter-everything' )
        )
    );

    unset( $filterSet );

    return apply_filters( 'wpc_set_location_groups', $fields, $no_selection );
}

function flrt_get_set_location_terms( $wpPageType = 'common___common', $postType = 'post', $full_label = true )
{
    $fields = [];
    if( ! is_admin() ){
        return $fields;
    }

    $wpPageType = $wpPageType ? $wpPageType : 'common___common';

    $pageTypeVars = explode('___', $wpPageType);
    $typeKey      = $pageTypeVars[0];
    $typeValue    = isset( $pageTypeVars[1] ) ? $pageTypeVars[1] : false;

    // @todo No posts, No tags what to show in Dropdown?
    switch ( $typeKey ){
        case 'no_page':
            $fields = flrt_get_no_page_terms();
            break;
        case 'common':
            $fields = flrt_get_common_location_terms( $postType );
            break;
        case 'post_type':
            $fields = flrt_get_post_type_location_terms( $typeValue, $full_label );
            break;
        case 'taxonomy':
            $fields = flrt_get_taxonomy_location_terms( $typeValue, $full_label );
            break;
        case 'author':
            $fields = flrt_get_author_location_terms();
            break;
    }

    return apply_filters( 'wpc_set_location_terms', $fields, $wpPageType, $postType, $full_label );
}

function flrt_get_common_location_terms( $postType = 'post' )
{
    $fields = [];
    $link   = get_post_type_archive_link( $postType );

    $lang   = '';

    // In case of Polylang
    if( function_exists('pll_home_url') ){
        global $post_id;
        $post_id        = ( isset( $_POST['postId'] ) && $_POST['postId'] ) ? $_POST['postId'] : $post_id;
        $lang           = pll_get_post_language( $post_id );
        $pll_language   = PLL()->model->get_language( $lang );

        if( $postType === 'post' ){
            $page_for_posts_id  = get_option( 'page_for_posts' );
            if( $page_for_posts_id ){
                $link = get_permalink( flrt_maybe_has_translation( $page_for_posts_id, $lang ) );
            }else{
                $link = pll_home_url($lang);
            }
        }else{
            $translated_post_types = PLL()->model->get_translated_post_types();
            if( isset( $translated_post_types[ $postType ] ) ){
                $link = PLL()->links_model->switch_language_in_link( $link, $pll_language );
            }
        }
    }

    // All archive pages for this Post Type
    if( $link ){
        $fields = array( '1' => array(
            'label' => esc_html__('All archive pages for this Post Type', 'filter-everything'),
            'data-link' => $link
        ),
        );
    }

    // Blog page
    $page_for_posts_id  = get_option( 'page_for_posts' );
    if( $page_for_posts_id ){
        $blog_page_link = get_permalink( flrt_maybe_has_translation( $page_for_posts_id, $lang ) );
        $fields['common___page_for_posts'] = array(
            'label' => esc_html__('Blog page', 'filter-everything'),
            'data-link' => $blog_page_link
        );
    }

    // Homepage
    $page_on_front_id = get_option( 'page_on_front' );
    if( $page_on_front_id ){
        $page_on_front_link = get_permalink( $page_on_front_id );
        if( function_exists('pll_home_url') ){
            $page_on_front_link = pll_home_url($lang);
        }

        $fields['common___page_on_front'] = array(
            'label' => esc_html__('Homepage' ),
            'data-link' => $page_on_front_link
        );
        if(!$link){
            $fields['common___page_on_front']['selected'] = true;
        }
    }

    // In case of Polylang plugin
    $home_url = trailingslashit( get_bloginfo('url') );
    if( function_exists('pll_home_url') ){
        $translated_post_types = PLL()->model->get_translated_post_types();
        if( isset( $translated_post_types[$postType] ) ){
            $home_url = trailingslashit( pll_home_url($lang) );
        }
    }

    $s = isset( $_GET['s'] ) ? filter_input( INPUT_GET, 's', FILTER_SANITIZE_SPECIAL_CHARS ) : 'a';
    $fields['common___search_results'] = array(
        'label' => esc_html__('Search results page for selected Post Type', 'filter-everything'),
        'data-link' => add_query_arg( array('s' => $s, 'post_type' => $postType ), $home_url )
    );

    if( function_exists('is_woocommerce') ){

        $shop_page_id   = wc_get_page_id( 'shop' );
        $shop_permalink = get_permalink( $shop_page_id );

        if( function_exists('pll_home_url') ){
            $translated_post_types = PLL()->model->get_translated_post_types();
            if( isset( $translated_post_types['product'] ) ){
                $shop_permalink = PLL()->links_model->switch_language_in_link( $shop_permalink, $pll_language );
            }
        }

        if ( $shop_page_id > 0 ) {
            $fields['common___shop_page'] = array(
                'label' => esc_html__('Shop page', 'filter-everything' ),
                'data-link' => $shop_permalink
            );
        }
    }

    return $fields;
}

function flrt_get_post_type_location_terms( $postType = 'post', $full_label = false )
{
    $postType   = $postType ? $postType : 'post';
    $fields     = [];

    $args = array(
        'post_type'      => $postType,
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'private' ),
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids'
    );

    $allPosts = new \WP_Query();
    $allPosts->parse_query($args);
    $ids      = $allPosts->get_posts();

    $ids      = apply_filters( 'wpc_post_type_location_terms', $ids, $postType );

    $postTypeObject = get_post_type_object( $postType );
    $label = isset( $postTypeObject->labels->singular_name ) ? $postTypeObject->labels->singular_name : flrt_ucfirst( $postType );

    if ( ! empty( $ids ) ) {
        $firstPostId    = reset($ids );
        $firstPostlink  = get_permalink( $firstPostId );

        if( $full_label ){
            $any_label = sprintf( esc_html__('Any %s page (for a common query across all %s pages)', 'filter-everything'), $label, $label );
        } else {
            $any_label = sprintf( esc_html__('Any %s page', 'filter-everything'), $label );
        }

        $fields[$postType.'___-1'] = array(
            'label'     => $any_label,
            'data-link' => $firstPostlink
        );

        unset( $firstPostId, $firstPostlink );

        foreach ( $ids as $postId ){
            $fields[$postType.'___'.$postId] = array(
                'label'     => get_the_title( $postId ),
                'data-link' => get_permalink( $postId )
            );
        }
    }else{
        $fields[$postType.'___0'] = array(
            'label'     => sprintf(esc_html__('— There is no any %s yet —', 'filter-everything'), $label ),
            'data-link' => ''
        );
    }

    return $fields;
}

function flrt_get_taxonomy_location_terms( $taxonomy, $full_label = true )
{
    $fields = [];

    if( ! $taxonomy ){
        return $fields;
    }

    $args = array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'fields'     => 'id=>name'
    );

    $terms          = get_terms( $args );
    $taxonomyObject = get_taxonomy( $taxonomy );

    $label          = isset( $taxonomyObject->labels->singular_name ) ? $taxonomyObject->labels->singular_name : flrt_ucfirst( $taxonomy );

    $terms = apply_filters( 'wpc_taxonomy_location_terms', $terms, $taxonomy );

    if( ! is_wp_error( $terms ) && ! empty( $terms ) ){

        $firstTermId    = array_key_first($terms);
        $firstTermlink  = get_term_link( $firstTermId, $taxonomy );
        $firstTermlink  = ( is_wp_error( $firstTermlink ) ) ? '' : $firstTermlink;

        if( $full_label ){
            $any_label = sprintf(esc_html__('Any %s (for a common query across all %s pages)', 'filter-everything'), $label, $label );
        }else{
            $any_label = sprintf(esc_html__('Any %s', 'filter-everything'), $label );
        }

        $fields[$taxonomy.'___-1'] = array(
            'label'     => $any_label,
            'data-link' => $firstTermlink
        );
        unset( $firstTermId, $firstTermlink);

        foreach ( $terms as $termId => $termName ){

            $link = get_term_link( $termId, $taxonomy );
            $link = ( is_wp_error( $link ) ) ? '' : $link;

            $fields[$taxonomy.'___'.$termId] = array(
                'label'     => $termName,
                'data-link' => $link
            );
        }
    }else{
        $fields[$taxonomy.'___0'] = array(
            'label'     => sprintf(esc_html__('— There is no any %s yet —', 'filter-everything'), $label ),
            'data-link' => ''
        );
    }

    return $fields;
}

function flrt_get_author_location_terms()
{
    $fields  = [];
    $em      = Container::instance()->getEntityManager();
    $authors = $em->getAuthorTermsForDropdown( true );

    $authors = apply_filters( 'wpc_author_location_terms', $authors );

    if (! empty( $authors )){
        $label = esc_html__('Author');

        $firstAuthorKey  = array_key_first($authors);
        $keyParts        = explode( ":", $firstAuthorKey );
        $firstAuthorId   = intval( $keyParts[1] );
        $firstAuthorLink = get_author_posts_url( $firstAuthorId );

        $fields['author___-1'] = array(
            'label'     => sprintf(esc_html__('Any %s (for a common query across all %s pages)', 'filter-everything'), $label, $label ),
            'data-link' => $firstAuthorLink
        );

        unset( $firstAuthorKey, $keyParts, $firstAuthorId, $firstAuthorLink );

        foreach ( $authors as $authorKey => $authorLabel ){
            $keyParts   = explode( ":", $authorKey );
            $authorId   = intval( $keyParts[1] );
            $authorLink = get_author_posts_url( $authorId );

            $fields['author___'.$authorId] = array(
                'label'     => $authorLabel,
                'data-link' => $authorLink
            );
        }

    }

    unset( $em );

    return $fields;
}

function flrt_get_no_page_terms()
{
    $fields = [];

    $fields['no_page___no_page'] = array(
        'label' => esc_html__('— No page for selection —', 'filter-everything'),
        'data-link' => ''
    );

    return $fields;
}