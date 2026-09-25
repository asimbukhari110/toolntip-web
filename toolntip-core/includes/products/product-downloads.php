<?php
/**
 * ToolNTip Product download-event repository and analytics queries.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_product_download_event_statuses() {
    return array( 'redirected' );
}

/**
 * Normalize an account geography snapshot.
 *
 * @param array $geography Geography values.
 * @return array
 */
function tnt_normalize_product_download_geography( $geography ) {
    $geography = is_array( $geography ) ? $geography : array();
    $country_code = isset( $geography['country_code'] ) ? strtoupper( sanitize_text_field( $geography['country_code'] ) ) : '';
    if ( $country_code && ! preg_match( '/^[A-Z]{2}$/', $country_code ) ) {
        $country_code = '';
    }
    return array(
        'country_code'  => $country_code ?: null,
        'country_name'  => ! empty( $geography['country_name'] ) ? sanitize_text_field( $geography['country_name'] ) : null,
        'region_name'   => ! empty( $geography['region_name'] ) ? sanitize_text_field( $geography['region_name'] ) : null,
        'city_name'     => ! empty( $geography['city_name'] ) ? sanitize_text_field( $geography['city_name'] ) : null,
        'timezone_name' => ! empty( $geography['timezone_name'] ) ? sanitize_text_field( $geography['timezone_name'] ) : null,
    );
}

/**
 * Obtain the current account geography snapshot.
 *
 * WEB-007.10 owns the profile UX. The filter lets that account layer supply
 * its canonical profile fields without coupling this repository to UI/meta.
 *
 * @param int $user_id User ID.
 * @return array
 */
function tnt_get_product_download_user_geography( $user_id ) {
    $geography = apply_filters( 'tnt_product_download_user_geography', array(), absint( $user_id ) );
    return tnt_normalize_product_download_geography( $geography );
}

/**
 * Record one successful governed redirect request.
 *
 * This is deliberately not a completed-download assertion.
 *
 * @param array $data Event data.
 * @return int|WP_Error
 */
function tnt_record_product_download_event( $data ) {
    global $wpdb;

    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }

    $user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : get_current_user_id();
    if ( ! $user_id || ! get_userdata( $user_id ) ) {
        return new WP_Error( 'tnt_download_auth_required', __( 'A registered user is required for Product downloads.', 'toolntip-core' ) );
    }

    $release_id = isset( $data['release_id'] ) ? absint( $data['release_id'] ) : 0;
    $release    = $release_id ? tnt_get_product_release( $release_id ) : null;
    if ( ! $release ) {
        return new WP_Error( 'tnt_invalid_release', __( 'A valid Release is required.', 'toolntip-core' ) );
    }

    $edition = tnt_get_product_edition( $release->edition_id );
    if ( ! $edition ) {
        return new WP_Error( 'tnt_invalid_edition', __( 'The Release Edition is unavailable.', 'toolntip-core' ) );
    }
    $product_id = absint( $edition->product_id );

    $location_id = isset( $data['location_id'] ) ? absint( $data['location_id'] ) : 0;
    $location    = $location_id ? tnt_get_product_release_location( $location_id ) : null;
    if ( ! $location || absint( $location->release_id ) !== $release_id ) {
        return new WP_Error( 'tnt_invalid_release_location', __( 'A valid Release location is required.', 'toolntip-core' ) );
    }

    $event_status = isset( $data['event_status'] ) ? sanitize_key( $data['event_status'] ) : 'redirected';
    if ( ! in_array( $event_status, tnt_get_product_download_event_statuses(), true ) ) {
        return new WP_Error( 'tnt_invalid_download_event_status', __( 'Invalid Product download event status.', 'toolntip-core' ) );
    }

    $geography = isset( $data['geography'] ) ? tnt_normalize_product_download_geography( $data['geography'] ) : tnt_get_product_download_user_geography( $user_id );
    $tables    = tnt_get_product_table_names();
    $row       = array(
        'user_id'                    => $user_id,
        'product_id'                 => $product_id,
        'edition_id'                 => absint( $edition->id ),
        'release_id'                 => $release_id,
        'location_id'                => absint( $location->id ),
        'provider'                   => sanitize_key( $location->provider ),
        'version_snapshot'           => sanitize_text_field( $release->version ),
        'artifact_filename_snapshot' => $release->artifact_filename ? sanitize_file_name( $release->artifact_filename ) : null,
        'event_status'               => $event_status,
        'country_code'               => $geography['country_code'],
        'country_name'               => $geography['country_name'],
        'region_name'                => $geography['region_name'],
        'city_name'                  => $geography['city_name'],
        'timezone_name'              => $geography['timezone_name'],
        'created_at'                 => current_time( 'mysql', true ),
    );

    $ok = $wpdb->insert( $tables['downloads'], $row, array( '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
    if ( false === $ok ) {
        return new WP_Error( 'tnt_download_event_log_failed', __( 'The Product download redirect could not be recorded.', 'toolntip-core' ) );
    }
    return absint( $wpdb->insert_id );
}

/**
 * Query download events for governed admin analytics.
 *
 * @param array $args Query arguments.
 * @return array
 */
function tnt_get_product_download_events( $args = array() ) {
    global $wpdb;

    if ( ! tnt_product_platform_ready() ) {
        return array();
    }

    $args = wp_parse_args( $args, array(
        'product_id'  => 0,
        'edition_id'  => 0,
        'release_id'  => 0,
        'user_id'     => 0,
        'provider'    => '',
        'country_code'=> '',
        'limit'       => 100,
        'offset'      => 0,
    ) );

    $where  = array( '1=1' );
    $values = array();
    foreach ( array( 'product_id', 'edition_id', 'release_id', 'user_id' ) as $field ) {
        if ( ! empty( $args[ $field ] ) ) {
            $where[]  = "$field = %d";
            $values[] = absint( $args[ $field ] );
        }
    }
    if ( ! empty( $args['provider'] ) ) {
        $where[]  = 'provider = %s';
        $values[] = sanitize_key( $args['provider'] );
    }
    if ( ! empty( $args['country_code'] ) ) {
        $where[]  = 'country_code = %s';
        $values[] = strtoupper( sanitize_text_field( $args['country_code'] ) );
    }

    $limit    = max( 1, min( 500, absint( $args['limit'] ) ) );
    $offset   = absint( $args['offset'] );
    $values[] = $limit;
    $values[] = $offset;
    $tables   = tnt_get_product_table_names();
    $sql      = "SELECT * FROM {$tables['downloads']} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d';
    return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
}
