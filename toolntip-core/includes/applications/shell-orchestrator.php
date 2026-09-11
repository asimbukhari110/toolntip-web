<?php
/**
 * ToolNTip Internal Application Shell Orchestrator.
 *
 * Composes a resolved internal-application context into a controlled Core-owned
 * shell. This file is intentionally dormant until a caller explicitly invokes
 * the shell renderer; merely loading it does not change existing Tool pages.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the public shell state derived from an application context.
 *
 * @param array|null $context Resolved application context.
 * @return string
 */
function tnt_get_application_shell_state( $context ) {
    if ( ! is_array( $context ) ) {
        return 'unavailable';
    }

    if ( 'ready' === ( $context['status'] ?? '' ) && tnt_application_context_is_renderable( $context ) ) {
        return 'ready';
    }

    return 'unavailable';
}

/**
 * Render controlled unavailable feedback for an internal application.
 *
 * Detailed WP_Error messages are deliberately not exposed to visitors. The
 * error remains available in the resolved context for privileged diagnostics.
 *
 * @param array|null $context Resolved application context.
 * @return string
 */
function tnt_render_application_unavailable( $context = null ) {
    return '<div class="tnt-application-feedback tnt-application-feedback--unavailable" role="status">'
        . '<p>' . esc_html__( 'This application is temporarily unavailable. Please try again later.', 'toolntip-core' ) . '</p>'
        . '</div>';
}

/**
 * Render the compact Core-owned application identity region.
 *
 * @param array $context Resolved application context.
 * @return string
 */
function tnt_render_application_identity( $context ) {
    $tool = isset( $context['tool'] ) && $context['tool'] instanceof WP_Post
        ? $context['tool']
        : null;

    if ( ! $tool ) {
        return '';
    }

    $icon    = tnt_render_tool_shell_icon( $tool );
    $title   = trim( (string) get_the_title( $tool ) );
    $tagline = tnt_get_tool_shell_tagline( $tool );
    $meta    = tnt_render_tool_shell_meta( $tool );

    if ( '' === $icon && '' === $title && '' === $tagline && '' === $meta ) {
        return '';
    }

    $output = '<header class="tnt-application-shell__identity">';

    if ( '' !== $icon ) {
        $output .= '<div class="tnt-application-shell__icon">' . $icon . '</div>';
    }

    $output .= '<div class="tnt-application-shell__identity-content">';

    if ( '' !== $title ) {
        $output .= '<h1 class="tnt-application-shell__title">' . esc_html( $title ) . '</h1>';
    }

    if ( '' !== $tagline ) {
        $output .= '<p class="tnt-application-shell__tagline">' . esc_html( $tagline ) . '</p>';
    }

    if ( '' !== $meta ) {
        $output .= '<div class="tnt-application-shell__meta">' . $meta . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    $output .= '</div></header>';

    return $output;
}

/**
 * Execute the trusted renderer for a resolved runtime.
 *
 * Runtime output is treated as trusted application markup because renderer
 * callbacks can only originate from the code-controlled runtime registry.
 * Runtime-specific user input remains the runtime's validation responsibility.
 *
 * @param array $context Resolved application context.
 * @return string
 */
function tnt_render_application_runtime( $context ) {
    if ( ! tnt_application_context_is_renderable( $context ) ) {
        return tnt_render_application_unavailable( $context );
    }

    $renderer = $context['runtime']['renderer'];

    try {
        $output = call_user_func( $renderer, $context );
    } catch ( Throwable $throwable ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'ToolNTip application runtime failure: ' . $throwable->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        return tnt_render_application_unavailable( $context );
    }

    if ( ! is_string( $output ) || '' === trim( $output ) ) {
        return tnt_render_application_unavailable( $context );
    }

    return $output;
}

/**
 * Render the Core-owned internal application shell.
 *
 * The shell ends after the post-runtime monetization region. Supporting Tool
 * content remains outside this function and continues to use existing Core
 * renderers. Disabled Tools return an empty string so legacy/non-application
 * Tool rendering remains untouched.
 *
 * @param array $args Tool-shell context arguments.
 * @return string
 */
function tnt_render_application_shell( $args = array() ) {
    $context = tnt_resolve_application_context( $args );

    if ( ! is_array( $context ) || empty( $context['enabled'] ) ) {
        return '';
    }

    $tool  = $context['tool'];
    $state = tnt_get_application_shell_state( $context );

    $output = '<section class="tnt-application-shell tnt-application-shell--' . esc_attr( $state ) . '"';
    $output .= ' data-runtime="' . esc_attr( (string) ( $context['runtime_id'] ?? '' ) ) . '"';
    $output .= ' data-layout="' . esc_attr( (string) ( $context['workspace_layout'] ?? '' ) ) . '">';

    $identity = tnt_render_application_identity( $context );
    if ( '' !== $identity ) {
        $output .= $identity;
    }

    $pre_runtime = tnt_render_monetization_placement( 'internal-hero', $tool );
    if ( '' !== $pre_runtime ) {
        $output .= '<div class="tnt-application-shell__monetization tnt-application-shell__monetization--before">' . $pre_runtime . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    $output .= '<div class="tnt-application-shell__runtime">';
    $output .= tnt_render_application_runtime( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $output .= '</div>';

    $post_runtime = tnt_render_monetization_placement( 'internal-after-app', $tool );
    if ( '' !== $post_runtime ) {
        $output .= '<div class="tnt-application-shell__monetization tnt-application-shell__monetization--after">' . $post_runtime . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    $output .= '</section>';

    return $output;
}
