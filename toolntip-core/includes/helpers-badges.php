<?php
/**
 * Badge Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns all badges for a Tool.
 *
 * @param WP_Post $tool
 * @return array
 */
function tnt_get_tool_badges( $tool ) {

    $badges = array();

    /*
     * Pricing
     */

    $pricing = get_field( 'pricing', $tool->ID );

    if ( ! empty( $pricing ) ) {

        $badges[] = array(
            'label' => $pricing,
            'class' => 'pricing',
        );

    }

    /*
     * Tool Type
     */

    $tool_type = get_field( 'tool_type', $tool->ID );

    if ( ! empty( $tool_type ) ) {

        $badges[] = array(
            'label' => $tool_type,
            'class' => 'tool-type',
        );

    }

    /*
     * Platform
     */

    $platforms = get_field( 'platform', $tool->ID );

    if ( ! empty( $platforms ) ) {

        foreach ( $platforms as $platform ) {

            $badges[] = array(
                'label' => $platform,
                'class' => 'platform',
            );

        }

    }

    return $badges;

}