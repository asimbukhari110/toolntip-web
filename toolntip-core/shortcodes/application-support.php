<?php
/**
 * Internal Application supporting-content shortcode.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_application_support_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'post_id'   => 0,
            'tool_slug' => '',
        ),
        is_array( $atts ) ? $atts : array(),
        'tnt_application_support'
    );

    $args = array();
    if ( ! empty( $atts['post_id'] ) ) {
        $args['post_id'] = absint( $atts['post_id'] );
    }
    if ( '' !== trim( (string) $atts['tool_slug'] ) ) {
        $args['tool_slug'] = sanitize_title( (string) $atts['tool_slug'] );
    }

    return tnt_render_application_support( $args );
}
add_shortcode( 'tnt_application_support', 'tnt_application_support_shortcode' );
