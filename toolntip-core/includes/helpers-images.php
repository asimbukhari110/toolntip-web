<?php
/**
 * Image Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns Tool logo.
 *
 * @param WP_Post $tool
 * @return array
 */
function tnt_get_tool_logo( $tool ) {

    $logo = get_field( 'tool_logo', $tool->ID );

    if ( empty( $logo ) ) {

		return array(
			'url'         => '',
			'alt'         => '',
			'width'       => '',
			'height'      => '',
			'placeholder' => true,
		);

    }

		return array(
			'url'         => $logo['url'] ?? '',
			'alt'         => $logo['alt'] ?? '',
			'width'       => $logo['width'] ?? '',
			'height'      => $logo['height'] ?? '',
			'placeholder' => false,
		);

}