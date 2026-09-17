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
    return '<div class="tnt-application-feedback tnt-application-feedback--unavailable" role="status" aria-live="polite">'
        . '<p>' . esc_html__( 'This application is temporarily unavailable. Please try again later.', 'toolntip-core' ) . '</p>'
        . '</div>';
}

/**
 * Log a runtime failure without exposing visitor input, filesystem paths,
 * callback details, stack traces, or exception messages.
 *
 * @param array  $context Resolved application context.
 * @param string $reason  Controlled failure reason.
 * @return void
 */
function tnt_log_application_runtime_failure( $context, $reason ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }

    $tool_id    = isset( $context['tool_id'] ) ? absint( $context['tool_id'] ) : 0;
    $runtime_id = isset( $context['runtime_id'] ) ? sanitize_key( (string) $context['runtime_id'] ) : '';
    $reason     = sanitize_key( (string) $reason );

    error_log( sprintf( 'ToolNTip application runtime failure [tool=%d runtime=%s reason=%s]', $tool_id, $runtime_id, $reason ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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

    $runtime  = $context['runtime'];
    $renderer = $runtime['renderer'];

    if ( function_exists( 'tnt_enqueue_application_runtime_assets' ) ) {
        tnt_enqueue_application_runtime_assets( $runtime );
    }

    try {
        $output = call_user_func( $renderer, $context );
    } catch ( Throwable $throwable ) {
        tnt_log_application_runtime_failure( $context, 'renderer_exception' );

        return tnt_render_application_unavailable( $context );
    }

    if ( ! is_string( $output ) ) {
        tnt_log_application_runtime_failure( $context, 'renderer_invalid_output' );
        return tnt_render_application_unavailable( $context );
    }

    if ( '' === trim( $output ) ) {
        tnt_log_application_runtime_failure( $context, 'renderer_empty_output' );
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

    // Shell CSS is needed only when an enabled application shell is emitted.
    wp_enqueue_style( 'tnt-application-shell' );

    $tool  = $context['tool'];
    $state = tnt_get_application_shell_state( $context );

    $output = '<section class="tnt-application-shell tnt-application-shell--' . esc_attr( $state ) . '"';
    $output .= ' data-runtime="' . esc_attr( (string) ( $context['runtime_id'] ?? '' ) ) . '"';
    $output .= ' data-layout="' . esc_attr( (string) ( $context['workspace_layout'] ?? '' ) ) . '">';

    $identity = tnt_render_application_identity( $context );
    $contextual = function_exists( 'tnt_render_monetization_placement' )
        ? tnt_render_monetization_placement( 'internal-contextual', $tool, array( 'variant' => 'contextual' ) )
        : '';

    if ( '' !== $identity || '' !== $contextual ) {
        $hero_class = 'tnt-application-shell__hero';
        if ( '' !== $contextual ) {
            $hero_class .= ' tnt-application-shell__hero--has-contextual';
        }

        $output .= '<div class="' . esc_attr( $hero_class ) . '">';
        if ( '' !== $identity ) {
            $output .= $identity;
        }
        if ( '' !== $contextual ) {
            $output .= '<aside class="tnt-application-shell__contextual" aria-label="' . esc_attr__( 'Contextual promotional content', 'toolntip-core' ) . '">' . $contextual . '</aside>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        $output .= '</div>';
    }

    $pre_runtime = function_exists( 'tnt_render_monetization_placement' )
        ? tnt_render_monetization_placement( 'internal-hero', $tool, array( 'variant' => 'hero' ) )
        : '';
    if ( '' !== $pre_runtime ) {
        $output .= '<div class="tnt-application-shell__monetization tnt-application-shell__monetization--before">' . $pre_runtime . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    $output .= '<div class="tnt-application-shell__runtime">';
    $output .= tnt_render_application_runtime( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $output .= '</div>';

    $post_runtime = function_exists( 'tnt_render_monetization_placement' )
        ? tnt_render_monetization_placement( 'internal-after-app', $tool )
        : '';
    if ( '' !== $post_runtime ) {
        $output .= '<div class="tnt-application-shell__monetization tnt-application-shell__monetization--after">' . $post_runtime . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    $output .= '</section>';

    return $output;
}
