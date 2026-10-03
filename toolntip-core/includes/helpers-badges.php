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

    $pricing = tnt_get_tool_meta( $tool, 'pricing', '' );

    if ( ! empty( $pricing ) ) {

        $badges[] = array(
            'label' => $pricing,
            'class' => 'pricing',
        );

    }

    /*
     * Tool Type
     */

    $tool_type = tnt_get_tool_meta( $tool, 'tool_type', '' );

    if ( ! empty( $tool_type ) ) {

        $badges[] = array(
            'label' => $tool_type,
            'class' => 'tool-type',
        );

    }

    /*
     * Platform
     */

    $platforms = tnt_get_tool_meta( $tool, 'platform', '' );

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