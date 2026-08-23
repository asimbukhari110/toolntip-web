<?php
/**
 * Resource Card data helpers.
 *
 * WEB-007.4 / 4.4C.1
 *
 * Focused Resource Card data normalization. This helper intentionally avoids
 * Resource Detail-only relationship/scoring domains and does not own queries
 * or presentation markup.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize one taxonomy term for Resource Card consumption.
 *
 * @param WP_Term $term Taxonomy term.
 * @return array<string,mixed>
 */
function tnt_normalize_resource_card_term( $term ) {

    if ( ! $term instanceof WP_Term ) {
        return array();
    }

    $url = get_term_link( $term );

    return array(
        'id'   => (int) $term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
        'url'  => is_wp_error( $url ) ? '' : $url,
    );
}

/**
 * Return normalized terms for a Resource Card taxonomy.
 *
 * @param int    $resource_id Resource post ID.
 * @param string $taxonomy    Taxonomy name.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_resource_card_terms( $resource_id, $taxonomy ) {

    $terms = get_the_terms( $resource_id, $taxonomy );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array();
    }

    $normalized = array();

    foreach ( $terms as $term ) {
        $data = tnt_normalize_resource_card_term( $term );

        if ( ! empty( $data ) ) {
            $normalized[] = $data;
        }
    }

    return $normalized;
}

/**
 * Resolve the compact identity icon for a Resource.
 *
 * Resolution order is intentionally centralized:
 * 1. Resource-specific icon (`tnt_resource_icon`).
 * 2. The currently configured WordPress Site Icon as the ToolNTip identity fallback.
 *
 * Featured Image is deliberately not used here; it remains editorial media.
 *
 * @param WP_Post|int $resource Resource object or ID.
 * @param string      $size     Registered image size for attachment-backed icons.
 * @return array{url:string,alt:string,source:string}
 */
function tnt_get_resource_icon( $resource, $size = 'thumbnail' ) {

    $resource_id = $resource instanceof WP_Post ? $resource->ID : absint( $resource );

    if ( ! $resource_id || 'resource' !== get_post_type( $resource_id ) ) {
        return array( 'url' => '', 'alt' => '', 'source' => '' );
    }

    $icon_value = get_post_meta( $resource_id, 'tnt_resource_icon', true );
    $icon_id    = 0;

    if ( is_numeric( $icon_value ) ) {
        $icon_id = absint( $icon_value );
    } elseif ( is_array( $icon_value ) && ! empty( $icon_value['ID'] ) ) {
        $icon_id = absint( $icon_value['ID'] );
    }

    if ( $icon_id ) {
        $icon_url = wp_get_attachment_image_url( $icon_id, $size );

        if ( $icon_url ) {
            $icon_alt = trim( (string) get_post_meta( $icon_id, '_wp_attachment_image_alt', true ) );

            return array(
                'url'    => $icon_url,
                'alt'    => $icon_alt ? $icon_alt : get_the_title( $resource_id ),
                'source' => 'resource',
            );
        }
    }

    $site_icon_url = get_site_icon_url( 192 );

    if ( $site_icon_url ) {
        return array(
            'url'    => $site_icon_url,
            'alt'    => get_bloginfo( 'name' ),
            'source' => 'site-icon',
        );
    }

    return array( 'url' => '', 'alt' => '', 'source' => '' );
}

/**
 * Return focused Resource Card data without loading detail-only domains.
 *
 * Accepts a Resource WP_Post object or numeric post ID and fails closed for
 * invalid/non-Resource targets.
 *
 * @param WP_Post|int $resource Resource object or ID.
 * @return array<string,mixed>|null
 */
function tnt_get_resource_card_data( $resource ) {

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

    $thumbnail_id = get_post_thumbnail_id( $resource );
    $image_url    = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';
    $image_alt    = $thumbnail_id ? get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) : '';

    $types  = tnt_get_resource_card_terms( $resource->ID, 'resource_type' );
    $topics = tnt_get_resource_card_terms( $resource->ID, 'tool_category' );
    $tags   = tnt_get_resource_card_terms( $resource->ID, 'resource_tag' );

    return array(
        'id'             => $resource->ID,
        'post'           => $resource,
        'post_id'        => $resource->ID,
        'title'          => get_the_title( $resource ),
        'excerpt'        => get_the_excerpt( $resource ),
        'permalink'      => get_permalink( $resource ),
        'featured'       => (bool) get_post_meta( $resource->ID, 'tnt_resource_featured', true ),
        'icon'           => tnt_get_resource_icon( $resource ),
        'featured_image' => array(
            'url' => $image_url ? $image_url : '',
            'alt' => trim( (string) $image_alt ),
        ),
        'type'           => ! empty( $types ) ? $types[0] : array(),
        'topics'         => $topics,
        'tags'           => $tags,
        'date'           => array(
            'machine' => get_the_date( 'Y-m-d', $resource ),
            'display' => get_the_date( get_option( 'date_format' ), $resource ),
        ),
    );
}
