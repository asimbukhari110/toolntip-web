<?php
/**
 * Rating Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Rating Data.
 *
 * @param WP_Post $tool
 * @return array
 */
function tnt_get_tool_rating( $tool ) {

    $rating = (float) get_field( 'editor_rating', $tool->ID );

    $reviews = (int) get_field( 'review_count', $tool->ID );

	return array(

		'value'      => $rating,

		'reviews'    => $reviews,

		'max'        => 5,

		'percentage' => ( $rating / 5 ) * 100,

	);
}