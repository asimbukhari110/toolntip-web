<?php
/**
 * Hero Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_tool_hero( $tool ) {

    return array(

        'tagline' => get_field( 'tool_tagline', $tool->ID ),

        'rating' => (float) get_field( 'editor_rating', $tool->ID ),

        'reviews' => (int) get_field( 'review_count', $tool->ID ),

        'verified' => (bool) get_field( 'verified', $tool->ID ),

    );

}