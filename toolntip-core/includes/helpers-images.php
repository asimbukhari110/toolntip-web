<?php
/**
 * Image Helper Functions.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_get_tool_logo( $tool ) {
    $image = tnt_get_tool_attachment_data( tnt_get_tool_meta( $tool, 'tool_logo', 0 ) );
    if ( empty( $image ) ) {
        return array(
            'url' => '', 'alt' => '', 'width' => '', 'height' => '', 'placeholder' => true,
        );
    }
    return array(
        'url'         => $image['url'],
        'alt'         => $image['alt'],
        'width'       => $image['width'],
        'height'      => $image['height'],
        'placeholder' => false,
    );
}
