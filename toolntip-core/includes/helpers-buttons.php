<?php
/**
 * Tool Action Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determine whether a URL points outside the ToolNTip site.
 *
 * Relative URLs and same-host URLs are treated as internal.
 *
 * @param string $url URL to inspect.
 * @return bool
 */
function tnt_is_external_tool_url( $url ) {

    if ( empty( $url ) ) {
        return false;
    }

    $url_host  = wp_parse_url( $url, PHP_URL_HOST );
    $site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

    if ( empty( $url_host ) || empty( $site_host ) ) {
        return false;
    }

    $url_host  = strtolower( preg_replace( '/^www\./i', '', $url_host ) );
    $site_host = strtolower( preg_replace( '/^www\./i', '', $site_host ) );

    return $url_host !== $site_host;
}

/**
 * Return the transparent action contract for a Tool.
 *
 * The four destinations remain independent:
 * - Use Tool: manually managed functional destination.
 * - Official Website: canonical product/vendor destination.
 * - Partner Link: optional affiliate destination.
 * - View Details: native WordPress Tool permalink.
 *
 * Affiliate data never overrides the primary Use Tool action.
 *
 * @param WP_Post|int|string $tool Tool object, ID or slug.
 * @return array
 */
function tnt_get_tool_actions( $tool ) {

    if ( ! $tool instanceof WP_Post ) {
        $tool = tnt_get_tool( $tool );
    }

    if ( ! $tool ) {
        return array();
    }

    $use_tool_url = trim( (string) get_field( 'use_tool_url', $tool->ID ) );
    $official_url = trim( (string) get_field( 'official_website', $tool->ID ) );
    $affiliate_url = trim( (string) get_field( 'affiliate_url', $tool->ID ) );
    $details_url  = get_permalink( $tool );

    return array(
        'use_tool' => array(
            'label'    => __( 'Use Tool', 'toolntip-core' ),
            'url'      => $use_tool_url,
            'external' => tnt_is_external_tool_url( $use_tool_url ),
            'disabled' => empty( $use_tool_url ),
        ),
        'official' => $official_url
            ? array(
                'label'    => __( 'Official Website', 'toolntip-core' ),
                'url'      => $official_url,
                'external' => tnt_is_external_tool_url( $official_url ),
                'disabled' => false,
            )
            : array(),
        'affiliate' => $affiliate_url
            ? array(
                'label'    => __( 'Partner Link', 'toolntip-core' ),
                'url'      => $affiliate_url,
                'external' => tnt_is_external_tool_url( $affiliate_url ),
                'disabled' => false,
                'rel'      => 'sponsored nofollow noopener noreferrer',
            )
            : array(),
        'details' => array(
            'label'    => __( 'View Details', 'toolntip-core' ),
            'url'      => $details_url,
            'external' => false,
            'disabled' => empty( $details_url ),
        ),
    );
}

/**
 * Backward-compatible accessor for the primary Use Tool button.
 *
 * @param WP_Post|int|string $tool Tool object, ID or slug.
 * @return array
 */
function tnt_get_use_tool_button( $tool ) {

    $actions = tnt_get_tool_actions( $tool );

    return ! empty( $actions['use_tool'] )
        ? $actions['use_tool']
        : array();
}
