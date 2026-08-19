<?php
/**
 * Resource Topic Taxonomy.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register legacy Resource Topic taxonomy during canonical-category migration.
 *
 * Existing term/relationship data remains readable for migration and rollback,
 * but new editorial use and public routing are disabled.
 */
function tnt_register_resource_topic_taxonomy() {

    register_taxonomy(
        'resource_topic',
        array( 'resource' ),
        array(
            'labels' => array(
                'name'              => __( 'Resource Topics', 'toolntip-core' ),
                'singular_name'     => __( 'Resource Topic', 'toolntip-core' ),
                'search_items'      => __( 'Search Resource Topics', 'toolntip-core' ),
                'all_items'         => __( 'All Resource Topics', 'toolntip-core' ),
                'parent_item'       => __( 'Parent Resource Topic', 'toolntip-core' ),
                'parent_item_colon' => __( 'Parent Resource Topic:', 'toolntip-core' ),
                'edit_item'         => __( 'Edit Resource Topic', 'toolntip-core' ),
                'update_item'       => __( 'Update Resource Topic', 'toolntip-core' ),
                'add_new_item'      => __( 'Add New Resource Topic', 'toolntip-core' ),
                'new_item_name'     => __( 'New Resource Topic', 'toolntip-core' ),
                'menu_name'         => __( 'Resource Topics', 'toolntip-core' ),
            ),
            'hierarchical'       => true,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_admin_column'  => false,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => false,
        )
    );
}

add_action( 'init', 'tnt_register_resource_topic_taxonomy' );
