<?php
/**
 * Taxonomy Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Tool Categories.
 *
 * @param WP_Post|int $tool Tool object or ID.
 * @return array
 */
function tnt_get_tool_categories( $tool ) {

    $tool_id = $tool instanceof WP_Post ? $tool->ID : (int) $tool;

    $terms = get_the_terms( $tool_id, 'tool_category' );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array();
    }

    return $terms;
}

/**
 * Get Tool Tags.
 *
 * @param WP_Post|int $tool Tool object or ID.
 * @return array
 */
function tnt_get_tool_tags( $tool ) {

    $tool_id = $tool instanceof WP_Post ? $tool->ID : (int) $tool;

    $terms = get_the_terms( $tool_id, 'tool_tag' );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array();
    }

    return $terms;
}