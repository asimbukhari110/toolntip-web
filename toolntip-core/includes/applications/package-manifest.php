<?php
/**
 * ToolNTip Application Package Manifest.
 *
 * Defines and validates the declarative manifest contract used by external
 * ToolNTip client-side application packages. This layer performs no ZIP
 * extraction, installation, activation or filesystem mutation.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Current application package manifest schema version.
 *
 * @return int
 */
function tnt_get_application_package_manifest_schema_version() {
    return 1;
}

/**
 * Return file extensions permitted inside a client-side application package.
 *
 * PHP and other server-executable payloads are intentionally excluded.
 *
 * @return array
 */
function tnt_get_application_package_allowed_extensions() {
    return array(
        'html',
        'css',
        'js',
        'json',
        'svg',
        'png',
        'jpg',
        'jpeg',
        'gif',
        'webp',
        'avif',
        'ico',
        'txt',
        'md',
    );
}

/**
 * Normalize and validate a package-relative file path.
 *
 * @param string $path Relative path declared by a package.
 * @return string|WP_Error Normalized path or error.
 */
function tnt_normalize_application_package_relative_path( $path ) {
    $path = trim( wp_normalize_path( (string) $path ) );

    if ( '' === $path ) {
        return new WP_Error(
            'tnt_app_package_empty_path',
            __( 'Application package file path cannot be empty.', 'toolntip-core' )
        );
    }

    if ( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $path ) || 0 === strpos( $path, '/' ) || preg_match( '#^[a-z]:/#i', $path ) ) {
        return new WP_Error(
            'tnt_app_package_absolute_path',
            __( 'Application package file paths must be relative.', 'toolntip-core' )
        );
    }

    $parts = explode( '/', $path );

    foreach ( $parts as $part ) {
        if ( '' === $part || '.' === $part || '..' === $part ) {
            return new WP_Error(
                'tnt_app_package_unsafe_path',
                __( 'Application package file path contains an unsafe path segment.', 'toolntip-core' )
            );
        }

        if ( false !== strpos( $part, "\0" ) || preg_match( '/[<>:"|?*\\\\]/', $part ) ) {
            return new WP_Error(
                'tnt_app_package_invalid_path_characters',
                __( 'Application package file path contains invalid characters.', 'toolntip-core' )
            );
        }
    }

    return implode( '/', $parts );
}

/**
 * Validate a package file path against the client-side extension allowlist.
 *
 * @param string $path Relative package path.
 * @return string|WP_Error Normalized path or error.
 */
function tnt_validate_application_package_file_path( $path ) {
    $normalized = tnt_normalize_application_package_relative_path( $path );

    if ( is_wp_error( $normalized ) ) {
        return $normalized;
    }

    $extension = strtolower( pathinfo( $normalized, PATHINFO_EXTENSION ) );

    if ( '' === $extension || ! in_array( $extension, tnt_get_application_package_allowed_extensions(), true ) ) {
        return new WP_Error(
            'tnt_app_package_disallowed_extension',
            sprintf(
                /* translators: %s: file path. */
                __( 'Application package file type is not allowed: %s', 'toolntip-core' ),
                $normalized
            )
        );
    }

    return $normalized;
}

/**
 * Normalize a manifest-declared list of asset paths.
 *
 * @param mixed  $value          Raw manifest value.
 * @param string $required_ext   Required extension.
 * @param string $error_code     Error code prefix.
 * @return array|WP_Error
 */
function tnt_validate_application_package_asset_list( $value, $required_ext, $error_code ) {
    if ( null === $value ) {
        return array();
    }

    if ( ! is_array( $value ) ) {
        return new WP_Error(
            $error_code . '_not_array',
            __( 'Application package asset declaration must be an array.', 'toolntip-core' )
        );
    }

    $validated = array();

    foreach ( $value as $path ) {
        $path = tnt_validate_application_package_file_path( $path );

        if ( is_wp_error( $path ) ) {
            return $path;
        }

        if ( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) !== $required_ext ) {
            return new WP_Error(
                $error_code . '_wrong_extension',
                sprintf(
                    /* translators: 1: asset path, 2: required extension. */
                    __( 'Application package asset %1$s must use the .%2$s extension.', 'toolntip-core' ),
                    $path,
                    $required_ext
                )
            );
        }

        $validated[] = $path;
    }

    return array_values( array_unique( $validated ) );
}

/**
 * Validate and normalize an application package manifest array.
 *
 * The normalized result is safe configuration only. It does not prove that
 * referenced files exist; archive/file existence verification belongs to the
 * transactional installer stage.
 *
 * @param mixed $manifest Decoded manifest data.
 * @return array|WP_Error Normalized manifest or validation error.
 */
function tnt_validate_application_package_manifest( $manifest ) {
    if ( ! is_array( $manifest ) ) {
        return new WP_Error(
            'tnt_app_manifest_not_object',
            __( 'Application package manifest must be a JSON object.', 'toolntip-core' )
        );
    }

    $manifest = wp_parse_args(
        $manifest,
        array(
            'schema'       => 0,
            'id'           => '',
            'name'         => '',
            'version'      => '',
            'runtime'      => array(),
            'assets'       => array(),
            'page'         => array(),
            'capabilities' => array(),
        )
    );

    $schema = absint( $manifest['schema'] );

    if ( tnt_get_application_package_manifest_schema_version() !== $schema ) {
        return new WP_Error(
            'tnt_app_manifest_schema_unsupported',
            sprintf(
                /* translators: 1: supplied schema version, 2: supported schema version. */
                __( 'Application package manifest schema %1$d is not supported. Expected schema %2$d.', 'toolntip-core' ),
                $schema,
                tnt_get_application_package_manifest_schema_version()
            )
        );
    }

    $id = strtolower( trim( (string) $manifest['id'] ) );

    if ( '' === $id || ! preg_match( '/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/', $id ) || sanitize_key( $id ) !== $id ) {
        return new WP_Error(
            'tnt_app_manifest_invalid_id',
            __( 'Application package ID must use lowercase letters, numbers, hyphens or underscores only.', 'toolntip-core' )
        );
    }

    $name = sanitize_text_field( (string) $manifest['name'] );

    if ( '' === $name ) {
        return new WP_Error(
            'tnt_app_manifest_missing_name',
            __( 'Application package manifest must define a name.', 'toolntip-core' )
        );
    }

    $version = trim( (string) $manifest['version'] );

    if ( '' === $version || ! preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version ) ) {
        return new WP_Error(
            'tnt_app_manifest_invalid_version',
            __( 'Application package version must use semantic versioning such as 1.0.0 or 1.0.0-beta.1.', 'toolntip-core' )
        );
    }

    $runtime = is_array( $manifest['runtime'] ) ? $manifest['runtime'] : array();
    $runtime = wp_parse_args(
        $runtime,
        array(
            'entry'             => '',
            'supported_layouts' => array(),
            'default_layout'    => '',
        )
    );

    $entry = tnt_validate_application_package_file_path( $runtime['entry'] );

    if ( is_wp_error( $entry ) ) {
        return $entry;
    }

    if ( 'html' !== strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ) ) {
        return new WP_Error(
            'tnt_app_manifest_runtime_entry_extension',
            __( 'Application runtime entry file must be an HTML file.', 'toolntip-core' )
        );
    }

    $allowed_layouts   = tnt_get_application_workspace_layouts();
    $supported_layouts = is_array( $runtime['supported_layouts'] ) ? $runtime['supported_layouts'] : array();
    $supported_layouts = array_values(
        array_unique(
            array_filter(
                array_map(
                    static function ( $layout ) use ( $allowed_layouts ) {
                        $layout = sanitize_key( (string) $layout );
                        return in_array( $layout, $allowed_layouts, true ) ? $layout : '';
                    },
                    $supported_layouts
                )
            )
        )
    );

    if ( empty( $supported_layouts ) ) {
        return new WP_Error(
            'tnt_app_manifest_missing_layouts',
            __( 'Application package must declare at least one supported workspace layout.', 'toolntip-core' )
        );
    }

    $default_layout = sanitize_key( (string) $runtime['default_layout'] );

    if ( '' === $default_layout ) {
        $default_layout = $supported_layouts[0];
    }

    if ( ! in_array( $default_layout, $supported_layouts, true ) ) {
        return new WP_Error(
            'tnt_app_manifest_invalid_default_layout',
            __( 'Application package default layout must be one of its supported layouts.', 'toolntip-core' )
        );
    }

    $assets = is_array( $manifest['assets'] ) ? $manifest['assets'] : array();
    $assets = wp_parse_args(
        $assets,
        array(
            'styles'  => array(),
            'scripts' => array(),
        )
    );

    $styles = tnt_validate_application_package_asset_list( $assets['styles'], 'css', 'tnt_app_manifest_styles' );
    if ( is_wp_error( $styles ) ) {
        return $styles;
    }

    $scripts = tnt_validate_application_package_asset_list( $assets['scripts'], 'js', 'tnt_app_manifest_scripts' );
    if ( is_wp_error( $scripts ) ) {
        return $scripts;
    }

    $page = is_array( $manifest['page'] ) ? $manifest['page'] : array();
    $page = wp_parse_args(
        $page,
        array(
            'title' => '',
            'slug'  => '',
        )
    );

    $page_title = sanitize_text_field( (string) $page['title'] );
    $page_slug  = sanitize_title( (string) $page['slug'] );

    if ( '' === $page_title ) {
        return new WP_Error(
            'tnt_app_manifest_missing_page_title',
            __( 'Application package must define the Draft runtime Page title.', 'toolntip-core' )
        );
    }

    if ( '' === $page_slug ) {
        return new WP_Error(
            'tnt_app_manifest_missing_page_slug',
            __( 'Application package must define the Draft runtime Page slug.', 'toolntip-core' )
        );
    }

    if ( trim( (string) $page['slug'] ) !== $page_slug ) {
        return new WP_Error(
            'tnt_app_manifest_invalid_page_slug',
            __( 'Application package Page slug must already be a valid lowercase WordPress slug.', 'toolntip-core' )
        );
    }

    $capabilities = is_array( $manifest['capabilities'] ) ? $manifest['capabilities'] : array();
    $capabilities = array_values(
        array_unique(
            array_filter(
                array_map(
                    static function ( $capability ) {
                        $capability = sanitize_key( (string) $capability );
                        return '' !== $capability ? $capability : '';
                    },
                    $capabilities
                )
            )
        )
    );

    return array(
        'schema'  => $schema,
        'id'      => $id,
        'name'    => $name,
        'version' => $version,
        'runtime' => array(
            'entry'             => $entry,
            'supported_layouts' => $supported_layouts,
            'default_layout'    => $default_layout,
        ),
        'assets' => array(
            'styles'  => $styles,
            'scripts' => $scripts,
        ),
        'page' => array(
            'title' => $page_title,
            'slug'  => $page_slug,
        ),
        'capabilities' => $capabilities,
    );
}

/**
 * Decode and validate a manifest JSON string.
 *
 * @param string $json Raw manifest JSON.
 * @return array|WP_Error Normalized manifest or validation error.
 */
function tnt_validate_application_package_manifest_json( $json ) {
    if ( ! is_string( $json ) || '' === trim( $json ) ) {
        return new WP_Error(
            'tnt_app_manifest_empty_json',
            __( 'Application package manifest JSON is empty.', 'toolntip-core' )
        );
    }

    $manifest = json_decode( $json, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error(
            'tnt_app_manifest_invalid_json',
            sprintf(
                /* translators: %s: JSON parser error. */
                __( 'Application package manifest contains invalid JSON: %s', 'toolntip-core' ),
                json_last_error_msg()
            )
        );
    }

    return tnt_validate_application_package_manifest( $manifest );
}
