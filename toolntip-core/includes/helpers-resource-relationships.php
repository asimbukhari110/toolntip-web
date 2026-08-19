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
