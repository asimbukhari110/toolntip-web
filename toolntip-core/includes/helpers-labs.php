<?php
/**
 * ToolNTip Labs semantic helpers.
 *
 * Labs is a discovery context over existing published Internal Tools. This
 * layer intentionally delegates filtering/query construction to the shared
 * Tool collection query engine.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build query arguments for a Labs collection.
 *
 * Internal Tool scope is authoritative and cannot be overridden by callers.
 *
 * @param array $args Labs collection options.
 * @return array
 */
function tnt_build_labs_query_args( $args = array() ) {

    $defaults = array(
        'category' => '',
        'featured' => '',
        'limit'    => 8,
        'orderby'  => 'date',
        'order'    => 'DESC',
    );

    $args = wp_parse_args( $args, $defaults );

    // Labs is, by definition, a collection of first-party Internal Tools.
    $args['type']    = 'internal';
    $args['pricing'] = '';
    $args['tag']     = '';

    return tnt_build_tools_shortcode_query_args( $args );
}

/**
 * Return the canonical Tool archive URL for the Labs empty-state continuation.
 *
 * @return string
 */
function tnt_get_labs_tools_url() {
    $url = get_post_type_archive_link( 'tool' );

    return $url ? $url : home_url( '/tools/' );
}
