<?php
/**
 * ToolNTip Product storage-provider adapter foundation.
 *
 * WordPress remains the control plane: adapters validate provider references,
 * inspect metadata/health and mint a provider-owned download destination. Core
 * never proxies release artifact bytes through WordPress.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize an adapter operation result.
 *
 * @param mixed  $result Operation result.
 * @param string $operation Operation key.
 * @return array|WP_Error
 */
function tnt_normalize_product_provider_result( $result, $operation ) {
    if ( is_wp_error( $result ) ) {
        return $result;
    }
    if ( ! is_array( $result ) ) {
        return new WP_Error( 'tnt_provider_invalid_result', sprintf( __( 'Storage provider returned an invalid result for %s.', 'toolntip-core' ), sanitize_key( $operation ) ) );
    }
    return $result;
}

/**
 * Built-in provider definitions.
 *
 * These definitions establish stable provider identities and API boundaries.
 * Authentication/configuration is intentionally supplied outside Release rows.
 *
 * @return array<string,array>
 */
function tnt_get_builtin_product_provider_adapters() {
    return array(
        'google_drive' => array(
            'key'              => 'google_drive',
            'label'            => __( 'Google Drive', 'toolntip-core' ),
            'api_family'       => 'google_drive_v3',
            'credential_scope' => 'central',
            'delivery_mode'    => 'provider_redirect',
        ),
        'onedrive' => array(
            'key'              => 'onedrive',
            'label'            => __( 'OneDrive', 'toolntip-core' ),
            'api_family'       => 'microsoft_graph_v1',
            'credential_scope' => 'central',
            'delivery_mode'    => 'temporary_provider_url',
        ),
    );
}

/**
 * Return registered provider adapters.
 *
 * Extensions may add operation callbacks with the
 * `tnt_product_provider_adapters` filter. Supported callbacks are:
 * validate_reference, get_metadata, health_check, get_download_destination.
 *
 * @return array<string,array>
 */
function tnt_get_product_provider_adapters() {
    $adapters = tnt_get_builtin_product_provider_adapters();
    $adapters = apply_filters( 'tnt_product_provider_adapters', $adapters );

    if ( ! is_array( $adapters ) ) {
        return array();
    }

    $clean = array();
    foreach ( $adapters as $key => $adapter ) {
        $key = sanitize_key( $key );
        if ( ! $key || ! is_array( $adapter ) ) {
            continue;
        }
        $adapter['key']              = $key;
        $adapter['label']            = isset( $adapter['label'] ) ? sanitize_text_field( $adapter['label'] ) : $key;
        $adapter['api_family']       = isset( $adapter['api_family'] ) ? sanitize_key( $adapter['api_family'] ) : '';
        $adapter['credential_scope'] = 'central';
        $adapter['delivery_mode']    = isset( $adapter['delivery_mode'] ) ? sanitize_key( $adapter['delivery_mode'] ) : 'provider_redirect';
        $clean[ $key ]               = $adapter;
    }
    return $clean;
}

/**
 * Return one provider adapter.
 *
 * @param string $provider Provider key.
 * @return array|null
 */
function tnt_get_product_provider_adapter( $provider ) {
    $provider = sanitize_key( $provider );
    $adapters = tnt_get_product_provider_adapters();
    return isset( $adapters[ $provider ] ) ? $adapters[ $provider ] : null;
}

/**
 * Whether a provider is centrally configured for live API operations.
 *
 * Built-ins deliberately default to false until credentials/configuration are
 * supplied by a governed integration. Secrets are never stored on a Release or
 * Release Location row.
 *
 * @param string $provider Provider key.
 * @return bool
 */
function tnt_product_provider_is_configured( $provider ) {
    $adapter = tnt_get_product_provider_adapter( $provider );
    if ( ! $adapter ) {
        return false;
    }
    if ( isset( $adapter['is_configured'] ) && is_callable( $adapter['is_configured'] ) ) {
        return (bool) call_user_func( $adapter['is_configured'], $adapter );
    }
    return (bool) apply_filters( 'tnt_product_provider_is_configured', false, sanitize_key( $provider ), $adapter );
}

/**
 * Validate a durable provider reference.
 *
 * @param string $provider Provider key.
 * @param string $reference Provider reference.
 * @return array|WP_Error
 */
function tnt_product_provider_validate_reference( $provider, $reference ) {
    $adapter = tnt_get_product_provider_adapter( $provider );
    if ( ! $adapter ) {
        return new WP_Error( 'tnt_provider_not_registered', __( 'Storage provider is not registered.', 'toolntip-core' ) );
    }
    $reference = trim( sanitize_text_field( $reference ) );
    if ( '' === $reference || strlen( $reference ) > 512 ) {
        return new WP_Error( 'tnt_provider_reference_invalid', __( 'A valid provider file reference is required.', 'toolntip-core' ) );
    }
    if ( isset( $adapter['validate_reference'] ) && is_callable( $adapter['validate_reference'] ) ) {
        return tnt_normalize_product_provider_result( call_user_func( $adapter['validate_reference'], $reference, $adapter ), 'validate_reference' );
    }
    return array( 'valid' => true, 'reference' => $reference, 'remote_verified' => false );
}

/**
 * Execute a provider operation that requires central configuration.
 *
 * @param string $provider Provider key.
 * @param string $operation Operation callback key.
 * @param string $reference Provider reference.
 * @param array  $context Optional context.
 * @return array|WP_Error
 */
function tnt_product_provider_operation( $provider, $operation, $reference, $context = array() ) {
    $adapter = tnt_get_product_provider_adapter( $provider );
    if ( ! $adapter ) {
        return new WP_Error( 'tnt_provider_not_registered', __( 'Storage provider is not registered.', 'toolntip-core' ) );
    }
    if ( ! tnt_product_provider_is_configured( $provider ) ) {
        return new WP_Error( 'tnt_provider_not_configured', __( 'Storage provider is not configured for live operations.', 'toolntip-core' ) );
    }
    if ( ! isset( $adapter[ $operation ] ) || ! is_callable( $adapter[ $operation ] ) ) {
        return new WP_Error( 'tnt_provider_operation_unavailable', __( 'Storage provider does not implement this operation.', 'toolntip-core' ) );
    }
    $reference_check = tnt_product_provider_validate_reference( $provider, $reference );
    if ( is_wp_error( $reference_check ) ) {
        return $reference_check;
    }
    return tnt_normalize_product_provider_result( call_user_func( $adapter[ $operation ], $reference, (array) $context, $adapter ), $operation );
}

/** @return array|WP_Error */
function tnt_product_provider_get_metadata( $provider, $reference, $context = array() ) {
    return tnt_product_provider_operation( $provider, 'get_metadata', $reference, $context );
}

/** @return array|WP_Error */
function tnt_product_provider_health_check( $provider, $reference, $context = array() ) {
    return tnt_product_provider_operation( $provider, 'health_check', $reference, $context );
}

/** @return array|WP_Error */
function tnt_product_provider_get_download_destination( $provider, $reference, $context = array() ) {
    $result = tnt_product_provider_operation( $provider, 'get_download_destination', $reference, $context );
    if ( is_wp_error( $result ) ) {
        return $result;
    }
    $url = isset( $result['url'] ) ? esc_url_raw( $result['url'] ) : '';
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        return new WP_Error( 'tnt_provider_download_destination_invalid', __( 'Storage provider did not return a valid download destination.', 'toolntip-core' ) );
    }
    $result['url'] = $url;
    return $result;
}

/**
 * Summarize provider readiness without exposing credentials.
 *
 * @param string $provider Provider key.
 * @return array
 */
function tnt_get_product_provider_readiness( $provider ) {
    $adapter = tnt_get_product_provider_adapter( $provider );
    if ( ! $adapter ) {
        return array( 'registered' => false, 'configured' => false, 'operations' => array() );
    }
    $operations = array();
    foreach ( array( 'validate_reference', 'get_metadata', 'health_check', 'get_download_destination' ) as $operation ) {
        $operations[ $operation ] = isset( $adapter[ $operation ] ) && is_callable( $adapter[ $operation ] );
    }
    return array(
        'registered'    => true,
        'configured'    => tnt_product_provider_is_configured( $provider ),
        'api_family'    => $adapter['api_family'],
        'delivery_mode' => $adapter['delivery_mode'],
        'operations'    => $operations,
    );
}
