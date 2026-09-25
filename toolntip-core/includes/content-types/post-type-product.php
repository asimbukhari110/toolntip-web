<?php
/**
 * Product Custom Post Type.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Product post type.
 *
 * Product editorial content lives in WordPress. Edition, Release and artifact
 * distribution data remain in the governed Product-platform tables.
 *
 * @return void
 */
function tnt_register_product_post_type() {
    $labels = array(
        'name'                  => __( 'Products', 'toolntip-core' ),
        'singular_name'         => __( 'Product', 'toolntip-core' ),
        'menu_name'             => __( 'Products', 'toolntip-core' ),
        'name_admin_bar'        => __( 'Product', 'toolntip-core' ),
        'add_new'               => __( 'Add New', 'toolntip-core' ),
        'add_new_item'          => __( 'Add New Product', 'toolntip-core' ),
        'new_item'              => __( 'New Product', 'toolntip-core' ),
        'edit_item'             => __( 'Edit Product', 'toolntip-core' ),
        'view_item'             => __( 'View Product', 'toolntip-core' ),
        'all_items'             => __( 'All Products', 'toolntip-core' ),
        'search_items'          => __( 'Search Products', 'toolntip-core' ),
        'not_found'             => __( 'No products found.', 'toolntip-core' ),
        'not_found_in_trash'    => __( 'No products found in Trash.', 'toolntip-core' ),
        'featured_image'        => __( 'Product Image', 'toolntip-core' ),
        'set_featured_image'    => __( 'Set product image', 'toolntip-core' ),
        'remove_featured_image' => __( 'Remove product image', 'toolntip-core' ),
        'use_featured_image'    => __( 'Use as product image', 'toolntip-core' ),
        'archives'              => __( 'Product Archives', 'toolntip-core' ),
        'insert_into_item'      => __( 'Insert into product', 'toolntip-core' ),
        'uploaded_to_this_item' => __( 'Uploaded to this product', 'toolntip-core' ),
        'filter_items_list'     => __( 'Filter products list', 'toolntip-core' ),
        'items_list_navigation' => __( 'Products list navigation', 'toolntip-core' ),
        'items_list'            => __( 'Products list', 'toolntip-core' ),
    );

    register_post_type(
        'tnt_product',
        array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'show_in_rest'        => true,
            'has_archive'         => 'products',
            'rewrite'             => array(
                'slug'       => 'products',
                'with_front' => false,
            ),
            'query_var'           => true,
            'exclude_from_search' => false,
            'hierarchical'        => false,
            'menu_position'       => 22,
            'menu_icon'           => 'dashicons-products',
            'supports'            => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'revisions',
                'custom-fields',
            ),
        )
    );
}
add_action( 'init', 'tnt_register_product_post_type' );

/**
 * Flush rewrite rules once for the Product route contract.
 *
 * This is versioned so normal requests never flush rewrite rules repeatedly.
 *
 * @return void
 */
function tnt_maybe_flush_product_rewrite_rules() {
    $rewrite_version = '2';
    $installed       = (string) get_option( 'tnt_product_rewrite_version', '' );

    if ( $rewrite_version === $installed ) {
        return;
    }

    flush_rewrite_rules( false );
    update_option( 'tnt_product_rewrite_version', $rewrite_version, false );
}
add_action( 'init', 'tnt_maybe_flush_product_rewrite_rules', 20 );
