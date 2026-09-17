<?php
/**
 * AIOSEO editor compatibility for ToolNTip editorial post types.
 *
 * ToolNTip does not own or duplicate SEO metadata. This integration keeps the
 * Tool and Resource post types compatible with AIOSEO's native block/classic
 * editor surfaces by guaranteeing REST exposure and keeping AIOSEO's native
 * editor metabox visible when it is registered by AIOSEO.
 *
 * WEB-007.8 / 8.3-H.2
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return post types that use AIOSEO as the canonical on-page SEO editor.
 *
 * @return string[]
 */
function tnt_aioseo_editor_post_types() {
    return array( 'tool', 'resource' );
}

/**
 * Guarantee REST support for ToolNTip editorial post types.
 *
 * AIOSEO integrates its editor/REST data with custom post types that are
 * exposed through the WordPress REST API. Resource is registered by Core;
 * Tool may be registered by an external CPT provider, so this filter handles
 * both without taking ownership of the Tool CPT registration.
 *
 * @param array  $args      Post type registration arguments.
 * @param string $post_type Post type key.
 * @return array
 */
function tnt_aioseo_editor_post_type_args( $args, $post_type ) {

    if ( ! in_array( $post_type, tnt_aioseo_editor_post_types(), true ) ) {
        return $args;
    }

    $args['show_in_rest'] = true;

    return $args;
}
add_filter( 'register_post_type_args', 'tnt_aioseo_editor_post_type_args', 20, 2 );

/**
 * Keep AIOSEO's native editor metabox at normal high visibility on ToolNTip
 * editorial screens. This changes placement only; AIOSEO still owns all data,
 * tabs, capabilities, licensing and feature availability.
 *
 * @param string $priority Existing AIOSEO metabox priority.
 * @return string
 */
function tnt_aioseo_editor_metabox_priority( $priority ) {

    if ( ! is_admin() ) {
        return $priority;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

    if ( $screen && in_array( $screen->post_type, tnt_aioseo_editor_post_types(), true ) ) {
        return 'high';
    }

    return $priority;
}
add_filter( 'aioseo_post_metabox_priority', 'tnt_aioseo_editor_metabox_priority', 20 );

/**
 * Do not let an old per-user Screen Options preference silently hide AIOSEO's
 * native metabox on Tool and Resource edit screens.
 *
 * This does not create an SEO UI, alter AIOSEO options or duplicate metadata;
 * it only removes AIOSEO-owned boxes from WordPress' hidden-metabox list for
 * the two editorial post types where ToolNTip explicitly requires SEO access.
 *
 * @param string[]  $hidden Hidden metabox IDs.
 * @param WP_Screen $screen Current admin screen.
 * @return string[]
 */
function tnt_aioseo_editor_unhide_metaboxes( $hidden, $screen ) {

    if ( ! is_array( $hidden ) || ! $screen || ! in_array( $screen->post_type, tnt_aioseo_editor_post_types(), true ) ) {
        return $hidden;
    }

    return array_values(
        array_filter(
            $hidden,
            static function ( $metabox_id ) {
                return false === stripos( (string) $metabox_id, 'aioseo' );
            }
        )
    );
}
add_filter( 'hidden_meta_boxes', 'tnt_aioseo_editor_unhide_metaboxes', 20, 2 );

/**
 * Add a small compatibility flag for administrators so QA can distinguish a
 * ToolNTip integration problem from AIOSEO feature/license configuration.
 * The notice is intentionally shown only when AIOSEO itself is unavailable.
 */
function tnt_aioseo_editor_missing_plugin_notice() {

    if ( ! current_user_can( 'manage_options' ) || function_exists( 'aioseo' ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

    if ( ! $screen || ! in_array( $screen->post_type, tnt_aioseo_editor_post_types(), true ) ) {
        return;
    }

    echo '<div class="notice notice-warning"><p>';
    echo esc_html__( 'ToolNTip SEO editing for this content type is delegated to All in One SEO, but AIOSEO is not currently available. ToolNTip does not create duplicate SEO metadata.', 'toolntip-core' );
    echo '</p></div>';
}
add_action( 'admin_notices', 'tnt_aioseo_editor_missing_plugin_notice' );
