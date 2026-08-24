<?php
/**
 * AIOSEO integration for ToolNTip Resources.
 *
 * Adds the canonical Resource Topic presentation URLs to AIOSEO's Additional
 * Pages sitemap without changing the native shared tool_category permalinks.
 *
 * WEB-007.4 / 4.7-D
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the latest published Resource modification time for Topic terms.
 *
 * A single grouped query keeps sitemap generation bounded when multiple
 * Resource Topics exist. Only published Resources participate in the result.
 *
 * @param int[] $term_ids Tool Category term IDs.
 * @return array<int,string> Term ID => GMT modified timestamp.
 */
function tnt_aioseo_resource_topic_lastmod_map( $term_ids ) {

    global $wpdb;

    $term_ids = array_values(
        array_unique(
            array_filter(
                array_map( 'absint', (array) $term_ids )
            )
        )
    );

    if ( empty( $term_ids ) ) {
        return array();
    }

    $placeholders = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );

    $sql = "SELECT tt.term_id, MAX(p.post_modified_gmt) AS lastmod
        FROM {$wpdb->term_taxonomy} AS tt
        INNER JOIN {$wpdb->term_relationships} AS tr
            ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->posts} AS p
            ON p.ID = tr.object_id
        WHERE tt.taxonomy = %s
            AND p.post_type = %s
            AND p.post_status = %s
            AND tt.term_id IN ({$placeholders})
        GROUP BY tt.term_id";

    $prepared = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql,
        array_merge(
            array( 'tool_category', 'resource', 'publish' ),
            $term_ids
        )
    );

    $rows = $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

    if ( empty( $rows ) ) {
        return array();
    }

    $lastmods = array();

    foreach ( $rows as $row ) {
        $term_id = isset( $row['term_id'] ) ? absint( $row['term_id'] ) : 0;
        $lastmod = isset( $row['lastmod'] ) ? sanitize_text_field( $row['lastmod'] ) : '';

        if ( $term_id && '' !== $lastmod && '0000-00-00 00:00:00' !== $lastmod ) {
            $lastmods[ $term_id ] = $lastmod;
        }
    }

    return $lastmods;
}

/**
 * Add canonical Resource Topic URLs to AIOSEO's Additional Pages sitemap.
 *
 * The underlying tool_category taxonomy is intentionally shared by Tools and
 * Resources. Its native /tool-category/{slug}/ URLs remain untouched. This
 * integration adds only the Resource-context /resources/topic/{slug}/ aliases
 * for terms actually used by published Resources.
 *
 * @param array<int,array<string,mixed>> $pages Existing Additional Pages.
 * @return array<int,array<string,mixed>>
 */
function tnt_aioseo_add_resource_topic_sitemap_pages( $pages ) {

    if ( ! is_array( $pages ) || ! function_exists( 'tnt_resource_hub_topic_terms' ) || ! function_exists( 'tnt_resource_hub_topic_url' ) ) {
        return is_array( $pages ) ? $pages : array();
    }

    $topics = tnt_resource_hub_topic_terms();

    if ( empty( $topics ) ) {
        return $pages;
    }

    $term_ids = array();

    foreach ( $topics as $topic ) {
        if ( $topic instanceof WP_Term && 'tool_category' === $topic->taxonomy ) {
            $term_ids[] = (int) $topic->term_id;
        }
    }

    $lastmods = tnt_aioseo_resource_topic_lastmod_map( $term_ids );
    $existing = array();

    foreach ( $pages as $page ) {
        if ( is_array( $page ) && ! empty( $page['loc'] ) ) {
            $existing[ untrailingslashit( esc_url_raw( (string) $page['loc'] ) ) ] = true;
        }
    }

    foreach ( $topics as $topic ) {
        if ( ! $topic instanceof WP_Term || 'tool_category' !== $topic->taxonomy ) {
            continue;
        }

        $url = tnt_resource_hub_topic_url( $topic );

        if ( '' === $url ) {
            continue;
        }

        $normalized_url = untrailingslashit( esc_url_raw( $url ) );

        if ( isset( $existing[ $normalized_url ] ) ) {
            continue;
        }

        $entry = array(
            'loc'        => esc_url_raw( $url ),
            'changefreq' => 'monthly',
            'priority'   => (float) 0.5,
        );

        if ( ! empty( $lastmods[ $topic->term_id ] ) ) {
            $entry['lastmod'] = $lastmods[ $topic->term_id ];
        }

        $pages[] = $entry;
        $existing[ $normalized_url ] = true;
    }

    return $pages;
}
add_filter( 'aioseo_sitemap_additional_pages', 'tnt_aioseo_add_resource_topic_sitemap_pages' );
