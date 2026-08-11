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
 * Get Tool rating data.
 *
 * Editor and community ratings remain distinct.
 * Legacy top-level keys continue to represent the editor rating
 * for backward compatibility with existing Tool Card presentation.
 *
 * @param WP_Post $tool Tool post.
 * @return array
 */
function tnt_get_tool_rating( $tool ) {

    $editor_value = (float) get_field(
        'editor_rating',
        $tool->ID
    );

    $community = tnt_get_tool_community_rating( $tool );

    $editor = array(
        'value'      => $editor_value,
        'max'        => 5,
        'percentage' => $editor_value > 0
            ? ( $editor_value / 5 ) * 100
            : 0,
    );

    return array(

        /*
         * Backward-compatible editor-rating contract.
         */
        'value'      => $editor['value'],
        'reviews'    => $community['count'],
        'max'        => $editor['max'],
        'percentage' => $editor['percentage'],

        /*
         * Explicit rating authorities.
         */
        'editor'     => $editor,
        'community'  => $community,

    );
}