<?php
/**
 * Tool Detail Hero Monetization Placement.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tnt_render_monetization_placement' ) ) {
    return;
}

echo tnt_render_monetization_placement(
    'external-hero',
    $tool,
    array(
        'variant' => 'hero',
    )
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
