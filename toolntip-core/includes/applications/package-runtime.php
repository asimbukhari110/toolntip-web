<?php
/**
 * ToolNTip Installed Package Runtime Bridge.
 *
 * Converts a validated installed client-side package into the existing trusted
 * ToolNTip application runtime registry contract. Package code never supplies
 * PHP callbacks: Core owns the renderer and asset registration.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Option containing the selected version for package-backed runtimes. */
function tnt_get_application_package_active_versions() {
    $value = get_option( 'tnt_application_package_active_versions', array() );
    return is_array( $value ) ? $value : array();
}

/** Build a public URL for a validated package-relative file. */
function tnt_get_application_package_file_url( $application_id, $version, $relative_path ) {
    $application_id = sanitize_key( (string) $application_id );
    $version        = (string) $version;
    $relative_path  = tnt_normalize_application_package_relative_path( $relative_path );
    if ( '' === $application_id || is_wp_error( $relative_path ) ) {
        return '';
    }
    $record = tnt_get_installed_application_version( $application_id, $version );
    if ( ! is_array( $record ) ) {
        return '';
    }
    $candidate = wp_normalize_path( $record['path'] . '/' . $relative_path );
    $root_real = realpath( $record['path'] );
    $file_real = realpath( $candidate );
    if ( false === $root_real || false === $file_real || ! is_file( $file_real ) || is_link( $candidate ) ) {
        return '';
    }
    $root_real = trailingslashit( wp_normalize_path( $root_real ) );
    $file_real = wp_normalize_path( $file_real );
    if ( 0 !== strpos( $file_real, $root_real ) ) {
        return '';
    }
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) || empty( $uploads['baseurl'] ) ) {
        return '';
    }
    $segments = array_map( 'rawurlencode', explode( '/', $relative_path ) );
    return trailingslashit( $uploads['baseurl'] ) . 'toolntip-applications/' . rawurlencode( $application_id ) . '/' . rawurlencode( $version ) . '/' . implode( '/', $segments );
}

/**
 * Validate runtime HTML as passive markup.
 *
 * Executable/resource-loading elements and inline event handlers are forbidden;
 * JavaScript and CSS must come only from manifest-declared package assets.
 */
function tnt_validate_application_package_runtime_html( $html ) {
    if ( ! is_string( $html ) || '' === trim( $html ) ) {
        return new WP_Error( 'tnt_package_runtime_empty', __( 'Package runtime HTML is empty.', 'toolntip-core' ) );
    }
    if ( preg_match( '/<\s*\/?\s*(script|style|link|base|iframe|object|embed|frame|frameset)\b/i', $html ) ) {
        return new WP_Error( 'tnt_package_runtime_active_markup', __( 'Package runtime HTML contains a prohibited executable or resource-loading element.', 'toolntip-core' ) );
    }
    if ( preg_match( '/\son[a-z0-9_-]+\s*=/i', $html ) ) {
        return new WP_Error( 'tnt_package_runtime_inline_handler', __( 'Package runtime HTML contains an inline event handler.', 'toolntip-core' ) );
    }
    if ( preg_match( '/\s(?:src|href|action|formaction|poster)\s*=\s*(["\'])\s*(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $html ) ) {
        return new WP_Error( 'tnt_package_runtime_external_url', __( 'Package runtime HTML contains an external or absolute resource URL.', 'toolntip-core' ) );
    }
    return $html;
}

/** Core-owned renderer for every package-backed runtime. */
function tnt_render_installed_application_package_runtime( $context ) {
    $runtime = isset( $context['runtime'] ) && is_array( $context['runtime'] ) ? $context['runtime'] : array();
    $package = isset( $runtime['package'] ) && is_array( $runtime['package'] ) ? $runtime['package'] : array();
    if ( empty( $package['id'] ) || empty( $package['version'] ) || empty( $package['entry'] ) ) {
        return '';
    }
    $record = tnt_get_installed_application_version( $package['id'], $package['version'] );
    if ( ! is_array( $record ) || $record['manifest']['runtime']['entry'] !== $package['entry'] ) {
        return '';
    }
    $entry = wp_normalize_path( $record['path'] . '/' . $record['manifest']['runtime']['entry'] );
    if ( ! is_file( $entry ) || is_link( $entry ) ) {
        return '';
    }
    $html = @file_get_contents( $entry );
    $html = tnt_validate_application_package_runtime_html( false === $html ? '' : $html );
    return is_wp_error( $html ) ? '' : $html;
}

/** Register manifest-declared local CSS/JS and return their trusted handles. */
function tnt_register_application_package_assets( $record ) {
    $handles = array( 'styles' => array(), 'scripts' => array() );
    $base = 'tnt-app-' . sanitize_key( $record['id'] ) . '-' . substr( md5( $record['version'] ), 0, 8 );
    foreach ( $record['manifest']['assets']['styles'] as $index => $relative ) {
        $url = tnt_get_application_package_file_url( $record['id'], $record['version'], $relative );
        if ( '' === $url ) {
            return new WP_Error( 'tnt_package_runtime_style_unavailable', __( 'A package runtime stylesheet could not be resolved safely.', 'toolntip-core' ) );
        }
        $handle = $base . '-css-' . ( $index + 1 );
        wp_register_style( $handle, $url, array(), $record['version'] );
        $handles['styles'][] = $handle;
    }
    foreach ( $record['manifest']['assets']['scripts'] as $index => $relative ) {
        $url = tnt_get_application_package_file_url( $record['id'], $record['version'], $relative );
        if ( '' === $url ) {
            return new WP_Error( 'tnt_package_runtime_script_unavailable', __( 'A package runtime script could not be resolved safely.', 'toolntip-core' ) );
        }
        $handle = $base . '-js-' . ( $index + 1 );
        wp_register_script( $handle, $url, array(), $record['version'], true );
        $handles['scripts'][] = $handle;
    }
    return $handles;
}

/** Return request-scoped package runtime bridge results for observability. */
function tnt_get_application_package_runtime_statuses() {
    return isset( $GLOBALS['tnt_application_package_runtime_statuses'] ) && is_array( $GLOBALS['tnt_application_package_runtime_statuses'] )
        ? $GLOBALS['tnt_application_package_runtime_statuses'] : array();
}

/** Register installed package runtimes early enough for all application resolution consumers. */
function tnt_register_installed_application_package_runtimes() {
    $apps = tnt_get_installed_application_packages( true );
    $selected = tnt_get_application_package_active_versions();
    $changed = false;
    $statuses = array();

    foreach ( $apps as $app_id => $app ) {
        $version = isset( $selected[ $app_id ] ) && isset( $app['versions'][ $selected[ $app_id ] ] )
            ? (string) $selected[ $app_id ] : (string) $app['latest_version'];
        $record = $app['versions'][ $version ];
        $entry_html = @file_get_contents( $record['path'] . '/' . $record['manifest']['runtime']['entry'] );
        $html_check = tnt_validate_application_package_runtime_html( false === $entry_html ? '' : $entry_html );
        if ( is_wp_error( $html_check ) ) {
            $statuses[ $app_id ] = array( 'registered' => false, 'version' => $version, 'reason' => $html_check->get_error_code() );
            continue;
        }
        $assets = tnt_register_application_package_assets( $record );
        if ( is_wp_error( $assets ) ) {
            $statuses[ $app_id ] = array( 'registered' => false, 'version' => $version, 'reason' => $assets->get_error_code() );
            continue;
        }
        $result = tnt_register_application_runtime(
            $app_id,
            array(
                'label'             => $record['name'],
                'supported_layouts' => $record['manifest']['runtime']['supported_layouts'],
                'default_layout'    => $record['manifest']['runtime']['default_layout'],
                'renderer'          => 'tnt_render_installed_application_package_runtime',
                'assets'            => $assets,
                'capabilities'      => $record['manifest']['capabilities'],
                'package'           => array(),
            )
        );
        if ( is_wp_error( $result ) ) {
            $statuses[ $app_id ] = array( 'registered' => false, 'version' => $version, 'reason' => $result->get_error_code() );
            continue;
        }
        // Runtime registry normalization intentionally strips unknown keys; attach
        // Core-owned package metadata only after successful trusted registration.
        $GLOBALS['tnt_application_runtimes'][ $app_id ]['package'] = array(
            'id' => $app_id, 'version' => $version, 'entry' => $record['manifest']['runtime']['entry'],
        );
        $statuses[ $app_id ] = array( 'registered' => true, 'version' => $version, 'reason' => '' );
        if ( ! isset( $selected[ $app_id ] ) || $selected[ $app_id ] !== $version ) {
            $selected[ $app_id ] = $version;
            $changed = true;
        }
    }
    $GLOBALS['tnt_application_package_runtime_statuses'] = $statuses;
    if ( $changed ) {
        update_option( 'tnt_application_package_active_versions', $selected, false );
    }
}
add_action( 'init', 'tnt_register_installed_application_package_runtimes', 5 );
