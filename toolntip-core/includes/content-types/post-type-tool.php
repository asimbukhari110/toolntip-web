<?php
/**
 * Tool Custom Post Type.
 *
 * ToolNTip Core is the canonical owner of the Tool entity. During the
 * migration away from CPT UI, an already-registered external `tool` post type
 * is left untouched so the handover can be verified without double
 * registration.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Tool post type when no earlier provider has registered it.
 *
 * Priority 20 intentionally gives compatibility providers such as CPT UI the
 * normal priority-10 init window. Once they are deactivated, Core assumes
 * ownership without changing the `tool` post-type identity.
 *
 * @return void
 */
function tnt_register_tool_post_type() {
    if ( post_type_exists( 'tool' ) ) {
        return;
    }

    $labels = array(
        'name'                  => __( 'ToolNTip Library', 'toolntip-core' ),
        'singular_name'         => __( 'Tool', 'toolntip-core' ),
        'menu_name'             => __( 'ToolNTip Library', 'toolntip-core' ),
        'name_admin_bar'        => __( 'Tool', 'toolntip-core' ),
        'add_new'               => __( 'Add New', 'toolntip-core' ),
        'add_new_item'          => __( 'Add New Tool', 'toolntip-core' ),
        'new_item'              => __( 'New Tool', 'toolntip-core' ),
        'edit_item'             => __( 'Edit Tool', 'toolntip-core' ),
        'view_item'             => __( 'View Tool', 'toolntip-core' ),
        'all_items'             => __( 'All Tools', 'toolntip-core' ),
        'search_items'          => __( 'Search Tools', 'toolntip-core' ),
        'not_found'             => __( 'No tools found.', 'toolntip-core' ),
        'not_found_in_trash'    => __( 'No tools found in Trash.', 'toolntip-core' ),
        'featured_image'        => __( 'Featured Image', 'toolntip-core' ),
        'set_featured_image'    => __( 'Set featured image', 'toolntip-core' ),
        'remove_featured_image' => __( 'Remove featured image', 'toolntip-core' ),
        'use_featured_image'    => __( 'Use as featured image', 'toolntip-core' ),
        'archives'              => __( 'Tool Archives', 'toolntip-core' ),
        'insert_into_item'      => __( 'Insert into tool', 'toolntip-core' ),
        'uploaded_to_this_item' => __( 'Uploaded to this tool', 'toolntip-core' ),
        'filter_items_list'     => __( 'Filter tools list', 'toolntip-core' ),
        'items_list_navigation' => __( 'Tools list navigation', 'toolntip-core' ),
        'items_list'            => __( 'Tools list', 'toolntip-core' ),
    );

    $GLOBALS['tnt_core_registered_tool_post_type'] = true;

    register_post_type(
        'tool',
        array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'show_in_rest'        => true,
            'has_archive'         => 'tools',
            'rewrite'             => array(
                'slug'       => 'tools',
                'with_front' => true,
            ),
            'query_var'           => true,
            'exclude_from_search' => false,
            'hierarchical'        => false,
            'can_export'          => true,
            'delete_with_user'    => false,
            'capability_type'     => 'post',
            'menu_icon'           => 'dashicons-hammer',
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'revisions',
            ),
            // Preserve the CPT UI-era WordPress category/tag attachment.
            // Core's canonical tool_category/tool_tag taxonomies are registered
            // separately and remain unchanged by this handover.
            'taxonomies'          => array( 'category', 'post_tag' ),
        )
    );
}
add_action( 'init', 'tnt_register_tool_post_type', 20 );

/**
 * Flush rewrite rules once when Core first assumes Tool CPT ownership.
 *
 * The marker is deliberately not written while an external provider owns the
 * post type. This ensures the first request after CPT UI deactivation refreshes
 * the /tools/ rules exactly once.
 *
 * @return void
 */
function tnt_maybe_flush_tool_rewrite_rules() {
    if ( ! did_action( 'init' ) || ! post_type_exists( 'tool' ) ) {
        return;
    }

    // Core sets this request-local flag only when it performed registration.
    if ( empty( $GLOBALS['tnt_core_registered_tool_post_type'] ) ) {
        return;
    }

    $rewrite_version = '1';
    $installed       = (string) get_option( 'tnt_tool_rewrite_version', '' );

    if ( $rewrite_version === $installed ) {
        return;
    }

    flush_rewrite_rules( false );
    update_option( 'tnt_tool_rewrite_version', $rewrite_version, false );
}
add_action( 'init', 'tnt_maybe_flush_tool_rewrite_rules', 30 );
