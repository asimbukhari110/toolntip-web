<?php
/**
 * ToolNTip package Application Page <-> Tool linkage helpers.
 *
 * Reuses the existing _tnt_tool_context_id Page -> Tool relationship. No
 * second association store is introduced. Package identity remains owned by
 * the provisioned Page via _tnt_application_package_id.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Return normalized package identity for a provisioned application Page. */
function tnt_get_application_package_id_for_page( $page_id ) {
    $page_id = absint( $page_id );
    if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) { return ''; }
    return sanitize_key( (string) get_post_meta( $page_id, '_tnt_application_package_id', true ) );
}

/** Return the validated linked Tool for a provisioned package Page. */
function tnt_get_application_package_linked_tool( $page_id ) {
    $page_id = absint( $page_id );
    if ( '' === tnt_get_application_package_id_for_page( $page_id ) ) { return null; }
    $tool_id = absint( get_post_meta( $page_id, '_tnt_tool_context_id', true ) );
    if ( ! $tool_id ) { return null; }
    $tool = tnt_get_tool( $tool_id );
    return $tool instanceof WP_Post ? $tool : null;
}

/** Return read-only linkage state for Applications Manager and QA. */
function tnt_get_application_package_tool_linkage( $application_id ) {
    $application_id = sanitize_key( (string) $application_id );
    $page_id = function_exists( 'tnt_get_application_package_page_id' ) ? tnt_get_application_package_page_id( $application_id ) : 0;
    if ( ! $page_id && function_exists( 'tnt_find_application_package_page_by_meta' ) ) {
        $page_id = tnt_find_application_package_page_by_meta( $application_id );
    }
    if ( ! $page_id ) {
        return array( 'state' => 'page_missing', 'page_id' => 0, 'tool_id' => 0, 'tool' => null );
    }
    $tool = tnt_get_application_package_linked_tool( $page_id );
    if ( ! $tool ) {
        return array( 'state' => 'unlinked', 'page_id' => $page_id, 'tool_id' => 0, 'tool' => null );
    }
    return array( 'state' => 'linked', 'page_id' => $page_id, 'tool_id' => (int) $tool->ID, 'tool' => $tool );
}

/**
 * Resolve a provisioned package application Page for a Tool.
 *
 * Ambiguous reverse relationships fail closed: if more than one package Page
 * points to the same Tool, no destination is returned until an administrator
 * resolves the duplicate linkage.
 */
function tnt_get_application_package_page_for_tool( $tool ) {
    $tool = tnt_get_tool( $tool );
    if ( ! $tool instanceof WP_Post ) { return null; }
    $ids = get_posts( array(
        'post_type'      => 'page',
        'post_status'    => array( 'draft', 'pending', 'private', 'publish', 'future' ),
        'posts_per_page' => 2,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key' => '_tnt_tool_context_id', 'value' => (int) $tool->ID, 'compare' => '=', 'type' => 'NUMERIC' ),
            array( 'key' => '_tnt_application_package_id', 'compare' => 'EXISTS' ),
        ),
    ) );
    if ( 1 !== count( $ids ) ) { return null; }
    $page = get_post( absint( $ids[0] ) );
    return $page instanceof WP_Post ? $page : null;
}

/** Return current queried package Page identity, or empty string. */
function tnt_get_current_application_package_id() {
    $page_id = absint( get_queried_object_id() );
    if ( ! $page_id ) { $page_id = absint( get_the_ID() ); }
    return tnt_get_application_package_id_for_page( $page_id );
}
