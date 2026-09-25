<?php
/**
 * ToolNTip Product Release location repository.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return provider keys enabled by Core.
 *
 * Provider adapters are registered separately; this list only governs stored
 * location references.
 *
 * @return string[]
 */
function tnt_get_product_release_location_providers() {
    if ( function_exists( 'tnt_get_product_provider_adapters' ) ) {
        return array_keys( tnt_get_product_provider_adapters() );
    }
    return array( 'google_drive', 'onedrive' );
}

/**
 * Return one release location.
 *
 * @param int $location_id Location ID.
 * @return object|null
 */
function tnt_get_product_release_location( $location_id ) {
    global $wpdb;

    $location_id = absint( $location_id );
    if ( ! $location_id || ! tnt_product_platform_ready() ) {
        return null;
    }

    $tables = tnt_get_product_table_names();
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['release_locations']} WHERE id = %d", $location_id ) );
}

/**
 * Return one Release Location by its complete durable identity.
 *
 * The database unique key uses a provider-file-reference prefix for broad
 * utf8mb4 compatibility. Repository duplicate/reconciliation checks therefore
 * compare the complete stored provider reference, not only the indexed prefix.
 *
 * @param int    $release_id Release ID.
 * @param string $provider Provider key.
 * @param string $provider_file_id Complete provider file/item reference.
 * @return object|null
 */
function tnt_get_product_release_location_by_reference( $release_id, $provider, $provider_file_id ) {
    global $wpdb;

    $release_id       = absint( $release_id );
    $provider         = sanitize_key( $provider );
    $provider_file_id = trim( (string) $provider_file_id );

    if ( ! $release_id || '' === $provider || '' === $provider_file_id || ! tnt_product_platform_ready() ) {
        return null;
    }

    $tables = tnt_get_product_table_names();

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$tables['release_locations']} WHERE release_id = %d AND provider = %s AND provider_file_id = %s LIMIT 1",
            $release_id,
            $provider,
            $provider_file_id
        )
    );
}

/**
 * Return locations for a release in deterministic delivery order.
 *
 * @param int  $release_id Release ID.
 * @param bool $enabled_only Return enabled locations only.
 * @return array
 */
function tnt_get_product_release_locations( $release_id, $enabled_only = false ) {
    global $wpdb;

    $release_id = absint( $release_id );
    if ( ! $release_id || ! tnt_product_platform_ready() ) {
        return array();
    }

    $tables = tnt_get_product_table_names();
    if ( $enabled_only ) {
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tables['release_locations']} WHERE release_id = %d AND enabled = 1 ORDER BY priority ASC, id ASC", $release_id ) );
    }

    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tables['release_locations']} WHERE release_id = %d ORDER BY enabled DESC, priority ASC, id ASC", $release_id ) );
}

/**
 * Validate release-location data.
 *
 * @param array       $data Data to validate.
 * @param object|null $existing Existing row.
 * @return array|WP_Error
 */
function tnt_validate_product_release_location_data( $data, $existing = null ) {
    $release_id = isset( $data['release_id'] ) ? absint( $data['release_id'] ) : ( $existing ? absint( $existing->release_id ) : 0 );
    $release    = $release_id ? tnt_get_product_release( $release_id ) : null;
    if ( ! $release ) {
        return new WP_Error( 'tnt_invalid_release', __( 'A valid Release is required.', 'toolntip-core' ) );
    }

    $provider = isset( $data['provider'] ) ? sanitize_key( $data['provider'] ) : ( $existing ? $existing->provider : '' );
    if ( ! in_array( $provider, tnt_get_product_release_location_providers(), true ) ) {
        return new WP_Error( 'tnt_invalid_release_provider', __( 'That release storage provider is not registered.', 'toolntip-core' ) );
    }

    $provider_file_id = isset( $data['provider_file_id'] ) ? trim( sanitize_text_field( $data['provider_file_id'] ) ) : ( $existing ? $existing->provider_file_id : '' );
    if ( '' === $provider_file_id || strlen( $provider_file_id ) > 512 ) {
        return new WP_Error( 'tnt_invalid_provider_file_id', __( 'A valid provider file reference is required.', 'toolntip-core' ) );
    }

    $enabled  = isset( $data['enabled'] ) ? (int) (bool) $data['enabled'] : ( $existing ? (int) $existing->enabled : 1 );
    $priority = isset( $data['priority'] ) ? absint( $data['priority'] ) : ( $existing ? absint( $existing->priority ) : 100 );
    $weight   = isset( $data['weight'] ) ? absint( $data['weight'] ) : ( $existing ? absint( $existing->weight ) : 100 );

    if ( $priority < 1 ) {
        $priority = 100;
    }
    if ( $weight < 1 ) {
        return new WP_Error( 'tnt_invalid_release_location_weight', __( 'Release location weight must be at least 1.', 'toolntip-core' ) );
    }

    return array(
        'release_id'       => $release_id,
        'provider'         => $provider,
        'provider_file_id' => $provider_file_id,
        'enabled'          => $enabled,
        'priority'         => $priority,
        'weight'           => $weight,
    );
}

/**
 * Resolve Product ID for a Release.
 *
 * @param object $release Release row.
 * @return int
 */
function tnt_get_product_id_for_release( $release ) {
    if ( ! $release || empty( $release->edition_id ) ) {
        return 0;
    }
    $edition = tnt_get_product_edition( $release->edition_id );
    return $edition ? absint( $edition->product_id ) : 0;
}

/**
 * Count enabled locations other than an optional location.
 *
 * @param int $release_id Release ID.
 * @param int $exclude_id Location to exclude.
 * @return int
 */
function tnt_count_enabled_product_release_locations( $release_id, $exclude_id = 0 ) {
    global $wpdb;

    $tables     = tnt_get_product_table_names();
    $release_id = absint( $release_id );
    $exclude_id = absint( $exclude_id );
    if ( ! $release_id || ! tnt_product_platform_ready() ) {
        return 0;
    }

    if ( $exclude_id ) {
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['release_locations']} WHERE release_id = %d AND enabled = 1 AND id <> %d", $release_id, $exclude_id ) );
    }
    return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['release_locations']} WHERE release_id = %d AND enabled = 1", $release_id ) );
}

/**
 * Save a release location and append the appropriate audit event.
 *
 * @param array $data Location data.
 * @param int   $location_id Existing location ID when updating.
 * @return int|WP_Error
 */
function tnt_save_product_release_location( $data, $location_id = 0 ) {
    global $wpdb;

    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }

    $existing = $location_id ? tnt_get_product_release_location( $location_id ) : null;
    if ( $location_id && ! $existing ) {
        return new WP_Error( 'tnt_release_location_not_found', __( 'Release location not found.', 'toolntip-core' ) );
    }

    $clean = tnt_validate_product_release_location_data( $data, $existing );
    if ( is_wp_error( $clean ) ) {
        return $clean;
    }

    if ( $existing && absint( $existing->release_id ) !== absint( $clean['release_id'] ) ) {
        return new WP_Error( 'tnt_release_location_move_blocked', __( 'A release location cannot be moved to another Release.', 'toolntip-core' ) );
    }

    $release = tnt_get_product_release( $clean['release_id'] );
    if ( $existing && 'published' === $release->status && (int) $existing->enabled === 1 && 0 === (int) $clean['enabled'] && tnt_count_enabled_product_release_locations( $release->id, $existing->id ) < 1 ) {
        return new WP_Error( 'tnt_last_release_location', __( 'A Published Release must retain at least one enabled release location.', 'toolntip-core' ) );
    }

    $tables = tnt_get_product_table_names();
    if ( $existing ) {
        $clean['updated_at'] = current_time( 'mysql', true );
        $ok = $wpdb->update( $tables['release_locations'], $clean, array( 'id' => absint( $existing->id ) ) );
        if ( false === $ok ) {
            return new WP_Error( 'tnt_release_location_save_failed', __( 'Release location could not be saved.', 'toolntip-core' ) );
        }
        $saved_id = absint( $existing->id );
        if ( (int) $existing->enabled !== (int) $clean['enabled'] ) {
            $action = $clean['enabled'] ? 'release_location_enabled' : 'release_location_disabled';
            tnt_record_product_release_audit( $action, array(
                'product_id' => tnt_get_product_id_for_release( $release ),
                'release_id' => $release->id,
                'context'    => array( 'location_id' => $saved_id, 'provider' => $clean['provider'] ),
            ) );
        }
        return $saved_id;
    }

    /*
     * Reject an already-existing exact durable reference before insert.
     * This gives a deterministic duplicate message rather than relying on the
     * database's prefix-based unique index error.
     */
    $duplicate = tnt_get_product_release_location_by_reference(
        $clean['release_id'],
        $clean['provider'],
        $clean['provider_file_id']
    );

    if ( $duplicate ) {
        return new WP_Error(
            'tnt_release_location_duplicate',
            __( 'This Release already has that provider file reference.', 'toolntip-core' )
        );
    }

    $now                 = current_time( 'mysql', true );
    $clean['created_at'] = $now;
    $clean['updated_at'] = $now;
    $ok = $wpdb->insert( $tables['release_locations'], $clean );

    if ( false === $ok ) {
        /*
         * A concurrent/double-submitted request can create the exact row
         * between our duplicate check and insert. Reconcile persisted state
         * before reporting failure so admin feedback cannot contradict the DB.
         */
        $reconciled = tnt_get_product_release_location_by_reference(
            $clean['release_id'],
            $clean['provider'],
            $clean['provider_file_id']
        );

        if ( $reconciled ) {
            return absint( $reconciled->id );
        }

        return new WP_Error(
            'tnt_release_location_save_failed',
            __( 'Release location could not be created. No matching location was persisted.', 'toolntip-core' )
        );
    }

    $saved_id = absint( $wpdb->insert_id );
    tnt_record_product_release_audit( 'release_location_added', array(
        'product_id' => tnt_get_product_id_for_release( $release ),
        'release_id' => $release->id,
        'context'    => array( 'location_id' => $saved_id, 'provider' => $clean['provider'] ),
    ) );
    return $saved_id;
}

/**
 * Remove a registered location without deleting the provider file.
 *
 * @param int $location_id Location ID.
 * @return bool|WP_Error
 */
function tnt_delete_product_release_location( $location_id ) {
    global $wpdb;

    $location = tnt_get_product_release_location( $location_id );
    if ( ! $location ) {
        return new WP_Error( 'tnt_release_location_not_found', __( 'Release location not found.', 'toolntip-core' ) );
    }

    $release = tnt_get_product_release( $location->release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }

    if ( 'published' === $release->status && (int) $location->enabled === 1 && tnt_count_enabled_product_release_locations( $release->id, $location->id ) < 1 ) {
        return new WP_Error( 'tnt_last_release_location', __( 'A Published Release must retain at least one enabled release location.', 'toolntip-core' ) );
    }

    $tables = tnt_get_product_table_names();
    $ok     = $wpdb->delete( $tables['release_locations'], array( 'id' => absint( $location->id ) ) );
    if ( false === $ok ) {
        return new WP_Error( 'tnt_release_location_delete_failed', __( 'Release location could not be removed.', 'toolntip-core' ) );
    }

    tnt_record_product_release_audit( 'release_location_removed', array(
        'product_id' => tnt_get_product_id_for_release( $release ),
        'release_id' => $release->id,
        'context'    => array( 'location_id' => absint( $location->id ), 'provider' => $location->provider ),
    ) );
    return true;
}
