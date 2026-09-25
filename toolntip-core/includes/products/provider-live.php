<?php
/**
 * Live Google Drive and Microsoft OneDrive provider integrations.
 *
 * Secrets/tokens are stored centrally in encrypted WordPress options. Release
 * rows contain only durable provider references and never OAuth credentials.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_product_provider_option_name( $provider ) {
    return 'tnt_product_provider_' . sanitize_key( $provider );
}

function tnt_product_provider_crypto_key() {
    return hash_hmac( 'sha256', 'toolntip-product-provider-v1', wp_salt( 'auth' ), true );
}

function tnt_product_provider_encrypt_secret( $value ) {
    $value = (string) $value;
    if ( '' === $value ) { return ''; }
    if ( ! function_exists( 'openssl_encrypt' ) ) {
        return new WP_Error( 'tnt_provider_crypto_unavailable', __( 'OpenSSL is required to store provider secrets securely.', 'toolntip-core' ) );
    }
    $cipher = 'aes-256-gcm';
    $iv_len = openssl_cipher_iv_length( $cipher );
    try { $iv = random_bytes( $iv_len ); } catch ( Exception $e ) { return new WP_Error( 'tnt_provider_crypto_random_failed', __( 'Secure random generation failed.', 'toolntip-core' ) ); }
    $tag = '';
    $encrypted = openssl_encrypt( $value, $cipher, tnt_product_provider_crypto_key(), OPENSSL_RAW_DATA, $iv, $tag );
    if ( false === $encrypted ) { return new WP_Error( 'tnt_provider_crypto_failed', __( 'Provider secret encryption failed.', 'toolntip-core' ) ); }
    return base64_encode( wp_json_encode( array( 'v' => 1, 'iv' => base64_encode( $iv ), 'tag' => base64_encode( $tag ), 'data' => base64_encode( $encrypted ) ) ) );
}

function tnt_product_provider_decrypt_secret( $value ) {
    $value = (string) $value;
    if ( '' === $value ) { return ''; }
    if ( ! function_exists( 'openssl_decrypt' ) ) { return ''; }
    $decoded = json_decode( base64_decode( $value, true ), true );
    if ( ! is_array( $decoded ) || empty( $decoded['iv'] ) || empty( $decoded['tag'] ) || empty( $decoded['data'] ) ) { return ''; }
    $plain = openssl_decrypt(
        base64_decode( $decoded['data'], true ),
        'aes-256-gcm',
        tnt_product_provider_crypto_key(),
        OPENSSL_RAW_DATA,
        base64_decode( $decoded['iv'], true ),
        base64_decode( $decoded['tag'], true )
    );
    return false === $plain ? '' : (string) $plain;
}

function tnt_get_product_provider_config( $provider, $with_secrets = false ) {
    $provider = sanitize_key( $provider );
    $config = get_option( tnt_product_provider_option_name( $provider ), array() );
    if ( ! is_array( $config ) ) { $config = array(); }
    $defaults = array(
        'client_id' => '', 'client_secret' => '', 'tenant' => 'common',
        'access_token' => '', 'refresh_token' => '', 'expires_at' => 0,
        'connected_at' => '', 'account_label' => '',
    );
    $config = wp_parse_args( $config, $defaults );
    if ( $with_secrets ) {
        foreach ( array( 'client_secret', 'access_token', 'refresh_token' ) as $key ) {
            $config[ $key ] = tnt_product_provider_decrypt_secret( $config[ $key ] );
        }
    } else {
        $config['client_secret'] = ! empty( $config['client_secret'] );
        $config['access_token'] = ! empty( $config['access_token'] );
        $config['refresh_token'] = ! empty( $config['refresh_token'] );
    }
    return $config;
}

function tnt_save_product_provider_config( $provider, $data ) {
    $provider = sanitize_key( $provider );
    if ( ! in_array( $provider, array( 'google_drive', 'onedrive' ), true ) ) { return new WP_Error( 'tnt_provider_invalid', __( 'Unsupported storage provider.', 'toolntip-core' ) ); }
    $existing = tnt_get_product_provider_config( $provider, false );
    $stored = get_option( tnt_product_provider_option_name( $provider ), array() );
    if ( ! is_array( $stored ) ) { $stored = array(); }
    $stored['client_id'] = isset( $data['client_id'] ) ? trim( sanitize_text_field( $data['client_id'] ) ) : (string) ( $stored['client_id'] ?? '' );
    if ( 'onedrive' === $provider ) {
        $tenant = isset( $data['tenant'] ) ? trim( sanitize_text_field( $data['tenant'] ) ) : (string) ( $stored['tenant'] ?? 'common' );
        $stored['tenant'] = $tenant ?: 'common';
    }
    if ( array_key_exists( 'client_secret', $data ) && '' !== trim( (string) $data['client_secret'] ) ) {
        $encrypted = tnt_product_provider_encrypt_secret( trim( (string) $data['client_secret'] ) );
        if ( is_wp_error( $encrypted ) ) { return $encrypted; }
        $stored['client_secret'] = $encrypted;
    } elseif ( empty( $existing['client_secret'] ) ) {
        $stored['client_secret'] = '';
    }
    update_option( tnt_product_provider_option_name( $provider ), $stored, false );
    return true;
}

function tnt_clear_product_provider_tokens( $provider ) {
    $provider = sanitize_key( $provider );
    $stored = get_option( tnt_product_provider_option_name( $provider ), array() );
    if ( ! is_array( $stored ) ) { $stored = array(); }
    foreach ( array( 'access_token', 'refresh_token', 'expires_at', 'connected_at', 'account_label' ) as $key ) { unset( $stored[ $key ] ); }
    update_option( tnt_product_provider_option_name( $provider ), $stored, false );
}

function tnt_store_product_provider_tokens( $provider, $tokens ) {
    $stored = get_option( tnt_product_provider_option_name( $provider ), array() );
    if ( ! is_array( $stored ) ) { $stored = array(); }
    foreach ( array( 'access_token', 'refresh_token' ) as $key ) {
        if ( ! empty( $tokens[ $key ] ) ) {
            $encrypted = tnt_product_provider_encrypt_secret( (string) $tokens[ $key ] );
            if ( is_wp_error( $encrypted ) ) { return $encrypted; }
            $stored[ $key ] = $encrypted;
        }
    }
    if ( ! empty( $tokens['expires_in'] ) ) { $stored['expires_at'] = time() + max( 60, absint( $tokens['expires_in'] ) - 60 ); }
    $stored['connected_at'] = current_time( 'mysql', true );
    update_option( tnt_product_provider_option_name( $provider ), $stored, false );
    return true;
}

function tnt_product_provider_redirect_uri( $provider ) {
    // Microsoft identity platform does not support query parameters in redirect URIs
    // for app registrations that include personal Microsoft accounts. Keep OneDrive's
    // callback query-free and recover the provider from the server-side OAuth state.
    if ( 'onedrive' === $provider ) {
        return admin_url( 'edit.php' );
    }

    return add_query_arg(
        array( 'post_type' => 'tnt_product', 'page' => 'tnt-product-storage', 'tnt_provider_oauth' => sanitize_key( $provider ) ),
        admin_url( 'edit.php' )
    );
}

function tnt_product_provider_is_live_configured( $provider ) {
    $config = tnt_get_product_provider_config( $provider, true );
    return ! empty( $config['client_id'] ) && ! empty( $config['client_secret'] ) && ! empty( $config['refresh_token'] );
}

function tnt_product_provider_token_endpoint( $provider, $config ) {
    if ( 'google_drive' === $provider ) { return 'https://oauth2.googleapis.com/token'; }
    $tenant = ! empty( $config['tenant'] ) ? rawurlencode( $config['tenant'] ) : 'common';
    return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
}

function tnt_product_provider_refresh_access_token( $provider ) {
    $config = tnt_get_product_provider_config( $provider, true );
    if ( empty( $config['client_id'] ) || empty( $config['client_secret'] ) || empty( $config['refresh_token'] ) ) {
        return new WP_Error( 'tnt_provider_not_configured', __( 'Storage provider is not connected.', 'toolntip-core' ) );
    }
    $body = array(
        'client_id' => $config['client_id'], 'client_secret' => $config['client_secret'],
        'refresh_token' => $config['refresh_token'], 'grant_type' => 'refresh_token',
    );
    if ( 'onedrive' === $provider ) { $body['scope'] = 'offline_access Files.Read'; }
    $response = wp_remote_post( tnt_product_provider_token_endpoint( $provider, $config ), array( 'timeout' => 20, 'body' => $body ) );
    if ( is_wp_error( $response ) ) { return new WP_Error( 'tnt_provider_token_refresh_failed', __( 'Provider token refresh failed.', 'toolntip-core' ) ); }
    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $code < 200 || $code >= 300 || empty( $data['access_token'] ) ) { return new WP_Error( 'tnt_provider_token_refresh_failed', __( 'Provider rejected the token refresh request.', 'toolntip-core' ) ); }
    if ( empty( $data['refresh_token'] ) ) { $data['refresh_token'] = $config['refresh_token']; }
    $saved = tnt_store_product_provider_tokens( $provider, $data );
    if ( is_wp_error( $saved ) ) { return $saved; }
    return (string) $data['access_token'];
}

function tnt_get_product_provider_access_token( $provider ) {
    $config = tnt_get_product_provider_config( $provider, true );
    if ( ! empty( $config['access_token'] ) && ! empty( $config['expires_at'] ) && absint( $config['expires_at'] ) > time() ) { return $config['access_token']; }
    return tnt_product_provider_refresh_access_token( $provider );
}

function tnt_product_provider_api_get( $provider, $url ) {
    $token = tnt_get_product_provider_access_token( $provider );
    if ( is_wp_error( $token ) ) { return $token; }
    $response = wp_remote_get( $url, array( 'timeout' => 20, 'redirection' => 0, 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json' ) ) );
    if ( is_wp_error( $response ) ) { return new WP_Error( 'tnt_provider_api_unavailable', __( 'Storage provider API request failed.', 'toolntip-core' ) ); }
    $code = wp_remote_retrieve_response_code( $response );
    if ( 401 === $code ) {
        $token = tnt_product_provider_refresh_access_token( $provider );
        if ( is_wp_error( $token ) ) { return $token; }
        $response = wp_remote_get( $url, array( 'timeout' => 20, 'redirection' => 0, 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json' ) ) );
        if ( is_wp_error( $response ) ) { return new WP_Error( 'tnt_provider_api_unavailable', __( 'Storage provider API request failed.', 'toolntip-core' ) ); }
        $code = wp_remote_retrieve_response_code( $response );
    }
    if ( 404 === $code ) { return new WP_Error( 'tnt_provider_not_found', __( 'The provider file could not be found.', 'toolntip-core' ) ); }
    if ( 429 === $code ) { return new WP_Error( 'tnt_provider_rate_limited', __( 'The storage provider is temporarily rate limited.', 'toolntip-core' ) ); }
    if ( $code < 200 || $code >= 300 ) { return new WP_Error( 'tnt_provider_api_error', __( 'Storage provider returned an unexpected API response.', 'toolntip-core' ) ); }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    return is_array( $data ) ? $data : new WP_Error( 'tnt_provider_invalid_response', __( 'Storage provider returned invalid metadata.', 'toolntip-core' ) );
}

function tnt_google_drive_validate_reference( $reference ) {
    $reference = trim( sanitize_text_field( $reference ) );
    if ( ! preg_match( '/^[A-Za-z0-9_-]{10,512}$/', $reference ) ) { return new WP_Error( 'tnt_google_reference_invalid', __( 'Enter a valid Google Drive file ID, not a share URL.', 'toolntip-core' ) ); }
    return array( 'valid' => true, 'reference' => $reference, 'remote_verified' => false );
}
function tnt_google_drive_metadata( $reference ) {
    $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $reference ) . '?' . http_build_query( array( 'fields' => 'id,name,size,mimeType,capabilities(canDownload),webContentLink,md5Checksum' ) );
    $data = tnt_product_provider_api_get( 'google_drive', $url );
    if ( is_wp_error( $data ) ) { return $data; }
    return array(
        'id' => sanitize_text_field( $data['id'] ?? '' ), 'filename' => sanitize_file_name( $data['name'] ?? '' ),
        'size' => isset( $data['size'] ) ? absint( $data['size'] ) : 0, 'mime_type' => sanitize_text_field( $data['mimeType'] ?? '' ),
        'can_download' => ! empty( $data['capabilities']['canDownload'] ), 'web_content_link' => esc_url_raw( $data['webContentLink'] ?? '' ),
        'md5' => sanitize_text_field( $data['md5Checksum'] ?? '' ),
    );
}
function tnt_google_drive_health( $reference ) {
    $metadata = tnt_google_drive_metadata( $reference );
    if ( is_wp_error( $metadata ) ) { return $metadata; }
    if ( empty( $metadata['can_download'] ) ) { return array( 'healthy' => false, 'status' => 'download_not_allowed' ); }
    if ( empty( $metadata['web_content_link'] ) ) { return array( 'healthy' => false, 'status' => 'download_link_unavailable' ); }
    return array( 'healthy' => true, 'status' => 'healthy' );
}
function tnt_google_drive_destination( $reference ) {
    $metadata = tnt_google_drive_metadata( $reference );
    if ( is_wp_error( $metadata ) ) { return $metadata; }
    if ( empty( $metadata['can_download'] ) || empty( $metadata['web_content_link'] ) ) { return new WP_Error( 'tnt_google_download_unavailable', __( 'Google Drive did not provide a browser download destination for this file.', 'toolntip-core' ) ); }
    return array( 'url' => $metadata['web_content_link'], 'temporary' => false );
}

function tnt_onedrive_parse_reference( $reference ) {
    $reference = trim( sanitize_text_field( $reference ) );
    if ( '' === $reference || strlen( $reference ) > 512 || preg_match( '/\s/', $reference ) ) { return new WP_Error( 'tnt_onedrive_reference_invalid', __( 'Enter a valid OneDrive item ID or drive-id:item-id reference.', 'toolntip-core' ) ); }
    $drive_id = '';
    $item_id = $reference;
    if ( false !== strpos( $reference, ':' ) ) { list( $drive_id, $item_id ) = array_map( 'trim', explode( ':', $reference, 2 ) ); }
    if ( '' === $item_id ) { return new WP_Error( 'tnt_onedrive_reference_invalid', __( 'OneDrive item ID is required.', 'toolntip-core' ) ); }
    return array( 'valid' => true, 'reference' => $reference, 'drive_id' => $drive_id, 'item_id' => $item_id, 'remote_verified' => false );
}
function tnt_onedrive_item_url( $reference ) {
    $parsed = tnt_onedrive_parse_reference( $reference );
    if ( is_wp_error( $parsed ) ) { return $parsed; }
    if ( $parsed['drive_id'] ) { return 'https://graph.microsoft.com/v1.0/drives/' . rawurlencode( $parsed['drive_id'] ) . '/items/' . rawurlencode( $parsed['item_id'] ); }
    return 'https://graph.microsoft.com/v1.0/me/drive/items/' . rawurlencode( $parsed['item_id'] );
}
function tnt_onedrive_metadata( $reference ) {
    $url = tnt_onedrive_item_url( $reference );
    if ( is_wp_error( $url ) ) { return $url; }
    $data = tnt_product_provider_api_get( 'onedrive', $url );
    if ( is_wp_error( $data ) ) { return $data; }
    if ( empty( $data['file'] ) ) { return new WP_Error( 'tnt_onedrive_not_file', __( 'The OneDrive item is not a downloadable file.', 'toolntip-core' ) ); }
    return array(
        'id' => sanitize_text_field( $data['id'] ?? '' ), 'filename' => sanitize_file_name( $data['name'] ?? '' ), 'size' => isset( $data['size'] ) ? absint( $data['size'] ) : 0,
        'mime_type' => sanitize_text_field( $data['file']['mimeType'] ?? '' ), 'download_url' => esc_url_raw( $data['@microsoft.graph.downloadUrl'] ?? '' ),
    );
}
function tnt_onedrive_health( $reference ) {
    $metadata = tnt_onedrive_metadata( $reference );
    if ( is_wp_error( $metadata ) ) { return $metadata; }
    return array( 'healthy' => ! empty( $metadata['download_url'] ), 'status' => ! empty( $metadata['download_url'] ) ? 'healthy' : 'download_link_unavailable' );
}
function tnt_onedrive_destination( $reference ) {
    $metadata = tnt_onedrive_metadata( $reference );
    if ( is_wp_error( $metadata ) ) { return $metadata; }
    if ( empty( $metadata['download_url'] ) ) { return new WP_Error( 'tnt_onedrive_download_unavailable', __( 'OneDrive did not return a temporary download destination.', 'toolntip-core' ) ); }
    return array( 'url' => $metadata['download_url'], 'temporary' => true );
}

function tnt_register_live_product_provider_adapters( $adapters ) {
    if ( isset( $adapters['google_drive'] ) ) {
        $adapters['google_drive']['is_configured'] = function () { return tnt_product_provider_is_live_configured( 'google_drive' ); };
        $adapters['google_drive']['validate_reference'] = 'tnt_google_drive_validate_reference';
        $adapters['google_drive']['get_metadata'] = 'tnt_google_drive_metadata';
        $adapters['google_drive']['health_check'] = 'tnt_google_drive_health';
        $adapters['google_drive']['get_download_destination'] = 'tnt_google_drive_destination';
    }
    if ( isset( $adapters['onedrive'] ) ) {
        $adapters['onedrive']['is_configured'] = function () { return tnt_product_provider_is_live_configured( 'onedrive' ); };
        $adapters['onedrive']['validate_reference'] = 'tnt_onedrive_parse_reference';
        $adapters['onedrive']['get_metadata'] = 'tnt_onedrive_metadata';
        $adapters['onedrive']['health_check'] = 'tnt_onedrive_health';
        $adapters['onedrive']['get_download_destination'] = 'tnt_onedrive_destination';
    }
    return $adapters;
}
add_filter( 'tnt_product_provider_adapters', 'tnt_register_live_product_provider_adapters' );
