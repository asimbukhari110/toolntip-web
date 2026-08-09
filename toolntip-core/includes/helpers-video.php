<?php
/**
 * Video Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Demo Video.
 *
 * @param WP_Post $tool
 * @return array
 */
function tnt_get_tool_video( $tool ) {

    $url = get_field( 'demo_video', $tool->ID );

    if ( empty( $url ) ) {
        return array();
    }

    return array(

        'url' => esc_url( $url ),

        'embed' => wp_oembed_get(
            $url,
            array(
                'width' => 900,
            )
        ),

    );

}