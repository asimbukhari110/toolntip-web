<?php
/**
 * Tool Category Taxonomy
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the canonical shared Category taxonomy for Tools and Resources.
 */
function tnt_register_tool_category_taxonomy() {

    register_taxonomy(
        'tool_category',
        array( 'tool', 'resource' ),
        array(

            'labels' => array(
                'name'              => 'Tool Categories',
                'singular_name'     => 'Tool Category',
                'search_items'      => 'Search Tool Categories',
                'all_items'         => 'All Tool Categories',
                'parent_item'       => 'Parent Tool Category',
                'parent_item_colon' => 'Parent Tool Category:',
                'edit_item'         => 'Edit Tool Category',
                'update_item'       => 'Update Tool Category',
                'add_new_item'      => 'Add New Tool Category',
                'new_item_name'     => 'New Tool Category',
                'menu_name'         => 'Categories',
            ),

            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,

            'rewrite' => array(
                'slug' => 'tool-category',
            ),

        )
    );

}

add_action(
    'init',
    'tnt_register_tool_category_taxonomy'
);