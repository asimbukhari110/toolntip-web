<?php
/**
 * ToolNTip Product Release repository.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_product_release_statuses() {
    return array( 'draft', 'ready', 'published', 'withdrawn' );
}

function tnt_get_product_release( $release_id ) {
    global $wpdb;
    $tables = tnt_get_product_table_names();
    $id = absint( $release_id );
    if ( ! $id || ! tnt_product_platform_ready() ) {
        return null;
    }
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['releases']} WHERE id = %d", $id ) );
}

function tnt_get_product_release_by_version( $edition_id, $version ) {
    global $wpdb;
    $tables = tnt_get_product_table_names();
    $edition_id = absint( $edition_id );
    $version = trim( sanitize_text_field( $version ) );
    if ( ! $edition_id || '' === $version || ! tnt_product_platform_ready() ) {
        return null;
    }
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['releases']} WHERE edition_id = %d AND version = %s", $edition_id, $version ) );
}

function tnt_get_edition_releases( $edition_id, $status = '' ) {
    global $wpdb;
    $tables = tnt_get_product_table_names();
    $edition_id = absint( $edition_id );
    if ( ! $edition_id || ! tnt_product_platform_ready() ) {
        return array();
    }
    $status = sanitize_key( $status );
    if ( $status && in_array( $status, tnt_get_product_release_statuses(), true ) ) {
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tables['releases']} WHERE edition_id = %d AND status = %s ORDER BY published_at DESC, id DESC", $edition_id, $status ) );
    }
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tables['releases']} WHERE edition_id = %d ORDER BY id DESC", $edition_id ) );
}

function tnt_validate_product_release_data( $data, $existing = null ) {
    $edition_id = isset( $data['edition_id'] ) ? absint( $data['edition_id'] ) : ( $existing ? absint( $existing->edition_id ) : 0 );
    if ( ! $edition_id || ! tnt_get_product_edition( $edition_id ) ) {
        return new WP_Error( 'tnt_invalid_edition', __( 'A valid Edition is required.', 'toolntip-core' ) );
    }
    $version = isset( $data['version'] ) ? trim( sanitize_text_field( $data['version'] ) ) : ( $existing ? $existing->version : '' );
    $platform = isset( $data['platform'] ) ? sanitize_text_field( $data['platform'] ) : ( $existing ? $existing->platform : '' );
    $status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : ( $existing ? $existing->status : 'draft' );
    if ( '' === $version || '' === $platform ) {
        return new WP_Error( 'tnt_invalid_release', __( 'Release version and platform are required.', 'toolntip-core' ) );
    }
    if ( ! in_array( $status, tnt_get_product_release_statuses(), true ) ) {
        return new WP_Error( 'tnt_invalid_release_status', __( 'Invalid Release status.', 'toolntip-core' ) );
    }
    $release_date = isset( $data['release_date'] ) ? trim( sanitize_text_field( $data['release_date'] ) ) : ( $existing ? $existing->release_date : null );
    if ( $release_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $release_date ) ) {
        return new WP_Error( 'tnt_invalid_release_date', __( 'Release date must use YYYY-MM-DD format.', 'toolntip-core' ) );
    }
    $sha = isset( $data['artifact_sha256'] ) ? strtolower( trim( sanitize_text_field( $data['artifact_sha256'] ) ) ) : ( $existing ? $existing->artifact_sha256 : null );
    if ( $sha && ! preg_match( '/^[a-f0-9]{64}$/', $sha ) ) {
        return new WP_Error( 'tnt_invalid_artifact_sha256', __( 'Artifact SHA-256 must contain 64 hexadecimal characters.', 'toolntip-core' ) );
    }
    return array(
        'edition_id'              => $edition_id,
        'version'                 => $version,
        'release_date'            => $release_date ?: null,
        'status'                  => $status,
        'platform'                => $platform,
        'architecture'            => isset( $data['architecture'] ) ? sanitize_text_field( $data['architecture'] ) : ( $existing ? $existing->architecture : null ),
        'artifact_filename'       => isset( $data['artifact_filename'] ) ? sanitize_file_name( $data['artifact_filename'] ) : ( $existing ? $existing->artifact_filename : null ),
        'artifact_size'           => isset( $data['artifact_size'] ) && '' !== (string) $data['artifact_size'] ? absint( $data['artifact_size'] ) : ( $existing ? $existing->artifact_size : null ),
        'artifact_sha256'         => $sha ?: null,
        'release_notes'           => isset( $data['release_notes'] ) ? wp_kses_post( $data['release_notes'] ) : ( $existing ? $existing->release_notes : null ),
        'system_requirements'     => isset( $data['system_requirements'] ) ? wp_kses_post( $data['system_requirements'] ) : ( $existing ? $existing->system_requirements : null ),
        'license_reference'       => isset( $data['license_reference'] ) ? sanitize_textarea_field( $data['license_reference'] ) : ( $existing ? $existing->license_reference : null ),
        'documentation_reference' => isset( $data['documentation_reference'] ) ? esc_url_raw( $data['documentation_reference'] ) : ( $existing ? $existing->documentation_reference : null ),
    );
}

function tnt_save_product_release( $data, $release_id = 0 ) {
    global $wpdb;
    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }
    $tables = tnt_get_product_table_names();
    $existing = $release_id ? tnt_get_product_release( $release_id ) : null;
    if ( $release_id && ! $existing ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }
    if ( $existing && in_array( $existing->status, array( 'published', 'withdrawn' ), true ) ) {
        return new WP_Error( 'tnt_release_protected', __( 'Published and Withdrawn Releases cannot be modified through the general repository.', 'toolntip-core' ) );
    }
    $clean = tnt_validate_product_release_data( $data, $existing );
    if ( is_wp_error( $clean ) ) {
        return $clean;
    }
    if ( $existing ) {
        if ( (string) $clean['status'] !== (string) $existing->status ) {
            return new WP_Error( 'tnt_release_transition_required', __( 'Release status changes require the governed Release lifecycle service.', 'toolntip-core' ) );
        }
    } elseif ( 'draft' !== $clean['status'] ) {
        return new WP_Error( 'tnt_release_transition_required', __( 'New Releases must begin in Draft state.', 'toolntip-core' ) );
    }
    $duplicate = tnt_get_product_release_by_version( $clean['edition_id'], $clean['version'] );
    if ( $duplicate && ( ! $existing || absint( $duplicate->id ) !== absint( $existing->id ) ) ) {
        return new WP_Error( 'tnt_duplicate_release_version', __( 'That version already exists for this Edition.', 'toolntip-core' ) );
    }
    $now = current_time( 'mysql', true );
    if ( $existing ) {
        if ( 'ready' === $existing->status ) {
            $critical = array( 'edition_id', 'version', 'platform', 'architecture', 'artifact_filename', 'artifact_size', 'artifact_sha256', 'release_notes', 'system_requirements', 'license_reference' );
            foreach ( $critical as $field ) {
                if ( (string) $clean[ $field ] !== (string) $existing->{$field} ) {
                    $clean['status'] = 'draft';
                    break;
                }
            }
        }
        $clean['updated_at'] = $now;
        $ok = $wpdb->update( $tables['releases'], $clean, array( 'id' => absint( $existing->id ) ) );
        return false === $ok ? new WP_Error( 'tnt_release_save_failed', __( 'Release could not be saved.', 'toolntip-core' ) ) : absint( $existing->id );
    }
    $clean['created_at'] = $now;
    $clean['updated_at'] = $now;
    $ok = $wpdb->insert( $tables['releases'], $clean );
    return false === $ok ? new WP_Error( 'tnt_release_save_failed', __( 'Release could not be created.', 'toolntip-core' ) ) : absint( $wpdb->insert_id );
}

function tnt_delete_product_release( $release_id ) {
    global $wpdb;
    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }
    if ( ! in_array( $release->status, array( 'draft', 'ready' ), true ) ) {
        return new WP_Error( 'tnt_release_delete_blocked', __( 'Published and Withdrawn Releases cannot be deleted.', 'toolntip-core' ) );
    }
    $tables = tnt_get_product_table_names();
    return false !== $wpdb->delete( $tables['releases'], array( 'id' => absint( $release->id ) ) );
}
