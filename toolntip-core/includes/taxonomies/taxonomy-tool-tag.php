<?php
/**
 * Tool Tag Taxonomy
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Tool Tag taxonomy.
 */
function tnt_register_tool_tag_taxonomy() {

    register_taxonomy(
        'tool_tag',
        array( 'tool' ),
        array(

            'labels' => array(
                'name'                       => 'Tool Tags',
                'singular_name'              => 'Tool Tag',
                'search_items'               => 'Search Tool Tags',
                'popular_items'              => 'Popular Tool Tags',
                'all_items'                  => 'All Tool Tags',
                'edit_item'                  => 'Edit Tool Tag',
                'update_item'                => 'Update Tool Tag',
                'add_new_item'               => 'Add New Tool Tag',
                'new_item_name'              => 'New Tool Tag',
                'separate_items_with_commas' => 'Separate tags with commas',
                'add_or_remove_items'        => 'Add or remove tags',
                'choose_from_most_used'      => 'Choose from most used',
                'menu_name'                  => 'Tags',
            ),

            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,

            'rewrite' => array(
                'slug' => 'tool-tag',
            ),

        )
    );

}

add_action(
    'init',
    'tnt_register_tool_tag_taxonomy'
);