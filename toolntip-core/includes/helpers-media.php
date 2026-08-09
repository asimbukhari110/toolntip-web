<?php
/**
 * Media Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_tool_screenshots( $tool ) {

    $gallery = get_field( 'screenshots', $tool->ID );

    if ( empty( $gallery ) ) {
        return array();
    }

    $images = array();

    foreach ( $gallery as $image ) {

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