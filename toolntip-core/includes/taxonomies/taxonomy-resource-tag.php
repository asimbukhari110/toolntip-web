<?php
/**
 * Resource Tag Taxonomy.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Resource Tag taxonomy.
 *
 * Resource Tags provide optional, granular, non-hierarchical semantic
 * classification for Resources. They remain separate from WordPress post_tag
 * so the Resource domain owns its vocabulary and public URL contract.
 */
function tnt_register_resource_tag_taxonomy() {

    register_taxonomy(
        'resource_tag',
        array( 'resource' ),
        array(
            'labels' => array(
                'name'                       => __( 'Resource Tags', 'toolntip-core' ),
                'singular_name'              => __( 'Resource Tag', 'toolntip-core' ),
                'search_items'               => __( 'Search Resource Tags', 'toolntip-core' ),
                'popular_items'              => __( 'Popular Resource Tags', 'toolntip-core' ),
                'all_items'                  => __( 'All Resource Tags', 'toolntip-core' ),
                'edit_item'                  => __( 'Edit Resource Tag', 'toolntip-core' ),
                'update_item'                => __( 'Update Resource Tag', 'toolntip-core' ),
                'add_new_item'               => __( 'Add New Resource Tag', 'toolntip-core' ),
                'new_item_name'              => __( 'New Resource Tag Name', 'toolntip-core' ),
                'separate_items_with_commas' => __( 'Separate Resource Tags with commas', 'toolntip-core' ),
                'add_or_remove_items'         => __( 'Add or remove Resource Tags', 'toolntip-core' ),
                'choose_from_most_used'       => __( 'Choose from the most used Resource Tags', 'toolntip-core' ),
                'not_found'                  => __( 'No Resource Tags found.', 'toolntip-core' ),
                'menu_name'                  => __( 'Resource Tags', 'toolntip-core' ),
            ),
            'hierarchical'       => false,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'resources/tag',
                'with_front' => false,
            ),
        )
    );
}

add_action( 'init', 'tnt_register_resource_tag_taxonomy' );
