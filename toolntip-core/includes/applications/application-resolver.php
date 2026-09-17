<?php
/**
 * ToolNTip Internal Application Resolver.
 *
 * Resolves a Tool CPT record into a normalized, presentation-neutral
 * application context. The resolver is deliberately safe when application
 * configuration fields do not yet exist: unconfigured Tools remain ordinary
 * Tools and no runtime is activated implicitly.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the application configuration field names used by Core.
 *
 * These names form the storage contract for the ACF fields introduced in the
 * later application-configuration implementation step.
 *
 * @return array
 */
function tnt_get_application_config_fields() {
    return array(
        'enabled'          => 'internal_application',
        'runtime_module'   => 'runtime_module',
        'workspace_layout' => 'workspace_layout',
    );
}

/**
 * Read one Tool application configuration value.
 *
 * ACF remains the authoring layer. Direct post-meta fallback keeps Core's
 * resolver presentation-neutral and allows the same stored values to be used
 * by future capability-controlled publishing workflows.
 *
 * @param int    $tool_id    Tool post ID.
 * @param string $field_name Field/meta name.
 * @return mixed
 */
function tnt_get_tool_application_config_value( $tool_id, $field_name ) {
    $tool_id    = absint( $tool_id );
    $field_name = sanitize_key( $field_name );

    if ( $tool_id <= 0 || '' === $field_name ) {
        return null;
    }

    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $field_name, $tool_id );

        if ( null !== $value && false !== $value ) {
            return $value;
        }
    }

    return get_post_meta( $tool_id, $field_name, true );
}

/**
 * Normalize an application-enabled value to boolean.
 *
 * @param mixed $value Stored value.
 * @return bool
 */
function tnt_normalize_internal_application_enabled( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return 1 === (int) $value;
    }

    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

/**
 * Get normalized internal-application configuration for a Tool.
 *
 * @param WP_Post|int|string $tool Tool object, ID or slug.
 * @return array|null
 */
function tnt_get_tool_application_config( $tool ) {
    $tool = tnt_get_tool( $tool );

    if ( ! $tool instanceof WP_Post || 'tool' !== $tool->post_type ) {
        return null;
    }

    $fields = tnt_get_application_config_fields();

    $enabled = tnt_normalize_internal_application_enabled(
        tnt_get_tool_application_config_value( $tool->ID, $fields['enabled'] )
    );

    $runtime_id = sanitize_key(
        (string) tnt_get_tool_application_config_value( $tool->ID, $fields['runtime_module'] )
    );

    $workspace_layout = sanitize_key(
        (string) tnt_get_tool_application_config_value( $tool->ID, $fields['workspace_layout'] )
    );

    return array(
        'enabled'          => $enabled,
        'runtime_id'       => $runtime_id,
        'workspace_layout' => $workspace_layout,
    );
}

/**
 * Determine whether a Tool is explicitly enabled as an internal application.
 *
 * @param WP_Post|int|string $tool Tool object, ID or slug.
 * @return bool
 */
function tnt_is_internal_application( $tool ) {
    $config = tnt_get_tool_application_config( $tool );

    return is_array( $config ) && ! empty( $config['enabled'] );
}

/**
 * Resolve the effective workspace layout for a registered runtime.
 *
 * An empty Tool override means "use the runtime default". A non-empty override
 * must be both globally allowed and explicitly supported by that runtime.
 *
 * @param array  $runtime           Registered runtime definition.
 * @param string $workspace_layout Optional Tool-level layout override.
 * @return string|WP_Error
 */
function tnt_resolve_application_workspace_layout( $runtime, $workspace_layout = '' ) {
    if ( ! is_array( $runtime ) ) {
        return new WP_Error(
            'tnt_application_invalid_runtime',
            __( 'Application runtime configuration is invalid.', 'toolntip-core' )
        );
    }

    $supported_layouts = isset( $runtime['supported_layouts'] ) && is_array( $runtime['supported_layouts'] )
        ? $runtime['supported_layouts']
        : array();

    $default_layout = isset( $runtime['default_layout'] )
        ? sanitize_key( (string) $runtime['default_layout'] )
        : '';

    $workspace_layout = sanitize_key( (string) $workspace_layout );

    if ( '' === $workspace_layout ) {
        if ( '' !== $default_layout && in_array( $default_layout, $supported_layouts, true ) ) {
            return $default_layout;
        }

        return new WP_Error(
            'tnt_application_missing_default_layout',
            __( 'Application runtime does not provide a valid default workspace layout.', 'toolntip-core' )
        );
    }

    if ( ! in_array( $workspace_layout, tnt_get_application_workspace_layouts(), true ) ) {
        return new WP_Error(
            'tnt_application_unknown_layout',
            __( 'Application workspace layout is not recognized.', 'toolntip-core' )
        );
    }

    if ( ! in_array( $workspace_layout, $supported_layouts, true ) ) {
        return new WP_Error(
            'tnt_application_unsupported_layout',
            __( 'Application runtime does not support the selected workspace layout.', 'toolntip-core' )
        );
    }

    return $workspace_layout;
}

/**
 * Resolve a Tool into a normalized internal-application context.
 *
 * Resolution never treats stored runtime values as executable paths or code.
 * Runtime IDs are lookup keys into the trusted Core registry only.
 *
 * Returned status values:
 * - disabled: Tool is not explicitly enabled as an internal application.
 * - unavailable: Tool is enabled but runtime/layout configuration is invalid.
 * - ready: registered runtime and effective layout resolved successfully.
 *
 * @param array $args Tool-shell context arguments.
 * @return array|null Null only when no Tool context can be resolved.
 */
function tnt_resolve_application_context( $args = array() ) {
    $tool = tnt_resolve_tool_shell_context( $args );

    if ( ! $tool instanceof WP_Post ) {
        return null;
    }

    $config = tnt_get_tool_application_config( $tool );

    if ( ! is_array( $config ) ) {
        return null;
    }

    /*
     * On a provisioned package-backed Application Page, the existing
     * _tnt_tool_context_id relationship supplies Tool identity while the
     * Page's governed package identity supplies the runtime ID. This avoids a
     * duplicate linkage/runtime field and keeps ordinary Tool Detail requests
     * on the existing Tool-authored application configuration contract.
     */
    if ( function_exists( 'tnt_get_current_application_package_id' ) ) {
        $package_id = tnt_get_current_application_package_id();
        $page_id    = absint( get_queried_object_id() );
        if ( ! $page_id ) { $page_id = absint( get_the_ID() ); }
        $linked_tool = $package_id ? tnt_get_application_package_linked_tool( $page_id ) : null;
        if ( $linked_tool instanceof WP_Post && (int) $linked_tool->ID === (int) $tool->ID ) {
            $config['enabled']    = true;
            $config['runtime_id'] = $package_id;
        }
    }

    $context = array(
        'tool'             => $tool,
        'tool_id'          => (int) $tool->ID,
        'enabled'          => (bool) $config['enabled'],
        'runtime_id'       => (string) $config['runtime_id'],
        'runtime'          => null,
        'workspace_layout' => '',
        'status'           => 'disabled',
        'error'            => null,
        'error_code'       => '',
    );

    if ( ! $context['enabled'] ) {
        return $context;
    }

    if ( '' === $context['runtime_id'] ) {
        $context['status'] = 'unavailable';
        $context['error_code'] = 'runtime_missing';
        $context['error']  = new WP_Error(
            'tnt_application_runtime_missing',
            __( 'No application runtime is configured for this Tool.', 'toolntip-core' )
        );

        return $context;
    }

    $runtime = tnt_get_application_runtime( $context['runtime_id'] );

    if ( null === $runtime ) {
        $context['status'] = 'unavailable';
        $context['error_code'] = 'runtime_unregistered';
        $context['error']  = new WP_Error(
            'tnt_application_runtime_unregistered',
            __( 'The configured application runtime is unavailable.', 'toolntip-core' )
        );

        return $context;
    }

    $context['runtime'] = $runtime;

    $workspace_layout = tnt_resolve_application_workspace_layout(
        $runtime,
        $config['workspace_layout']
    );

    if ( is_wp_error( $workspace_layout ) ) {
        $context['status'] = 'unavailable';
        $context['error_code'] = 'workspace_invalid';
        $context['error']  = $workspace_layout;

        return $context;
    }

    $context['workspace_layout'] = $workspace_layout;
    $context['status']           = 'ready';

    return $context;
}

/**
 * Determine whether a resolved application context is ready for rendering.
 *
 * Render-time callable validation is intentional even though registration also
 * validates non-null renderers. A metadata-only runtime definition must never
 * be executed until it supplies a trusted callable renderer.
 *
 * @param array|null $context Application context.
 * @return bool
 */
function tnt_application_context_is_renderable( $context ) {
    if ( ! is_array( $context ) || 'ready' !== $context['status'] ) {
        return false;
    }

    if ( empty( $context['runtime'] ) || ! is_array( $context['runtime'] ) ) {
        return false;
    }

    return isset( $context['runtime']['renderer'] )
        && is_callable( $context['runtime']['renderer'] );
}
