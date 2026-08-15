<?php
/**
 * Tool Application Page Composition Shortcodes.
 *
 * Thin shortcode adapters over the ToolNTip Core application-page
 * renderer API.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [tnt_internal_tool_top]
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_internal_tool_top_shortcode( $atts ) {

    $tool = tnt_tool_shell_shortcode_tool(
        $atts,
        'tnt_internal_tool_top'
    );

    return $tool
        ? tnt_render_internal_tool_top( $tool )
        : '';
}

/**
 * Shortcode: [tnt_internal_tool_bottom]
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_internal_tool_bottom_shortcode( $atts ) {

    $tool = tnt_tool_shell_shortcode_tool(
        $atts,
        'tnt_internal_tool_bottom'
    );

    return $tool
        ? tnt_render_internal_tool_bottom( $tool )
        : '';
}

/**
 * Shortcode: [tnt_external_tool_page]
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_external_tool_page_shortcode( $atts ) {

    $tool = tnt_tool_shell_shortcode_tool(
        $atts,
        'tnt_external_tool_page'
    );

    return $tool
        ? tnt_render_external_tool_page( $tool )
        : '';
}

add_shortcode(
    'tnt_internal_tool_top',
    'tnt_internal_tool_top_shortcode'
);

add_shortcode(
    'tnt_internal_tool_bottom',
    'tnt_internal_tool_bottom_shortcode'
);

add_shortcode(
    'tnt_external_tool_page',
    'tnt_external_tool_page_shortcode'
);
