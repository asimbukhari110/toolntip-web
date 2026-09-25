<?php
/**
 * ToolNTip Product Release audit service.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return controlled Product Release audit actions.
 *
 * @return string[]
 */
function tnt_get_product_release_audit_actions() {
    return array(
        'edition_created',
        'edition_updated',
        'edition_deleted',
        'release_created',
        'release_updated',
        'release_deleted',
        'release_validation_succeeded',
        'release_validation_failed',
        'release_ready',
        'release_published',
        'release_withdrawn',
        'release_integrity_failed',
        'release_artifact_missing',
        'release_location_added',
        'release_location_removed',
        'release_location_enabled',
        'release_location_disabled',
        'release_location_verified',
        'release_delivery_failover',
    );
}

/**
 * Sanitize audit context recursively for JSON storage.
 *
 * Context must contain operational metadata only. Secret-bearing keys are
 * removed defensively before persistence.
 *
 * @param mixed $value Context value.
 * @return mixed
 */
function tnt_sanitize_product_release_audit_context( $value ) {
    $blocked = array( 'password', 'passwd', 'secret', 'token', 'access_token', 'refresh_token', 'nonce', 'cookie', 'authorization', 'license_key', 'api_key' );

    if ( is_array( $value ) ) {
        $clean = array();
        foreach ( $value as $key => $item ) {
            $safe_key = is_string( $key ) ? sanitize_key( $key ) : $key;
            if ( is_string( $safe_key ) && in_array( $safe_key, $blocked, true ) ) {
                continue;
            }
            $clean[ $safe_key ] = tnt_sanitize_product_release_audit_context( $item );
        }
        return $clean;
    }

    if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
        return $value;
    }

    return sanitize_textarea_field( (string) $value );
}

/**
 * Append a governed Product Release audit event.
 *
 * @param string $action Action key.
 * @param array  $data Event data.
 * @return int|WP_Error Audit row ID or error.
 */
function tnt_record_product_release_audit( $action, $data ) {
    global $wpdb;

    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }

    $action = sanitize_key( $action );
    if ( ! in_array( $action, tnt_get_product_release_audit_actions(), true ) ) {
        return new WP_Error( 'tnt_invalid_release_audit_action', __( 'Invalid Product Release audit action.', 'toolntip-core' ) );
    }

    $product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return new WP_Error( 'tnt_invalid_product', __( 'A valid Product is required for the audit event.', 'toolntip-core' ) );
    }

    $edition_id = isset( $data['edition_id'] ) ? absint( $data['edition_id'] ) : 0;
    $release_id = isset( $data['release_id'] ) ? absint( $data['release_id'] ) : 0;

    if ( $edition_id ) {
        $edition = tnt_get_product_edition( $edition_id );
        if ( ! $edition || absint( $edition->product_id ) !== $product_id ) {
            return new WP_Error( 'tnt_invalid_audit_edition', __( 'Audit Edition does not belong to the Product.', 'toolntip-core' ) );
        }
    }

    $release = null;
    if ( $release_id ) {
        $release = tnt_get_product_release( $release_id );
        if ( ! $release ) {
            return new WP_Error( 'tnt_invalid_audit_release', __( 'Audit Release was not found.', 'toolntip-core' ) );
        }
        $release_edition = tnt_get_product_edition( $release->edition_id );
        if ( ! $release_edition || absint( $release_edition->product_id ) !== $product_id ) {
            return new WP_Error( 'tnt_invalid_audit_release_product', __( 'Audit Release does not belong to the Product.', 'toolntip-core' ) );
        }
        if ( $edition_id && absint( $release->edition_id ) !== $edition_id ) {
            return new WP_Error( 'tnt_invalid_audit_release_edition', __( 'Audit Release does not belong to the Edition.', 'toolntip-core' ) );
        }
        if ( ! $edition_id ) {
            $edition_id = absint( $release->edition_id );
        }
    }

    $actor_user_id = array_key_exists( 'actor_user_id', $data ) ? absint( $data['actor_user_id'] ) : get_current_user_id();
    $version       = isset( $data['version_snapshot'] ) ? sanitize_text_field( $data['version_snapshot'] ) : ( $release ? $release->version : '' );
    $filename      = isset( $data['artifact_filename_snapshot'] ) ? sanitize_file_name( $data['artifact_filename_snapshot'] ) : ( $release ? $release->artifact_filename : '' );
    $context       = isset( $data['context'] ) ? tnt_sanitize_product_release_audit_context( $data['context'] ) : array();

    $tables = tnt_get_product_table_names();
    $ok     = $wpdb->insert(
        $tables['release_audit'],
        array(
            'product_id'                 => $product_id,
            'edition_id'                 => $edition_id ?: null,
            'release_id'                 => $release_id ?: null,
            'action'                     => $action,
            'actor_user_id'              => $actor_user_id,
            'version_snapshot'           => '' !== $version ? $version : null,
            'artifact_filename_snapshot' => '' !== $filename ? $filename : null,
            'context'                    => ! empty( $context ) ? wp_json_encode( $context ) : null,
            'created_at'                 => current_time( 'mysql', true ),
        ),
        array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
    );

    if ( false === $ok ) {
        return new WP_Error( 'tnt_release_audit_failed', __( 'Product Release audit event could not be recorded.', 'toolntip-core' ) );
    }

    return absint( $wpdb->insert_id );
}

/**
 * Return Release audit events newest first.
 *
 * @param int $release_id Release ID.
 * @param int $limit Maximum rows.
 * @return array
 */
function tnt_get_product_release_audit( $release_id, $limit = 100 ) {
    global $wpdb;

    $release_id = absint( $release_id );
    $limit      = max( 1, min( 500, absint( $limit ) ) );

    if ( ! $release_id || ! tnt_product_platform_ready() ) {
        return array();
    }

    $tables = tnt_get_product_table_names();
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$tables['release_audit']} WHERE release_id = %d ORDER BY created_at DESC, id DESC LIMIT %d",
            $release_id,
            $limit
        )
    );
}
