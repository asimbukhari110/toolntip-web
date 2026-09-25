<?php
/**
 * Product collection shortcode.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_products_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'limit' => '6',
        ),
        $atts,
        'tnt_products'
    );

    $limit = intval( $atts['limit'] );
    if ( 0 === $limit ) {
        $limit = -1;
    }

    return tnt_render_product_collection(
        array(
            'posts_per_page' => $limit,
        )
    );
}
add_shortcode( 'tnt_products', 'tnt_products_shortcode' );
