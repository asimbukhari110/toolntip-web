<?php
/**
 * Resource Type Taxonomy.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Resource Type taxonomy.
 */
function tnt_register_resource_type_taxonomy() {

    register_taxonomy(
        'resource_type',
        array( 'resource' ),
        array(
            'labels' => array(
                'name'              => __( 'Resource Types', 'toolntip-core' ),
                'singular_name'     => __( 'Resource Type', 'toolntip-core' ),
                'search_items'      => __( 'Search Resource Types', 'toolntip-core' ),
                'all_items'         => __( 'All Resource Types', 'toolntip-core' ),
                'parent_item'       => __( 'Parent Resource Type', 'toolntip-core' ),
                'parent_item_colon' => __( 'Parent Resource Type:', 'toolntip-core' ),
                'edit_item'         => __( 'Edit Resource Type', 'toolntip-core' ),
                'update_item'       => __( 'Update Resource Type', 'toolntip-core' ),
                'add_new_item'      => __( 'Add New Resource Type', 'toolntip-core' ),
                'new_item_name'     => __( 'New Resource Type', 'toolntip-core' ),
                'menu_name'         => __( 'Resource Types', 'toolntip-core' ),
            ),
            'hierarchical'      => true,
            'public'            => true,
            'publicly_queryable'=> true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array(
                'slug'         => 'resources/type',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        )
    );
}

add_action( 'init', 'tnt_register_resource_type_taxonomy' );
