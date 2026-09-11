<?php
/**
 * ToolNTip Internal Application Runtime Registry.
 *
 * Provides the trusted registry used by ToolNTip Core to register,
 * discover and validate internal application runtimes.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the allowed application workspace layouts.
 *
 * @return array
 */
function tnt_get_application_workspace_layouts() {
    return array(
        'split',
        'stacked',
        'form-result',
        'single',
    );
}

/**
 * Validate and normalize a runtime definition.
 *
 * Runtime definitions are developer-controlled trusted configuration.
 * Editorial/application content must not be stored in the runtime registry.
 *
 * @param string $runtime_id Runtime identifier.
 * @param array  $definition Runtime definition.
 * @return array|WP_Error Normalized definition or validation error.
 */
function tnt_validate_application_runtime_definition( $runtime_id, $definition ) {
    $runtime_id = sanitize_key( $runtime_id );

    if ( '' === $runtime_id ) {
        return new WP_Error(
            'tnt_invalid_runtime_id',
            __( 'Application runtime ID is invalid.', 'toolntip-core' )
        );
    }

    if ( ! is_array( $definition ) ) {
        return new WP_Error(
            'tnt_invalid_runtime_definition',
            __( 'Application runtime definition must be an array.', 'toolntip-core' )
        );
    }

    $definition = wp_parse_args(
        $definition,
        array(
            'label'             => '',
            'supported_layouts' => array(),
            'default_layout'    => '',
            'renderer'          => null,
            'assets'            => array(),
            'capabilities'      => array(),
        )
    );

    $label = sanitize_text_field( (string) $definition['label'] );

    if ( '' === $label ) {
        return new WP_Error(
            'tnt_runtime_missing_label',
            __( 'Application runtime must define a label.', 'toolntip-core' )
        );
    }

    $allowed_layouts   = tnt_get_application_workspace_layouts();
    $supported_layouts = is_array( $definition['supported_layouts'] )
        ? $definition['supported_layouts']
        : array();

    $supported_layouts = array_values(
        array_unique(
            array_filter(
                array_map(
                    static function ( $layout ) use ( $allowed_layouts ) {
                        $layout = sanitize_key( (string) $layout );

                        return in_array( $layout, $allowed_layouts, true )
                            ? $layout
                            : '';
                    },
                    $supported_layouts
                )
            )
        )
    );

    if ( empty( $supported_layouts ) ) {
        return new WP_Error(
            'tnt_runtime_missing_layouts',
            __( 'Application runtime must support at least one valid workspace layout.', 'toolntip-core' )
        );
    }

    $default_layout = sanitize_key( (string) $definition['default_layout'] );

    if ( '' === $default_layout ) {
        $default_layout = $supported_layouts[0];
    }

    if ( ! in_array( $default_layout, $supported_layouts, true ) ) {
        return new WP_Error(
            'tnt_runtime_invalid_default_layout',
            __( 'Application runtime default layout must be one of its supported layouts.', 'toolntip-core' )
        );
    }

    $renderer = $definition['renderer'];

    if ( null !== $renderer && ! is_callable( $renderer ) ) {
        return new WP_Error(
            'tnt_runtime_invalid_renderer',
            __( 'Application runtime renderer is not callable.', 'toolntip-core' )
        );
    }

    return array(
        'id'                => $runtime_id,
        'label'             => $label,
        'supported_layouts' => $supported_layouts,
        'default_layout'    => $default_layout,
        'renderer'          => $renderer,
        'assets'            => is_array( $definition['assets'] ) ? $definition['assets'] : array(),
        'capabilities'      => is_array( $definition['capabilities'] ) ? $definition['capabilities'] : array(),
    );
}

/**
 * Get the internal application runtime registry.
 *
 * @return array
 */
function tnt_get_application_runtimes() {
    if ( ! isset( $GLOBALS['tnt_application_runtimes'] ) || ! is_array( $GLOBALS['tnt_application_runtimes'] ) ) {
        $GLOBALS['tnt_application_runtimes'] = array();
    }

    return $GLOBALS['tnt_application_runtimes'];
}

/**
 * Register an internal application runtime.
 *
 * @param string $runtime_id Runtime identifier.
 * @param array  $definition Runtime definition.
 * @return true|WP_Error True on success or validation error.
 */
function tnt_register_application_runtime( $runtime_id, $definition ) {
    $runtime_id = sanitize_key( $runtime_id );
    $validated  = tnt_validate_application_runtime_definition( $runtime_id, $definition );

    if ( is_wp_error( $validated ) ) {
        return $validated;
    }

    if ( ! isset( $GLOBALS['tnt_application_runtimes'] ) || ! is_array( $GLOBALS['tnt_application_runtimes'] ) ) {
        $GLOBALS['tnt_application_runtimes'] = array();
    }

    if ( isset( $GLOBALS['tnt_application_runtimes'][ $runtime_id ] ) ) {
        return new WP_Error(
            'tnt_runtime_already_registered',
            __( 'Application runtime is already registered.', 'toolntip-core' )
        );
    }

    $GLOBALS['tnt_application_runtimes'][ $runtime_id ] = $validated;

    return true;
}

/**
 * Get one registered application runtime.
 *
 * @param string $runtime_id Runtime identifier.
 * @return array|null
 */
function tnt_get_application_runtime( $runtime_id ) {
    $runtime_id = sanitize_key( $runtime_id );

    if ( '' === $runtime_id ) {
        return null;
    }

    $runtimes = tnt_get_application_runtimes();

    return isset( $runtimes[ $runtime_id ] ) ? $runtimes[ $runtime_id ] : null;
}

/**
 * Determine whether an application runtime is registered.
 *
 * @param string $runtime_id Runtime identifier.
 * @return bool
 */
function tnt_application_runtime_exists( $runtime_id ) {
    return null !== tnt_get_application_runtime( $runtime_id );
}
