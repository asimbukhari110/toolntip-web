<?php
/**
 * Resource Query Engine.
 *
 * WEB-007.4 / 4.3D.1
 *
 * Canonical Resource query normalization and WP_Query construction. This
 * helper owns WHAT Resource records are returned. Relationship discovery is
 * added separately in 4.3D.2 and presentation remains owned by 4.4.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the frozen default Resource query contract.
 *
 * @return array<string,mixed>
 */
function tnt_resource_query_defaults() {

    return array(
        'limit'            => 12,
        'page'             => 1,
        'search'           => '',
        'type'             => '',
        'topic'            => '',
        'tag'              => '',
        'orderby'          => 'date',
        'order'            => 'DESC',
        'related_tool'     => 0,
        'related_resource' => 0,
        'exclude'          => array(),
        'include'          => array(),
        'status'           => 'publish',
    );
}

/**
 * Normalize a comma/whitespace separated taxonomy slug list.
 *
 * Multiple values within the same dimension are OR-ed by WP_Query. Different
 * taxonomy dimensions are combined with AND by the canonical query builder.
 *
 * @param mixed $value Raw taxonomy value/list.
 * @return string[]
 */
function tnt_resource_query_parse_slugs( $value ) {

    if ( is_array( $value ) ) {
        $parts = $value;
    } else {
        $parts = preg_split( '/[\s,]+/', trim( (string) $value ), -1, PREG_SPLIT_NO_EMPTY );
    }

    if ( empty( $parts ) ) {
        return array();
    }

    $parts = array_map(
        static function ( $part ) {
            return sanitize_title( trim( (string) $part ) );
        },
        $parts
    );

    return array_values( array_unique( array_filter( $parts ) ) );
}

/**
 * Normalize a Resource ID list.
 *
 * @param mixed $value Raw ID value/list.
 * @return int[]
 */
function tnt_resource_query_parse_ids( $value ) {

    if ( is_array( $value ) ) {
        $parts = $value;
    } else {
        $parts = preg_split( '/[\s,]+/', trim( (string) $value ), -1, PREG_SPLIT_NO_EMPTY );
    }

    if ( empty( $parts ) ) {
        return array();
    }

    return array_values(
        array_unique(
            array_filter(
                array_map( 'absint', $parts )
            )
        )
    );
}

/**
 * Normalize canonical Resource query arguments.
 *
 * 4.3D.1 intentionally normalizes but does not yet execute relationship
 * filters. Those are implemented in 4.3D.2 against the frozen 4.3B contract.
 *
 * @param array $args Incoming Resource query arguments.
 * @return array<string,mixed>
 */
function tnt_normalize_resource_query_args( $args = array() ) {

    $args = wp_parse_args( is_array( $args ) ? $args : array(), tnt_resource_query_defaults() );

    $limit = absint( $args['limit'] );
    $page  = absint( $args['page'] );

    if ( $limit < 1 ) {
        $limit = 12;
    }

    if ( $page < 1 ) {
        $page = 1;
    }

    $orderby = sanitize_key( (string) $args['orderby'] );
    $order   = strtoupper( sanitize_key( (string) $args['order'] ) );

    if ( ! in_array( $orderby, array( 'date', 'title', 'modified', 'relevance' ), true ) ) {
        $orderby = 'date';
    }

    if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
        $order = 'DESC';
    }

    $search = sanitize_text_field( (string) $args['search'] );

    /*
     * Relevance is meaningful only for a search query. Without search text,
     * fall back deterministically to newest-first rather than passing an
     * ambiguous relevance order into WP_Query.
     */
    if ( 'relevance' === $orderby && '' === trim( $search ) ) {
        $orderby = 'date';
        $order   = 'DESC';
    }

    $status = sanitize_key( (string) $args['status'] );

    if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private', 'future', 'any' ), true ) ) {
        $status = 'publish';
    }

    return array(
        'limit'            => $limit,
        'page'             => $page,
        'search'           => trim( $search ),
        'type'             => tnt_resource_query_parse_slugs( $args['type'] ),
        'topic'            => tnt_resource_query_parse_slugs( $args['topic'] ),
        'tag'              => tnt_resource_query_parse_slugs( $args['tag'] ),
        'orderby'          => $orderby,
        'order'            => $order,
        'related_tool'     => absint( $args['related_tool'] ),
        'related_resource' => absint( $args['related_resource'] ),
        'exclude'          => tnt_resource_query_parse_ids( $args['exclude'] ),
        'include'          => tnt_resource_query_parse_ids( $args['include'] ),
        'status'           => $status,
    );
}

/**
 * Apply frozen Resource taxonomy filters to WP_Query arguments.
 *
 * Public/editorial terminology maps to canonical taxonomies as follows:
 * type  -> resource_type
 * topic -> tool_category (shared canonical Tool/Resource taxonomy)
 * tag   -> resource_tag
 *
 * Values within a dimension use IN (OR semantics); dimensions are AND-ed.
 *
 * @param array $query_args WP_Query arguments.
 * @param array $args       Normalized Resource arguments.
 * @return array
 */
function tnt_resource_query_apply_tax_filters( $query_args, $args ) {

    $tax_query = array();

    $dimensions = array(
        'type'  => 'resource_type',
        'topic' => 'tool_category',
        'tag'   => 'resource_tag',
    );

    foreach ( $dimensions as $argument => $taxonomy ) {
        $terms = isset( $args[ $argument ] ) && is_array( $args[ $argument ] )
            ? $args[ $argument ]
            : array();

        if ( empty( $terms ) ) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $terms,
            'operator' => 'IN',
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    if ( ! empty( $tax_query ) ) {
        $query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    }

    return $query_args;
}

/**
 * Build canonical WP_Query arguments for Resources.
 *
 * Relationship arguments are intentionally reserved but not applied in this
 * 4.3D.1 unit. 4.3D.2 will resolve them to Resource ID allowlists.
 *
 * @param array $args Resource query contract arguments.
 * @return array
 */
function tnt_build_resource_query_args( $args = array() ) {

    $args = tnt_normalize_resource_query_args( $args );

    $query_args = array(
        'post_type'              => 'resource',
        'post_status'            => $args['status'],
        'posts_per_page'         => $args['limit'],
        'paged'                  => $args['page'],
        'ignore_sticky_posts'    => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'order'                  => $args['order'],
    );

    if ( '' !== $args['search'] ) {
        $query_args['s'] = $args['search'];
    }

    if ( ! empty( $args['include'] ) ) {
        $query_args['post__in'] = $args['include'];
    }

    if ( ! empty( $args['exclude'] ) ) {
        $query_args['post__not_in'] = $args['exclude'];
    }

    $query_args = tnt_resource_query_apply_tax_filters( $query_args, $args );

    $relationship_sets = array();

    if ( ! empty( $args['related_tool'] ) ) {
        $relationship_sets[] = tnt_get_resource_ids_related_to_tool( $args['related_tool'] );
    }
    if ( ! empty( $args['related_resource'] ) ) {
        $relationship_sets[] = tnt_get_resource_ids_related_to_resource( $args['related_resource'] );
    }

    if ( $relationship_sets ) {
        $relationship_ids = array_shift( $relationship_sets );
        foreach ( $relationship_sets as $relationship_set ) {
            $relationship_ids = array_values( array_intersect( $relationship_ids, $relationship_set ) );
        }
        if ( ! empty( $query_args['post__in'] ) ) {
            $relationship_ids = array_values( array_intersect( $query_args['post__in'], $relationship_ids ) );
        }

        // Empty post__in means unrestricted in WP_Query; use impossible ID to fail closed.
        $query_args['post__in'] = $relationship_ids ? $relationship_ids : array( 0 );
    }


    if ( 'relevance' === $args['orderby'] ) {
        $query_args['orderby'] = 'relevance';
        unset( $query_args['order'] );
    } else {
        $query_args['orderby'] = $args['orderby'];
    }

    return $query_args;
}

/**
 * Execute the canonical Resource query.
 *
 * Returning WP_Query preserves posts, found_posts, max_num_pages, and the
 * pagination metadata required by later Resource Collection/Hub consumers.
 *
 * @param array $args Resource query contract arguments.
 * @return WP_Query
 */
function tnt_get_resources( $args = array() ) {

    return new WP_Query( tnt_build_resource_query_args( $args ) );
}
