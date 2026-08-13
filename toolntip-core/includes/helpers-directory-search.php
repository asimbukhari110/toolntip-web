<?php
/**
 * Tool Directory Search Helpers.
 *
 * WEB-006.2 - Server-rendered search for the native Tool archive.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the active Tool Directory search term.
 *
 * @return string
 */
function tnt_get_tool_directory_search_term() {

    if ( ! isset( $_GET['tool_search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return '';
    }

    return sanitize_text_field( wp_unslash( $_GET['tool_search'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Apply Tool Directory search to the native main archive query.
 *
 * WordPress handles the actual title/content/excerpt search through its
 * standard `s` query variable. The public URL remains scoped to the Tool
 * Directory through the `tool_search` parameter.
 *
 * @param WP_Query $query Current WordPress query.
 * @return void
 */
function tnt_apply_tool_directory_search( $query ) {

    if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'tool' ) ) {
        return;
    }

    $search_term = tnt_get_tool_directory_search_term();

    if ( '' === $search_term ) {
        return;
    }

    $query->set( 's', $search_term );
}

add_action( 'pre_get_posts', 'tnt_apply_tool_directory_search' );
