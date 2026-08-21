<?php
/**
 * Resource relationship helpers.
 *
 * Canonical relationship metadata:
 * - tnt_related_tool_ids
 * - tnt_related_resource_ids
 *
 * Derived reverse relationship indexes:
 * - _tnt_idx_related_tool
 * - _tnt_idx_related_resource
 *
 * Canonical metadata remains authoritative. Derived index metadata exists
 * only to provide efficient reverse relationship discovery and may always
 * be rebuilt from the canonical relationship values.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Derived relationship index meta keys.
 *
 * These are intentionally separate from canonical editorial metadata.
 * Each related ID is stored as its own scalar post-meta row.
 */
if ( ! defined( 'TNT_RESOURCE_TOOL_INDEX_META_KEY' ) ) {
	define( 'TNT_RESOURCE_TOOL_INDEX_META_KEY', '_tnt_idx_related_tool' );
}

if ( ! defined( 'TNT_RESOURCE_RESOURCE_INDEX_META_KEY' ) ) {
	define( 'TNT_RESOURCE_RESOURCE_INDEX_META_KEY', '_tnt_idx_related_resource' );
}


/**
 * Normalize an ordered relationship ID list.
 *
 * @param mixed  $value       Raw relationship value.
 * @param string $post_type   Required target post type.
 * @param int    $exclude_id  Object ID to exclude for self-reference protection.
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

		if (
			$candidate_id <= 0 ||
			$candidate_id === absint( $exclude_id )
		) {
			continue;
		}

		if ( isset( $seen[ $candidate_id ] ) ) {
			continue;
		}

		$candidate_post = get_post( $candidate_id );

		if (
			! $candidate_post ||
			$post_type !== $candidate_post->post_type
		) {
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

	return tnt_normalize_resource_relationship_ids(
		$value,
		'tool'
	);
}


/**
 * Sanitize Related Resource IDs for metadata registration.
 *
 * Self-reference is removed again during Resource save when the current
 * Resource ID is available.
 *
 * @param mixed $value Raw meta value.
 * @return int[]
 */
function tnt_sanitize_related_resource_ids( $value ) {

	return tnt_normalize_resource_relationship_ids(
		$value,
		'resource'
	);
}


/**
 * Get ordered related Tool IDs from canonical Resource metadata.
 *
 * @param int  $resource_id     Resource ID.
 * @param bool $published_only  Whether to return published targets only.
 * @return int[]
 */
function tnt_get_resource_related_tool_ids( $resource_id, $published_only = false ) {

	$ids = tnt_normalize_resource_relationship_ids(
		get_post_meta(
			absint( $resource_id ),
			'tnt_related_tool_ids',
			true
		),
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
 * Get ordered related Resource IDs from canonical Resource metadata.
 *
 * @param int  $resource_id     Resource ID.
 * @param bool $published_only  Whether to return published targets only.
 * @return int[]
 */
function tnt_get_resource_related_resource_ids( $resource_id, $published_only = false ) {

	$resource_id = absint( $resource_id );

	$ids = tnt_normalize_resource_relationship_ids(
		get_post_meta(
			$resource_id,
			'tnt_related_resource_ids',
			true
		),
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
 * Rebuild derived relationship indexes for one Resource.
 *
 * Canonical Resource relationship metadata remains the authoritative source.
 * Existing derived rows are deleted and regenerated from normalized canonical
 * values.
 *
 * The function does not modify:
 *
 * - tnt_related_tool_ids
 * - tnt_related_resource_ids
 *
 * @param int $resource_id Resource post ID.
 * @return bool True when rebuilt successfully; false for an invalid target.
 */
function tnt_rebuild_resource_relationship_index( $resource_id ) {

	$resource_id = absint( $resource_id );

	if (
		! $resource_id ||
		'resource' !== get_post_type( $resource_id )
	) {
		return false;
	}

	/*
	 * Derived data is rebuildable. Always remove stale rows first.
	 */
	delete_post_meta(
		$resource_id,
		TNT_RESOURCE_TOOL_INDEX_META_KEY
	);

	delete_post_meta(
		$resource_id,
		TNT_RESOURCE_RESOURCE_INDEX_META_KEY
	);

	/*
	 * Build Tool reverse-index rows.
	 */
	$tool_ids = tnt_get_resource_related_tool_ids(
		$resource_id,
		false
	);

	foreach ( $tool_ids as $tool_id ) {

		$tool_id = absint( $tool_id );

		if ( ! $tool_id ) {
			continue;
		}

		add_post_meta(
			$resource_id,
			TNT_RESOURCE_TOOL_INDEX_META_KEY,
			(string) $tool_id,
			false
		);
	}

	/*
	 * Build Resource reverse-index rows.
	 */
	$related_resource_ids = tnt_get_resource_related_resource_ids(
		$resource_id,
		false
	);

	foreach ( $related_resource_ids as $related_resource_id ) {

		$related_resource_id = absint( $related_resource_id );

		if (
			! $related_resource_id ||
			$related_resource_id === $resource_id
		) {
			continue;
		}

		add_post_meta(
			$resource_id,
			TNT_RESOURCE_RESOURCE_INDEX_META_KEY,
			(string) $related_resource_id,
			false
		);
	}

	return true;
}


/**
 * Find published Resources by an exact derived relationship index value.
 *
 * This is the optimized reverse-discovery primitive.
 *
 * Relationship IDs are stored as individual scalar post-meta rows, allowing
 * an exact equality lookup. The function intentionally does not perform a
 * serialized LIKE query against canonical array metadata.
 *
 * @param string $meta_key    Derived relationship index meta key.
 * @param int    $related_id  Related Tool or Resource ID.
 * @return int[] Published Resource IDs.
 */
function tnt_get_resource_ids_by_relationship_index( $meta_key, $related_id ) {

	global $wpdb;

	$related_id = absint( $related_id );

	$allowed_keys = array(
		TNT_RESOURCE_TOOL_INDEX_META_KEY,
		TNT_RESOURCE_RESOURCE_INDEX_META_KEY,
	);

	if (
		! $related_id ||
		! in_array( $meta_key, $allowed_keys, true )
	) {
		return array();
	}

	/*
	 * Exact scalar lookup against derived index metadata.
	 *
	 * Do not replace this with a serialized LIKE comparison against:
	 *
	 * - tnt_related_tool_ids
	 * - tnt_related_resource_ids
	 */
	$sql = $wpdb->prepare(
		"
		SELECT DISTINCT p.ID
		FROM {$wpdb->posts} AS p
		INNER JOIN {$wpdb->postmeta} AS pm
			ON pm.post_id = p.ID
		WHERE p.post_type = %s
			AND p.post_status = %s
			AND pm.meta_key = %s
			AND pm.meta_value = %s
		ORDER BY p.ID ASC
		",
		'resource',
		'publish',
		$meta_key,
		(string) $related_id
	);

	$ids = $wpdb->get_col( $sql );

	if ( empty( $ids ) ) {
		return array();
	}

	$ids = array_map(
		'absint',
		$ids
	);

	$ids = array_values(
		array_filter( $ids )
	);

	$ids = array_values(
		array_unique( $ids )
	);

	sort(
		$ids,
		SORT_NUMERIC
	);

	return $ids;
}


/**
 * Read-only reverse discovery for a saved Resource -> Tool relationship.
 *
 * Public API preserved from WEB-007.4 / 4.3.
 *
 * Reverse discovery now uses the derived scalar relationship index rather
 * than scanning every published Resource.
 *
 * @param int $tool_id Tool post ID.
 * @return int[] Published Resource IDs.
 */
function tnt_get_resource_ids_related_to_tool( $tool_id ) {

	$tool_id = absint( $tool_id );

	if (
		! $tool_id ||
		'tool' !== get_post_type( $tool_id )
	) {
		return array();
	}

	return tnt_get_resource_ids_by_relationship_index(
		TNT_RESOURCE_TOOL_INDEX_META_KEY,
		$tool_id
	);
}


/**
 * Read-only bidirectional discovery for a Resource relationship.
 *
 * Public API preserved from WEB-007.4 / 4.3.
 *
 * Outgoing relationships continue to come directly from canonical metadata.
 * Incoming relationships are discovered through the derived scalar reverse
 * index.
 *
 * @param int $resource_id Resource post ID.
 * @return int[] Published related Resource IDs.
 */
function tnt_get_resource_ids_related_to_resource( $resource_id ) {

	$resource_id = absint( $resource_id );

	if (
		! $resource_id ||
		'resource' !== get_post_type( $resource_id )
	) {
		return array();
	}

	/*
	 * Direct/outgoing relationships remain canonical.
	 *
	 * Only published related Resources participate in public discovery.
	 */
	$direct_ids = tnt_get_resource_related_resource_ids(
		$resource_id,
		true
	);

	/*
	 * Incoming relationships are discovered using the derived scalar index.
	 */
	$incoming_ids = tnt_get_resource_ids_by_relationship_index(
		TNT_RESOURCE_RESOURCE_INDEX_META_KEY,
		$resource_id
	);

	$ids = array_merge(
		$direct_ids,
		$incoming_ids
	);

	$ids = array_map(
		'absint',
		$ids
	);

	$ids = array_filter(
		$ids,
		static function ( $candidate_id ) use ( $resource_id ) {

			return (
				$candidate_id > 0 &&
				$candidate_id !== $resource_id
			);
		}
	);

	$ids = array_values(
		array_unique( $ids )
	);

	sort(
		$ids,
		SORT_NUMERIC
	);

	return $ids;
}