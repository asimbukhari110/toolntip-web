<?php
/**
 * Resource relationship helpers.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize an ordered relationship ID list.
 *
 * @param mixed  $value       Raw relationship value.
 * @param string $post_type   Required target post type.
 * @param int    $exclude_id  Object ID to exclude (for self-reference protection).
 * @return int[]
 */
function tnt_normalize_resource_relationship_ids( $value, $post_type, $exclude_id = 0 ) {

    if ( ! is_array( $value ) ) {
        $value = empty( $value ) ? array() : array( $value );
    }

    $normalized = array();
    $seen       = array();

    foreach ( $value as $candidate ) {
        $candidate_id = absint( $candidate );

        if ( $candidate_id <= 0 || $candidate_id === absint( $exclude_id ) ) {
            continue;
        }

        if ( isset( $seen[ $candidate_id ] ) ) {
            continue;
        }

        $candidate_post = get_post( $candidate_id );

        if ( ! $candidate_post || $post_type !== $candidate_post->post_type ) {
            continue;
        }

        $seen[ $candidate_id ] = true;
        $normalized[]          = $candidate_id;
    }

    return $normalized;
}

/**
 * Sanitize Related Tool IDs for metadata registration.
 *
 * @param mixed $value Raw meta value.
 * @return int[]
 */
function tnt_sanitize_related_tool_ids( $value ) {
    return tnt_normalize_resource_relationship_ids( $value, 'tool' );
}

/**
 * Sanitize Related Resource IDs for metadata registration.
 *
 * Self-reference is removed again during Resource save when the current object
 * ID is available.
 *
 * @param mixed $value Raw meta value.
 * @return int[]
 */
function tnt_sanitize_related_resource_ids( $value ) {
    return tnt_normalize_resource_relationship_ids( $value, 'resource' );
}

/**
 * Get ordered related Tool IDs.
 *
 * @param int  $resource_id     Resource ID.
 * @param bool $published_only  Whether to return published targets only.
 * @return int[]
 */
function tnt_get_resource_related_tool_ids( $resource_id, $published_only = false ) {

    $ids = tnt_normalize_resource_relationship_ids(
        get_post_meta( absint( $resource_id ), 'tnt_related_tool_ids', true ),
        'tool'
    );

    if ( ! $published_only ) {
        return $ids;
    }

    return array_values(
        array_filter(
            $ids,
            static function ( $tool_id ) {
                return 'publish' === get_post_status( $tool_id );
            }
        )
    );
}

/**
 * Get ordered related Resource IDs.
 *
 * @param int  $resource_id     Resource ID.
 * @param bool $published_only  Whether to return published targets only.
 * @return int[]
 */
function tnt_get_resource_related_resource_ids( $resource_id, $published_only = false ) {

    $resource_id = absint( $resource_id );

    $ids = tnt_normalize_resource_relationship_ids(
        get_post_meta( $resource_id, 'tnt_related_resource_ids', true ),
        'resource',
        $resource_id
    );

    if ( ! $published_only ) {
        return $ids;
    }

    return array_values(
        array_filter(
            $ids,
            static function ( $related_resource_id ) {
                return 'publish' === get_post_status( $related_resource_id );
            }
        )
    );
}

/**
 * Read-only reverse discovery for a saved Resource -> Tool relationship.
 */
function tnt_get_resource_ids_related_to_tool( $tool_id ) {
    $tool_id = absint( $tool_id );
    if ( ! $tool_id || 'tool' !== get_post_type( $tool_id ) ) {
        return array();
    }

    $matches = array();
    $ids = get_posts( array(
        'post_type' => 'resource',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ) );

    foreach ( $ids as $resource_id ) {
        if ( in_array( $tool_id, tnt_get_resource_related_tool_ids( $resource_id ), true ) ) {
            $matches[] = absint( $resource_id );
        }
    }

    return array_values( array_unique( array_filter( $matches ) ) );
}

/**
 * Read-only bidirectional discovery for a single-sided Resource relationship.
 */
function tnt_get_resource_ids_related_to_resource( $resource_id ) {
    $resource_id = absint( $resource_id );
    if ( ! $resource_id || 'resource' !== get_post_type( $resource_id ) ) {
        return array();
    }

    $matches = array();

    foreach ( tnt_get_resource_related_resource_ids( $resource_id ) as $related_id ) {
        $related_id = absint( $related_id );
        if ( $related_id && $related_id !== $resource_id && 'resource' === get_post_type( $related_id ) && 'publish' === get_post_status( $related_id ) ) {
            $matches[] = $related_id;
        }
    }

    $ids = get_posts( array(
        'post_type' => 'resource',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post__not_in' => array( $resource_id ),
        'no_found_rows' => true,
    ) );

    foreach ( $ids as $candidate_id ) {
        if ( in_array( $resource_id, tnt_get_resource_related_resource_ids( $candidate_id ), true ) ) {
            $matches[] = absint( $candidate_id );
        }
    }

    $matches = array_values( array_unique( array_filter( array_map( 'absint', $matches ) ) ) );
    sort( $matches, SORT_NUMERIC );
    return $matches;
}
