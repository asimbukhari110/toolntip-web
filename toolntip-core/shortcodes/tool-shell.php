<?php
/**
 * Internal Tool Application Shell Shortcodes.
 *
 * Thin presentation adapters over the ToolNTip Core helper API.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize common Tool shell shortcode attributes.
 *
 * @param array|string $atts Shortcode attributes.
 * @param string       $shortcode Shortcode name.
 * @return array
 */
function tnt_tool_shell_shortcode_atts( $atts, $shortcode ) {
    return shortcode_atts(
        array(
            'post_id'   => 0,
            'tool_slug' => '',
        ),
        $atts,
        $shortcode
    );
}

/**
 * Resolve Tool context for a shortcode call.
 *
 * @param array|string $atts Shortcode attributes.
 * @param string       $shortcode Shortcode name.
 * @return WP_Post|null
 */
function tnt_tool_shell_shortcode_tool( $atts, $shortcode ) {
    return tnt_resolve_tool_shell_context(
        tnt_tool_shell_shortcode_atts( $atts, $shortcode )
    );
}

function tnt_tool_title_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_title' );
    return $tool ? esc_html( get_the_title( $tool ) ) : '';
}

function tnt_tool_tagline_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_tagline' );
    return $tool ? esc_html( tnt_get_tool_shell_tagline( $tool ) ) : '';
}

function tnt_tool_hero_image_shortcode() {
    return tnt_get_tool_shell_hero_image();
}

function tnt_tool_excerpt_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_excerpt' );
    return $tool ? esc_html( tnt_get_tool_shell_excerpt( $tool ) ) : '';
}

function tnt_tool_rating_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_rating' );
    if ( ! $tool ) {
        return '';
    }

    $rating = tnt_get_tool_shell_rating( $tool );
    return $rating > 0 ? esc_html( number_format_i18n( $rating, 1 ) ) : '';
}

function tnt_tool_review_count_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_review_count' );
    return $tool ? esc_html( (string) tnt_get_tool_shell_review_count( $tool ) ) : '';
}

function tnt_tool_verified_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_verified' );
    return $tool ? tnt_render_tool_shell_verified( $tool ) : '';
}

function tnt_tool_category_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_category' );
    if ( ! $tool ) {
        return '';
    }

    $names = tnt_get_tool_shell_term_names( tnt_get_tool_categories( $tool ) );
    return ! empty( $names ) ? esc_html( implode( ', ', $names ) ) : '';
}

function tnt_tool_pricing_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_pricing' );
    return $tool ? esc_html( tnt_get_tool_shell_pricing( $tool ) ) : '';
}

function tnt_tool_platform_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_platform' );
    if ( ! $tool ) {
        return '';
    }

    $platforms = tnt_get_tool_shell_platforms( $tool );
    return ! empty( $platforms ) ? esc_html( implode( ', ', $platforms ) ) : '';
}

function tnt_tool_developer_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_developer' );
    return $tool ? esc_html( tnt_get_tool_shell_developer( $tool ) ) : '';
}

function tnt_tool_type_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_type' );
    return $tool ? esc_html( tnt_get_tool_shell_type( $tool ) ) : '';
}

function tnt_tool_tags_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_tags' );
    if ( ! $tool ) {
        return '';
    }

    $names = tnt_get_tool_shell_term_names( tnt_get_tool_tags( $tool ) );
    return ! empty( $names ) ? esc_html( implode( ', ', $names ) ) : '';
}

function tnt_tool_url_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_url' );
    return $tool ? esc_url( tnt_get_tool_shell_url( $tool ) ) : '';
}

function tnt_tool_permalink_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_permalink' );
    return $tool ? esc_url( tnt_get_tool_shell_permalink( $tool ) ) : '';
}

function tnt_tool_meta_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_meta' );
    return $tool ? tnt_render_tool_shell_meta( $tool ) : '';
}

function tnt_tool_features_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_features' );
    return $tool ? tnt_render_tool_shell_list( tnt_get_tool_features( $tool ), 'tnt-tool-features' ) : '';
}

function tnt_tool_pros_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_pros' );
    return $tool ? tnt_render_tool_shell_list( tnt_get_tool_pros( $tool ), 'tnt-tool-pros' ) : '';
}

function tnt_tool_cons_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_cons' );
    return $tool ? tnt_render_tool_shell_list( tnt_get_tool_cons( $tool ), 'tnt-tool-cons' ) : '';
}

function tnt_tool_description_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_description' );
    if ( ! $tool ) {
        return '';
    }

    $content = tnt_get_tool_shell_description( $tool );
    return $content !== '' ? wp_kses_post( apply_filters( 'the_content', $content ) ) : '';
}

function tnt_tool_screenshots_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_screenshots' );
    return $tool ? tnt_render_tool_shell_screenshots( $tool ) : '';
}

function tnt_tool_video_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_video' );
    return $tool ? tnt_render_tool_shell_video( $tool ) : '';
}

function tnt_tool_faq_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, 'tnt_tool_faq' );
    return $tool ? tnt_render_tool_shell_faq( $tool ) : '';
}

function tnt_tool_icon_shortcode( $atts ) {

    $tool = tnt_tool_shell_shortcode_tool(
        $atts,
        'tnt_tool_icon'
    );

    return $tool
        ? tnt_render_tool_shell_icon( $tool )
        : '';
}

add_shortcode( 'tnt_tool_title', 'tnt_tool_title_shortcode' );
add_shortcode( 'tnt_tool_tagline', 'tnt_tool_tagline_shortcode' );
add_shortcode( 'tnt_tool_hero_image', 'tnt_tool_hero_image_shortcode' );
add_shortcode( 'tnt_tool_excerpt', 'tnt_tool_excerpt_shortcode' );
add_shortcode( 'tnt_tool_rating', 'tnt_tool_rating_shortcode' );
add_shortcode( 'tnt_tool_review_count', 'tnt_tool_review_count_shortcode' );
add_shortcode( 'tnt_tool_verified', 'tnt_tool_verified_shortcode' );
add_shortcode( 'tnt_tool_category', 'tnt_tool_category_shortcode' );
add_shortcode( 'tnt_tool_pricing', 'tnt_tool_pricing_shortcode' );
add_shortcode( 'tnt_tool_platform', 'tnt_tool_platform_shortcode' );
add_shortcode( 'tnt_tool_developer', 'tnt_tool_developer_shortcode' );
add_shortcode( 'tnt_tool_type', 'tnt_tool_type_shortcode' );
add_shortcode( 'tnt_tool_tags', 'tnt_tool_tags_shortcode' );
add_shortcode( 'tnt_tool_url', 'tnt_tool_url_shortcode' );
add_shortcode( 'tnt_tool_permalink', 'tnt_tool_permalink_shortcode' );
add_shortcode( 'tnt_tool_meta', 'tnt_tool_meta_shortcode' );
add_shortcode( 'tnt_tool_features', 'tnt_tool_features_shortcode' );
add_shortcode( 'tnt_tool_pros', 'tnt_tool_pros_shortcode' );
add_shortcode( 'tnt_tool_cons', 'tnt_tool_cons_shortcode' );
add_shortcode( 'tnt_tool_description', 'tnt_tool_description_shortcode' );
add_shortcode( 'tnt_tool_screenshots', 'tnt_tool_screenshots_shortcode' );
add_shortcode( 'tnt_tool_video', 'tnt_tool_video_shortcode' );
add_shortcode( 'tnt_tool_faq', 'tnt_tool_faq_shortcode' );
add_shortcode( 'tnt_tool_icon', 'tnt_tool_icon_shortcode');
