<?php
/**
 * Media Helper.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_get_tool_screenshots( $tool ) {
    $images = array();
    foreach ( tnt_get_tool_screenshot_attachment_data( $tool ) as $image ) {
        $images[] = array(
            'id'     => $image['ID'],
            'url'    => $image['url'],
            'thumb'  => $image['sizes']['medium'],
            'large'  => $image['sizes']['large'],
            'alt'    => $image['alt'],
            'width'  => $image['width'],
            'height' => $image['height'],
        );
    }
    return $images;
}
