<?php
/**
 * Reusable Monetization Ad Unit Shortcodes.
 *
 * These shortcodes are intended for manual placement in Elementor pages.
 * They read only administrator-managed ad-unit code from ToolNTip's
 * centralized monetization settings and accept no arbitrary HTML attributes.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render one manual ad-unit shortcode.
 *
 * @param string $unit Ad-unit key.
 *
 * @return string
 */
function tnt_monetization_ad_shortcode_output( $unit ) {
    if ( ! function_exists( 'tnt_get_monetization_ad_unit_markup' ) ) {
        return '';
    }

    return tnt_get_monetization_ad_unit_markup( $unit, 'elementor' );
}

/**
 * Shortcode: [tnt_ad_leaderboard]
 *
 * @return string
 */
function tnt_ad_leaderboard_shortcode() {
    return tnt_monetization_ad_shortcode_output( 'leaderboard' );
}

/**
 * Shortcode: [tnt_ad_rectangle]
 *
 * @return string
 */
function tnt_ad_rectangle_shortcode() {
    return tnt_monetization_ad_shortcode_output( 'rectangle' );
}

/**
 * Shortcode: [tnt_ad_horizontal]
 *
 * @return string
 */
function tnt_ad_horizontal_shortcode() {
    return tnt_monetization_ad_shortcode_output( 'horizontal' );
}

/**
 * Shortcode: [tnt_ad_sidebar]
 *
 * @return string
 */
function tnt_ad_sidebar_shortcode() {
    return tnt_monetization_ad_shortcode_output( 'sidebar' );
}

/**
 * Shortcode: [tnt_ad_mobile]
 *
 * @return string
 */
function tnt_ad_mobile_shortcode() {
    return tnt_monetization_ad_shortcode_output( 'mobile' );
}

add_shortcode( 'tnt_ad_leaderboard', 'tnt_ad_leaderboard_shortcode' );
add_shortcode( 'tnt_ad_rectangle', 'tnt_ad_rectangle_shortcode' );
add_shortcode( 'tnt_ad_horizontal', 'tnt_ad_horizontal_shortcode' );
add_shortcode( 'tnt_ad_sidebar', 'tnt_ad_sidebar_shortcode' );
add_shortcode( 'tnt_ad_mobile', 'tnt_ad_mobile_shortcode' );
