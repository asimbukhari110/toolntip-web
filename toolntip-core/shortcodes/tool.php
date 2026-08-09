<?php
/**
 * Tool Shortcode
 *
 * Usage:
 * [tnt_tool slug="json-formatter"]
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render a Tool.
 */
function tnt_tool_shortcode( $atts ) {

    $atts = shortcode_atts(
		array(
			'slug'     => '',
			'template' => 'detail',
		),
		$atts,
		'tnt_tool'
	);

    if ( empty( $atts['slug'] ) ) {
        return '<p>No tool slug provided.</p>';
    }

    $tool = tnt_get_tool_data( $atts['slug'] );

    if ( ! $tool ) {
        return '<p>Tool not found.</p>';
    }

    ob_start();

    $template = sanitize_key( $atts['template'] );

	switch ( $template ) {

		case 'card':

			$template_file = TNT_CORE_PATH .
				'templates/parts/tool-card.php';

			break;

		case 'detail':

		default:

			$template_file = TNT_CORE_PATH . 'templates/tool-detail.php';
			break;

	}

include $template_file;

    return ob_get_clean();
}

add_shortcode( 'tnt_tool', 'tnt_tool_shortcode' );