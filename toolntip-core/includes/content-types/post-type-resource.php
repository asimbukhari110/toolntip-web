<?php
/**
 * Resource Custom Post Type.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Resource post type.
 */
function tnt_register_resource_post_type() {

    $labels = array(
        'name'                  => __( 'Resources', 'toolntip-core' ),
        'singular_name'         => __( 'Resource', 'toolntip-core' ),
        'menu_name'             => __( 'Resources', 'toolntip-core' ),
        'name_admin_bar'        => __( 'Resource', 'toolntip-core' ),
        'add_new'               => __( 'Add New', 'toolntip-core' ),
        'add_new_item'          => __( 'Add New Resource', 'toolntip-core' ),
        'new_item'              => __( 'New Resource', 'toolntip-core' ),
        'edit_item'             => __( 'Edit Resource', 'toolntip-core' ),
        'view_item'             => __( 'View Resource', 'toolntip-core' ),
        'all_items'             => __( 'All Resources', 'toolntip-core' ),
        'search_items'          => __( 'Search Resources', 'toolntip-core' ),
        'parent_item_colon'     => __( 'Parent Resources:', 'toolntip-core' ),
        'not_found'             => __( 'No resources found.', 'toolntip-core' ),
        'not_found_in_trash'    => __( 'No resources found in Trash.', 'toolntip-core' ),
        'featured_image'        => __( 'Featured Image', 'toolntip-core' ),
        'set_featured_image'    => __( 'Set featured image', 'toolntip-core' ),
        'remove_featured_image' => __( 'Remove featured image', 'toolntip-core' ),
        'use_featured_image'    => __( 'Use as featured image', 'toolntip-core' ),
        'archives'              => __( 'Resource Archives', 'toolntip-core' ),
        'insert_into_item'      => __( 'Insert into resource', 'toolntip-core' ),
        'uploaded_to_this_item' => __( 'Uploaded to this resource', 'toolntip-core' ),
        'filter_items_list'     => __( 'Filter resources list', 'toolntip-core' ),
        'items_list_navigation' => __( 'Resources list navigation', 'toolntip-core' ),
        'items_list'            => __( 'Resources list', 'toolntip-core' ),
    );

    register_post_type(
        'resource',
        array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'show_in_rest'        => true,
            'has_archive'         => 'resources',
            'rewrite'             => array(
                'slug'       => 'resources',
                'with_front' => false,
            ),
            'query_var'           => true,
            'exclude_from_search' => false,
            'hierarchical'        => false,
            'menu_position'       => 21,
            'menu_icon'           => 'dashicons-media-document',
            'supports'            => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'author',
                'comments',
                'revisions',
            ),
        )
    );
}

add_action( 'init', 'tnt_register_resource_post_type' );
