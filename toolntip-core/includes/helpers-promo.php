<?php
/**
 * Promotional & Monetization Placement Helpers.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_monetization_settings() {
    $defaults = function_exists( 'tnt_get_default_monetization_settings' )
        ? tnt_get_default_monetization_settings()
        : array( 'global_loader_code' => '', 'placements' => array() );

    $stored = get_option( 'tnt_monetization_settings', null );

    if ( is_array( $stored ) ) {
        return wp_parse_args( $stored, $defaults );
    }

    $settings = $defaults;
    $legacy_internal = absint( get_option( 'tnt_promo_internal_after_app_tool_id', 0 ) );
    $legacy_external = absint( get_option( 'tnt_promo_external_after_hero_tool_id', 0 ) );

    if ( $legacy_internal > 0 ) {
        $settings['placements']['internal-after-app'] = array(
            'mode' => 'tool', 'tool_ids' => array( $legacy_internal ), 'custom_code' => '',
        );
    }

    if ( $legacy_external > 0 ) {
        $settings['placements']['external-after-hero'] = array(
            'mode' => 'tool', 'tool_ids' => array( $legacy_external ), 'custom_code' => '',
        );
    }

    return $settings;
}

function tnt_get_monetization_placement_config( $placement ) {
    $placement = sanitize_key( $placement );
    if ( '' === $placement ) {
        return array();
    }

    $settings = tnt_get_monetization_settings();
    if ( empty( $settings['placements'][ $placement ] ) || ! is_array( $settings['placements'][ $placement ] ) ) {
        return array();
    }

    $config = $settings['placements'][ $placement ];
    $mode = sanitize_key( $config['mode'] ?? 'disabled' );
    if ( ! in_array( $mode, array( 'disabled', 'tool', 'custom' ), true ) ) {
        $mode = 'disabled';
    }

    $tool_ids = array();
    if ( ! empty( $config['tool_ids'] ) && is_array( $config['tool_ids'] ) ) {
        foreach ( $config['tool_ids'] as $tool_id ) {
            $tool_id = absint( $tool_id );
            if ( $tool_id > 0 ) {
                $tool_ids[] = $tool_id;
            }
        }
    }

    return array(
        'mode' => $mode,
        'tool_ids' => array_values( array_unique( $tool_ids ) ),
        'custom_code' => (string) ( $config['custom_code'] ?? '' ),
    );
}

function tnt_get_promo_current_tool_id( $tool ) {
    if ( $tool instanceof WP_Post ) {
        return absint( $tool->ID );
    }

    if ( is_array( $tool ) ) {
        if ( ! empty( $tool['id'] ) ) {
            return absint( $tool['id'] );
        }
        if ( ! empty( $tool['post'] ) && $tool['post'] instanceof WP_Post ) {
            return absint( $tool['post']->ID );
        }
    }

    return 0;
}

function tnt_get_eligible_tool_promo_ids( $placement, $tool = array() ) {
    $config = tnt_get_monetization_placement_config( $placement );
    if ( 'tool' !== ( $config['mode'] ?? 'disabled' ) ) {
        return array();
    }

    $current_tool_id = tnt_get_promo_current_tool_id( $tool );
    $eligible_ids = array();

    foreach ( $config['tool_ids'] ?? array() as $tool_id ) {
        $tool_id = absint( $tool_id );
        if ( $tool_id <= 0 || ( $current_tool_id > 0 && $current_tool_id === $tool_id ) ) {
            continue;
        }

        $promoted_tool = tnt_get_tool( $tool_id );
        if ( ! $promoted_tool instanceof WP_Post || 'tool' !== $promoted_tool->post_type || 'publish' !== $promoted_tool->post_status ) {
            continue;
        }

        $eligible_ids[] = $tool_id;
    }

    return array_values( array_unique( $eligible_ids ) );
}

function tnt_choose_tool_promo_id( $placement, $tool = array() ) {
    $eligible_ids = tnt_get_eligible_tool_promo_ids( $placement, $tool );
    if ( empty( $eligible_ids ) ) {
        return 0;
    }

    $random_key = array_rand( $eligible_ids );
    return absint( $eligible_ids[ $random_key ] );
}

function tnt_get_tool_promo_placement( $placement, $tool = array() ) {
    $promoted_tool_id = tnt_choose_tool_promo_id( $placement, $tool );
    if ( $promoted_tool_id <= 0 ) {
        return array();
    }

    $promoted_tool = tnt_get_tool( $promoted_tool_id );
    if ( ! $promoted_tool instanceof WP_Post ) {
        return array();
    }

    $actions = tnt_get_tool_actions( $promoted_tool );
    $url = '';
    $external = false;

    if ( ! empty( $actions['use_tool']['url'] ) ) {
        $url = (string) $actions['use_tool']['url'];
        $external = ! empty( $actions['use_tool']['external'] );
    }

    if ( '' === $url ) {
        $url = (string) get_permalink( $promoted_tool );
    }

    if ( '' === $url ) {
        return array();
    }

    return array(
        'type'        => 'house-tool',
        'provider'    => 'tool',
        'placement'   => sanitize_key( $placement ),
        'tool_id'     => $promoted_tool->ID,
        'label'       => __( 'Featured Tool', 'toolntip-core' ),
        'eyebrow'     => __( 'ToolNTip', 'toolntip-core' ),
        'title'       => get_the_title( $promoted_tool ),
        'description' => tnt_get_tool_shell_tagline( $promoted_tool ),
        'cta_label'   => __( 'Open Tool', 'toolntip-core' ),
        'url'         => $url,
        'external'    => $external,
        'logo'        => tnt_get_tool_logo( $promoted_tool ),
    );
}

function tnt_get_monetization_placement( $placement, $tool = array() ) {
    $config = tnt_get_monetization_placement_config( $placement );
    $mode = $config['mode'] ?? 'disabled';

    if ( 'disabled' === $mode ) {
        return array();
    }

    if ( 'tool' === $mode ) {
        return tnt_get_tool_promo_placement( $placement, $tool );
    }

    if ( 'custom' === $mode ) {
        $code = trim( (string) ( $config['custom_code'] ?? '' ) );
        if ( '' === $code ) {
            return array();
        }

        return array(
            'type' => 'custom-code',
            'provider' => 'custom',
            'placement' => sanitize_key( $placement ),
            'code' => $code,
        );
    }

    return array();
}

function tnt_get_global_tool_promo_id( $placement ) {
    return tnt_choose_tool_promo_id( $placement, array() );
}

function tnt_get_promo_placement( $placement, $tool = array() ) {
    if ( 'tool-detail-hero' === $placement ) {
        return array(
            'type' => 'house',
            'provider' => 'house',
            'label' => __( 'Featured', 'toolntip-core' ),
            'eyebrow' => __( 'ToolNTip', 'toolntip-core' ),
            'title' => __( 'Discover More Developer Tools', 'toolntip-core' ),
            'description' => __( 'Explore curated tools to improve your development workflow.', 'toolntip-core' ),
            'cta_label' => __( 'Explore Tools', 'toolntip-core' ),
            'url' => home_url( '/tools/' ),
            'external' => false,
        );
    }

    return tnt_get_monetization_placement( $placement, $tool );
}

function tnt_is_monetization_frontend_context() {
    if ( is_admin() ) {
        return false;
    }

    $object_id = get_queried_object_id();
    if ( $object_id <= 0 ) {
        return false;
    }

    if ( 'tool' === get_post_type( $object_id ) ) {
        return true;
    }

    if ( 'page' === get_post_type( $object_id ) ) {
        return absint( get_post_meta( $object_id, '_tnt_tool_context_id', true ) ) > 0;
    }

    return false;
}

function tnt_render_monetization_global_loader() {
    if ( ! tnt_is_monetization_frontend_context() ) {
        return;
    }

    $settings = tnt_get_monetization_settings();
    $code = trim( (string) ( $settings['global_loader_code'] ?? '' ) );
    if ( '' === $code ) {
        return;
    }

    echo "\n<!-- ToolNTip Monetization Loader -->\n";
    echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo "\n<!-- /ToolNTip Monetization Loader -->\n";
}
add_action( 'wp_head', 'tnt_render_monetization_global_loader', 30 );
