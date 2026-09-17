<?php
/**
 * Internal Application Shell shortcode.
 *
 * Provides a controlled integration surface for WEB-007.7 testing without
 * changing the canonical Tool template or legacy Elementor application path.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [tnt_application_shell]
 *
 * Optional attributes mirror the existing Tool shell resolver contract.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_application_shell_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'post_id'   => 0,
            'tool_slug' => '',
        ),
        is_array( $atts ) ? $atts : array(),
        'tnt_application_shell'
    );

    $args = array();

    if ( ! empty( $atts['post_id'] ) ) {
        $args['post_id'] = absint( $atts['post_id'] );
    }

    if ( '' !== trim( (string) $atts['tool_slug'] ) ) {
        $args['tool_slug'] = sanitize_title( (string) $atts['tool_slug'] );
    }

    return tnt_render_application_shell( $args );
}
add_shortcode( 'tnt_application_shell', 'tnt_application_shell_shortcode' );
