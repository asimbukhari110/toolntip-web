<?php
/**
 * Button Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the primary button for a Tool.
 *
 * @param WP_Post $tool
 * @return array
 */
function tnt_get_use_tool_button( $tool ) {

    $tool_type = get_field( 'tool_type', $tool->ID );

    $affiliate = get_field( 'affiliate_url', $tool->ID );

    $official = get_field( 'official_website', $tool->ID );

    $button = array(
        'label'    => 'Use Tool',
        'url'      => '',
        'external' => false,
        'disabled' => false,
    );

    if ( $tool_type === 'Internal' ) {

        $button['url'] = home_url( '/tool/' . $tool->post_name . '/' );

    } elseif ( ! empty( $affiliate ) ) {

        $button['url'] = $affiliate;
        $button['external'] = true;

    } elseif ( ! empty( $official ) ) {

        $button['url'] = $official;
        $button['external'] = true;

    } else {

        $button['label'] = 'Coming Soon';
        $button['disabled'] = true;

    }

    return $button;
}