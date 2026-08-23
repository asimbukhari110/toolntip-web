<?php
/**
 * Resource Detail data helpers.
 *
 * WEB-007.4 / 4.6-B
 *
 * Owns the normalized identity contract for a single Resource and the read-only
 * Related Tools projection introduced by WEB-007.4 / 4.6-D. Comments,
 * monetization and other later presentation domains remain outside this helper.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize Resource taxonomy terms for detail presentation.
 *
 * @param int    $resource_id Resource post ID.
 * @param string $taxonomy    Taxonomy name.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_resource_detail_terms( $resource_id, $taxonomy ) {

    $terms = get_the_terms( $resource_id, $taxonomy );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array();
    }

    $normalized = array();

    foreach ( $terms as $term ) {
        if ( ! $term instanceof WP_Term ) {
            continue;
        }

        $url = get_term_link( $term );

        $normalized[] = array(
            'id'   => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'url'  => is_wp_error( $url ) ? '' : $url,
        );
    }

    return $normalized;
}

/**
 * Return the canonical identity contract for one Resource.
 *
 * The editorial body is intentionally not copied into this data structure.
 * Single Resource templates must render body content through `the_content()` so
 * WordPress blocks, shortcodes, embeds and compatible content builders retain
 * ownership of the editorial content pipeline.
 *
 * @param WP_Post|int $resource Resource object or ID.
 * @return array<string,mixed>|null
 */
function tnt_get_resource_detail_data( $resource ) {

    if ( ! $resource instanceof WP_Post ) {
        $resource_id = absint( $resource );

        if ( ! $resource_id ) {
            return null;
        }

        $resource = get_post( $resource_id );
    }

    if ( ! $resource || 'resource' !== $resource->post_type ) {
        return null;
    }

    $resource_id  = (int) $resource->ID;
    $author_id    = (int) $resource->post_author;
    $thumbnail_id = (int) get_post_thumbnail_id( $resource_id );

    return array(
        'id'        => $resource_id,
        'post'      => $resource,
        'title'     => get_the_title( $resource ),
        'permalink' => get_permalink( $resource ),
        'featured'  => (bool) get_post_meta( $resource_id, 'tnt_resource_featured', true ),
        'media'     => array(
            'id'  => $thumbnail_id,
            'alt' => $thumbnail_id ? (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) : '',
        ),
        'type'      => tnt_get_resource_detail_terms( $resource_id, 'resource_type' ),
        'topics'    => tnt_get_resource_detail_terms( $resource_id, 'tool_category' ),
        'tags'      => tnt_get_resource_detail_terms( $resource_id, 'resource_tag' ),
        'date'      => array(
            'published_machine' => get_the_date( DATE_W3C, $resource ),
            'published_display' => get_the_date( get_option( 'date_format' ), $resource ),
            'modified_machine'  => get_the_modified_date( DATE_W3C, $resource_id ),
            'modified_display'  => get_the_modified_date( get_option( 'date_format' ), $resource_id ),
        ),
        'author'    => array(
            'id'   => $author_id,
            'name' => $author_id ? get_the_author_meta( 'display_name', $author_id ) : '',
            'url'  => $author_id ? get_author_posts_url( $author_id ) : '',
        ),
    );
}

/**
 * Return ordered Related Tool Card data for one Resource.
 *
 * Canonical Resource -> Tool metadata remains authoritative. This direct lookup
 * deliberately does not use the A6 reverse index because the current Resource
 * is already known. Only published Tool targets are exposed, editorial order is
 * preserved, and no frontend relationship mutation occurs.
 *
 * WEB-007.4 / 4.6-D
 *
 * @param int $resource_id Resource post ID.
 * @param int $limit       Maximum number of Tool cards to return.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_resource_detail_related_tools( $resource_id, $limit = 3 ) {

    $resource_id = absint( $resource_id );
    $limit       = absint( $limit );

    if ( ! $resource_id || 'resource' !== get_post_type( $resource_id ) || $limit < 1 ) {
        return array();
    }

    if ( ! function_exists( 'tnt_get_resource_related_tool_ids' ) || ! function_exists( 'tnt_get_tool_card_data' ) ) {
        return array();
    }

    $related_ids = tnt_get_resource_related_tool_ids( $resource_id, true );

    if ( empty( $related_ids ) ) {
        return array();
    }

    $tools = array();

    foreach ( $related_ids as $tool_id ) {
        if ( count( $tools ) >= $limit ) {
            break;
        }

        $tool = get_post( absint( $tool_id ) );

        if ( ! $tool instanceof WP_Post || 'tool' !== $tool->post_type || 'publish' !== $tool->post_status ) {
            continue;
        }

        $card_data = tnt_get_tool_card_data( $tool );

        if ( ! empty( $card_data ) ) {
            $tools[] = $card_data;
        }
    }

    return $tools;
}

/**
 * Return ordered Related Resource Card data for one Resource Detail page.
 *
 * Direct canonical relationships are presented first in editorial order.
 * Incoming relationships then fill remaining capacity through the A6 scalar
 * reverse index. The current Resource, duplicates and unpublished targets are
 * excluded. This helper is read-only and does not mutate relationship data.
 *
 * WEB-007.4 / 4.6-E
 *
 * @param int $resource_id Resource post ID.
 * @param int $limit       Maximum number of Resource cards to return.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_resource_detail_related_resources( $resource_id, $limit = 3 ) {

    $resource_id = absint( $resource_id );
    $limit       = absint( $limit );

    if ( ! $resource_id || 'resource' !== get_post_type( $resource_id ) || $limit < 1 ) {
        return array();
    }

    if (
        ! function_exists( 'tnt_get_resource_related_resource_ids' ) ||
        ! function_exists( 'tnt_get_resource_ids_by_relationship_index' ) ||
        ! function_exists( 'tnt_get_resource_card_data' ) ||
        ! defined( 'TNT_RESOURCE_RESOURCE_INDEX_META_KEY' )
    ) {
        return array();
    }

    $direct_ids = tnt_get_resource_related_resource_ids( $resource_id, true );
    $incoming_ids = tnt_get_resource_ids_by_relationship_index(
        TNT_RESOURCE_RESOURCE_INDEX_META_KEY,
        $resource_id
    );

    $ordered_ids = array();

    foreach ( array_merge( $direct_ids, $incoming_ids ) as $related_id ) {
        $related_id = absint( $related_id );

        if (
            ! $related_id ||
            $related_id === $resource_id ||
            in_array( $related_id, $ordered_ids, true ) ||
            'resource' !== get_post_type( $related_id ) ||
            'publish' !== get_post_status( $related_id )
        ) {
            continue;
        }

        $ordered_ids[] = $related_id;

        if ( count( $ordered_ids ) >= $limit ) {
            break;
        }
    }

    $resources = array();

    foreach ( $ordered_ids as $related_id ) {
        $card_data = tnt_get_resource_card_data( $related_id );

        if ( ! empty( $card_data ) ) {
            $resources[] = $card_data;
        }
    }

    return $resources;
}