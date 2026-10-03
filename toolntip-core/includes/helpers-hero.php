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

        'tagline' => tnt_get_tool_meta( $tool, 'tool_tagline', '' ),

        'rating' => (float) tnt_get_tool_meta( $tool, 'editor_rating', '' ),

        'reviews' => (int) tnt_get_tool_meta( $tool, 'review_count', '' ),

        'verified' => (bool) tnt_get_tool_meta( $tool, 'verified', '' ),

    );

}