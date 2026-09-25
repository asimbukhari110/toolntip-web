<?php
/**
 * ToolNTip Product Release lifecycle service.
 *
 * Governs Draft -> Ready -> Published -> Withdrawn transitions, readiness
 * validation, published identity protection and audit-backed state changes.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the immutable identity fields for a Published Release.
 *
 * @return string[]
 */
function tnt_get_product_release_immutable_fields() {
    return array(
        'edition_id',
        'version',
        'platform',
        'architecture',
        'artifact_filename',
        'artifact_size',
        'artifact_sha256',
        'published_at',
    );
}

/**
 * Return descriptive fields that may be corrected after publication.
 *
 * @return string[]
 */
function tnt_get_product_release_descriptive_fields() {
    return array(
        'release_date',
        'release_notes',
        'system_requirements',
        'license_reference',
        'documentation_reference',
    );
}

/**
 * Resolve Product ID for a Release without depending on presentation code.
 *
 * @param object $release Release row.
 * @return int
 */
function tnt_get_product_release_product_id( $release ) {
    if ( ! $release || empty( $release->edition_id ) ) {
        return 0;
    }

    $edition = tnt_get_product_edition( absint( $release->edition_id ) );
    return $edition ? absint( $edition->product_id ) : 0;
}

/**
 * Validate whether a Release contains the data required to become Ready.
 *
 * Provider-specific remote verification is intentionally layered on top of
 * this generic lifecycle contract. The base lifecycle requires at least one
 * enabled, registered replica and a complete canonical artifact identity.
 *
 * @param int $release_id Release ID.
 * @return true|WP_Error
 */
function tnt_validate_product_release_readiness( $release_id ) {
    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }

    $required_text = array(
        'version'             => __( 'Version', 'toolntip-core' ),
        'platform'            => __( 'Platform', 'toolntip-core' ),
        'artifact_filename'   => __( 'Artifact filename', 'toolntip-core' ),
        'artifact_sha256'     => __( 'Artifact SHA-256', 'toolntip-core' ),
        'release_notes'       => __( 'Release notes', 'toolntip-core' ),
        'system_requirements' => __( 'System requirements', 'toolntip-core' ),
        'license_reference'   => __( 'License reference', 'toolntip-core' ),
    );

    $missing = array();
    foreach ( $required_text as $field => $label ) {
        if ( '' === trim( (string) $release->{$field} ) ) {
            $missing[] = $label;
        }
    }

    if ( empty( $release->artifact_size ) || absint( $release->artifact_size ) < 1 ) {
        $missing[] = __( 'Artifact size', 'toolntip-core' );
    }

    $filename = (string) $release->artifact_filename;
    $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    if ( $filename && ! in_array( $extension, array( 'zip', 'rar', 'iso' ), true ) ) {
        return new WP_Error( 'tnt_release_artifact_type_invalid', __( 'Release artifact must be a ZIP, RAR or ISO file.', 'toolntip-core' ) );
    }

    if ( ! empty( $release->artifact_sha256 ) && ! preg_match( '/^[a-f0-9]{64}$/', (string) $release->artifact_sha256 ) ) {
        return new WP_Error( 'tnt_invalid_artifact_sha256', __( 'Artifact SHA-256 must contain 64 lowercase hexadecimal characters.', 'toolntip-core' ) );
    }

    if ( ! empty( $missing ) ) {
        return new WP_Error(
            'tnt_release_not_ready',
            sprintf(
                /* translators: %s: comma-separated missing Release fields. */
                __( 'Release is missing required readiness data: %s.', 'toolntip-core' ),
                implode( ', ', $missing )
            ),
            array( 'missing_fields' => $missing )
        );
    }

    $locations = tnt_get_product_release_locations( $release->id, true );
    if ( empty( $locations ) ) {
        return new WP_Error( 'tnt_release_no_enabled_location', __( 'Release requires at least one enabled storage location before it can become Ready.', 'toolntip-core' ) );
    }

    foreach ( $locations as $location ) {
        if ( ! tnt_get_product_provider_adapter( $location->provider ) ) {
            return new WP_Error( 'tnt_release_provider_not_registered', __( 'Release contains an enabled storage provider that is not registered.', 'toolntip-core' ) );
        }

        $reference = tnt_product_provider_validate_reference( $location->provider, $location->provider_file_id );
        if ( is_wp_error( $reference ) ) {
            return $reference;
        }
    }

    /**
     * Allow provider/policy layers to add stricter readiness requirements.
     *
     * Return true to accept or WP_Error to reject.
     *
     * @param true|WP_Error $result  Current validation result.
     * @param object        $release Release row.
     * @param array         $locations Enabled location rows.
     */
    $result = apply_filters( 'tnt_product_release_readiness_validation', true, $release, $locations );

    return is_wp_error( $result ) ? $result : true;
}

/**
 * Record a lifecycle validation outcome.
 *
 * @param object        $release Release row.
 * @param string        $action Audit action.
 * @param array         $context Audit context.
 * @param int|null      $actor_user_id Actor user ID override.
 * @return int|WP_Error
 */
function tnt_record_product_release_lifecycle_audit( $release, $action, $context = array(), $actor_user_id = null ) {
    $data = array(
        'product_id' => tnt_get_product_release_product_id( $release ),
        'edition_id' => absint( $release->edition_id ),
        'release_id' => absint( $release->id ),
        'context'    => $context,
    );

    if ( null !== $actor_user_id ) {
        $data['actor_user_id'] = absint( $actor_user_id );
    }

    return tnt_record_product_release_audit( $action, $data );
}

/**
 * Validate a Release and audit the validation result.
 *
 * @param int      $release_id Release ID.
 * @param int|null $actor_user_id Actor user ID override.
 * @return true|WP_Error
 */
function tnt_validate_and_audit_product_release_readiness( $release_id, $actor_user_id = null ) {
    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }

    $result = tnt_validate_product_release_readiness( $release->id );
    $action = is_wp_error( $result ) ? 'release_validation_failed' : 'release_validation_succeeded';
    $context = is_wp_error( $result )
        ? array( 'error_code' => $result->get_error_code(), 'message' => $result->get_error_message() )
        : array( 'status' => (string) $release->status );

    $audit = tnt_record_product_release_lifecycle_audit( $release, $action, $context, $actor_user_id );
    if ( is_wp_error( $audit ) ) {
        return $audit;
    }

    return $result;
}

/**
 * Perform an audit-backed lifecycle transition atomically where supported.
 *
 * @param int      $release_id Release ID.
 * @param string   $expected_status Required current status.
 * @param string   $target_status Target status.
 * @param string   $audit_action Audit action.
 * @param array    $updates Additional database updates.
 * @param int|null $actor_user_id Actor user ID override.
 * @return int|WP_Error Release ID or error.
 */
function tnt_transition_product_release( $release_id, $expected_status, $target_status, $audit_action, $updates = array(), $actor_user_id = null ) {
    global $wpdb;

    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }

    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }

    if ( (string) $release->status !== (string) $expected_status ) {
        return new WP_Error(
            'tnt_release_stale_state',
            sprintf(
                /* translators: 1: expected Release status, 2: actual Release status. */
                __( 'Release state changed. Expected %1$s but found %2$s. Refresh and try again.', 'toolntip-core' ),
                sanitize_text_field( $expected_status ),
                sanitize_text_field( $release->status )
            )
        );
    }

    $tables = tnt_get_product_table_names();
    $now    = current_time( 'mysql', true );
    $data   = array_merge(
        array(
            'status'     => sanitize_key( $target_status ),
            'updated_at' => $now,
        ),
        $updates
    );

    $wpdb->query( 'START TRANSACTION' );

    $updated = $wpdb->update(
        $tables['releases'],
        $data,
        array(
            'id'     => absint( $release->id ),
            'status' => sanitize_key( $expected_status ),
        )
    );

    if ( false === $updated || 1 !== (int) $updated ) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_Error( 'tnt_release_transition_failed', __( 'Release state could not be changed. Refresh and try again.', 'toolntip-core' ) );
    }

    $updated_release = tnt_get_product_release( $release->id );
    $audit = tnt_record_product_release_lifecycle_audit(
        $updated_release,
        $audit_action,
        array(
            'previous_status' => $expected_status,
            'new_status'      => $target_status,
        ),
        $actor_user_id
    );

    if ( is_wp_error( $audit ) ) {
        $wpdb->query( 'ROLLBACK' );
        return $audit;
    }

    $wpdb->query( 'COMMIT' );
    return absint( $release->id );
}

/**
 * Transition Draft -> Ready after readiness validation.
 *
 * @param int      $release_id Release ID.
 * @param int|null $actor_user_id Actor user ID override.
 * @return int|WP_Error
 */
function tnt_mark_product_release_ready( $release_id, $actor_user_id = null ) {
    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }
    if ( 'draft' !== $release->status ) {
        return new WP_Error( 'tnt_release_transition_invalid', __( 'Only a Draft Release can become Ready.', 'toolntip-core' ) );
    }

    $validation = tnt_validate_and_audit_product_release_readiness( $release->id, $actor_user_id );
    if ( is_wp_error( $validation ) ) {
        return $validation;
    }

    return tnt_transition_product_release( $release->id, 'draft', 'ready', 'release_ready', array(), $actor_user_id );
}

/**
 * Transition Ready -> Published after re-validating readiness.
 *
 * @param int      $release_id Release ID.
 * @param int|null $actor_user_id Actor user ID override.
 * @return int|WP_Error
 */
function tnt_publish_product_release( $release_id, $actor_user_id = null ) {
    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }
    if ( 'ready' !== $release->status ) {
        return new WP_Error( 'tnt_release_transition_invalid', __( 'Only a Ready Release can be Published.', 'toolntip-core' ) );
    }

    $validation = tnt_validate_and_audit_product_release_readiness( $release->id, $actor_user_id );
    if ( is_wp_error( $validation ) ) {
        return $validation;
    }

    return tnt_transition_product_release(
        $release->id,
        'ready',
        'published',
        'release_published',
        array( 'published_at' => current_time( 'mysql', true ) ),
        $actor_user_id
    );
}

/**
 * Transition Published -> Withdrawn.
 *
 * Provider artifacts are retained; withdrawal only ends ToolNTip distribution.
 *
 * @param int      $release_id Release ID.
 * @param int|null $actor_user_id Actor user ID override.
 * @return int|WP_Error
 */
function tnt_withdraw_product_release( $release_id, $actor_user_id = null ) {
    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }
    if ( 'published' !== $release->status ) {
        return new WP_Error( 'tnt_release_transition_invalid', __( 'Only a Published Release can be Withdrawn.', 'toolntip-core' ) );
    }

    return tnt_transition_product_release( $release->id, 'published', 'withdrawn', 'release_withdrawn', array(), $actor_user_id );
}

/**
 * Correct descriptive Release fields without changing published identity.
 *
 * @param int      $release_id Release ID.
 * @param array    $data Descriptive values.
 * @param int|null $actor_user_id Actor user ID override.
 * @return int|WP_Error
 */
function tnt_update_product_release_description( $release_id, $data, $actor_user_id = null ) {
    global $wpdb;

    $release = tnt_get_product_release( $release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }
    if ( ! in_array( $release->status, array( 'published', 'withdrawn' ), true ) ) {
        return new WP_Error( 'tnt_release_description_state_invalid', __( 'Use the normal Release editor for Draft or Ready Releases.', 'toolntip-core' ) );
    }

    $allowed = tnt_get_product_release_descriptive_fields();
    foreach ( array_keys( (array) $data ) as $field ) {
        if ( ! in_array( $field, $allowed, true ) ) {
            return new WP_Error( 'tnt_release_immutable_field', __( 'Published Release identity fields cannot be changed.', 'toolntip-core' ) );
        }
    }

    $validated = tnt_validate_product_release_data( $data, $release );
    if ( is_wp_error( $validated ) ) {
        return $validated;
    }

    $updates = array();
    foreach ( $allowed as $field ) {
        if ( array_key_exists( $field, (array) $data ) ) {
            $updates[ $field ] = $validated[ $field ];
        }
    }

    if ( empty( $updates ) ) {
        return absint( $release->id );
    }

    $updates['updated_at'] = current_time( 'mysql', true );
    $tables = tnt_get_product_table_names();

    $wpdb->query( 'START TRANSACTION' );
    $saved = $wpdb->update( $tables['releases'], $updates, array( 'id' => absint( $release->id ), 'status' => $release->status ) );
    if ( false === $saved ) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_Error( 'tnt_release_update_failed', __( 'Release description could not be updated.', 'toolntip-core' ) );
    }

    $audit = tnt_record_product_release_lifecycle_audit(
        tnt_get_product_release( $release->id ),
        'release_updated',
        array( 'updated_fields' => array_keys( $updates ) ),
        $actor_user_id
    );
    if ( is_wp_error( $audit ) ) {
        $wpdb->query( 'ROLLBACK' );
        return $audit;
    }

    $wpdb->query( 'COMMIT' );
    return absint( $release->id );
}
