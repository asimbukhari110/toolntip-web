<?php
/**
 * Taxonomy Helper Functions.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize taxonomy terms.
 *
 * @param array|WP_Error $terms Taxonomy terms.
 * @return array
 */
function tnt_normalize_taxonomy_terms( $terms ) {

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array();
    }

    return array_map(
        static function ( $term ) {
            return array(
                'id'   => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        },
        $terms
    );
}

/**
 * Get Tool Categories.
 *
 * @param WP_Post|int $tool Tool object or ID.
 * @return array
 */
function tnt_get_tool_categories( $tool ) {

    $tool_id = $tool instanceof WP_Post ? $tool->ID : (int) $tool;

    return tnt_normalize_taxonomy_terms(
        get_the_terms( $tool_id, 'tool_category' )
    );
}

/**
 * Get Tool Tags.
 *
 * @param WP_Post|int $tool Tool object or ID.
 * @return array
 */
function tnt_get_tool_tags( $tool ) {

    $tool_id = $tool instanceof WP_Post ? $tool->ID : (int) $tool;

    return tnt_normalize_taxonomy_terms(
        get_the_terms( $tool_id, 'tool_tag' )
    );
}