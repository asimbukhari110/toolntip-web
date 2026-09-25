<?php
/**
 * Governed Product Release storage-replica administration.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether the current user may manage Release replicas.
 *
 * Replica management follows Release-management authority. Provider credential
 * configuration remains a separate, stricter boundary implemented later.
 *
 * @return bool
 */
function tnt_current_user_can_manage_release_locations() {
    return current_user_can( 'manage_options' ) || current_user_can( 'manage_toolntip_releases' );
}

/**
 * Return an admin-safe provider label.
 *
 * @param string $provider Provider key.
 * @return string
 */
function tnt_product_provider_admin_label( $provider ) {
    $adapter = tnt_get_product_provider_adapter( $provider );

    return $adapter && ! empty( $adapter['label'] )
        ? (string) $adapter['label']
        : sanitize_key( $provider );
}

/**
 * Return the persisted administrator verification summary for a Release Location.
 *
 * This records the last explicit verification result for presentation only. It is
 * not used as delivery-health truth; delivery maintains its own short-lived live
 * provider-health cache. No credentials, tokens, raw provider responses or
 * download destinations are stored here.
 *
 * @param int $location_id Location ID.
 * @return array
 */
function tnt_get_release_location_verification_record( $location_id ) {
    $location_id = absint( $location_id );
    if ( ! $location_id ) {
        return array();
    }

    $value = get_option( 'tnt_product_location_verification_' . $location_id, array() );

    return is_array( $value ) ? $value : array();
}

/**
 * Persist the safe summary of the last explicit administrator verification.
 *
 * @param int   $location_id Location ID.
 * @param array $data Verification summary.
 * @return void
 */
function tnt_set_release_location_verification_record( $location_id, $data ) {
    $location_id = absint( $location_id );
    if ( ! $location_id ) {
        return;
    }

    $data = is_array( $data ) ? $data : array();
    $safe = array(
        'status'     => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : '',
        'checked_at' => isset( $data['checked_at'] ) ? sanitize_text_field( $data['checked_at'] ) : current_time( 'mysql', true ),
    );

    if ( isset( $data['remote_filename'] ) ) {
        $safe['remote_filename'] = sanitize_file_name( $data['remote_filename'] );
    }
    if ( isset( $data['remote_size'] ) ) {
        $safe['remote_size'] = absint( $data['remote_size'] );
    }

    update_option( 'tnt_product_location_verification_' . $location_id, $safe, false );

    // Explicit verification is authoritative enough to invalidate any stale
    // live delivery-health decision. The next download will evaluate current
    // provider health instead of waiting for the runtime TTL to expire.
    if ( function_exists( 'tnt_clear_product_release_location_health_cache' ) ) {
        tnt_clear_product_release_location_health_cache( $location_id );
    }
}

/**
 * Remove the persisted administrator verification summary.
 *
 * @param int $location_id Location ID.
 * @return void
 */
function tnt_clear_release_location_verification_record( $location_id ) {
    $location_id = absint( $location_id );
    if ( $location_id ) {
        delete_option( 'tnt_product_location_verification_' . $location_id );
    }
}

/**
 * Return the short-lived derived verification cache for a Release Location.
 *
 * @param int $location_id Location ID.
 * @return array
 */
function tnt_get_release_location_verification_cache( $location_id ) {
    $location_id = absint( $location_id );
    if ( ! $location_id ) {
        return array();
    }

    $value = get_transient( 'tnt_product_location_verify_' . $location_id );

    return is_array( $value ) ? $value : array();
}

/**
 * Store short-lived derived verification information.
 *
 * This is operational cache only. It is not Release lifecycle truth.
 *
 * @param int   $location_id Location ID.
 * @param array $data Verification summary.
 * @return void
 */
function tnt_set_release_location_verification_cache( $location_id, $data ) {
    $location_id = absint( $location_id );
    if ( ! $location_id ) {
        return;
    }

    $data = is_array( $data ) ? $data : array();

    set_transient(
        'tnt_product_location_verify_' . $location_id,
        $data,
        5 * MINUTE_IN_SECONDS
    );

    tnt_set_release_location_verification_record( $location_id, $data );
}

/**
 * Remove derived verification cache.
 *
 * @param int $location_id Location ID.
 * @return void
 */
function tnt_clear_release_location_verification_cache( $location_id ) {
    $location_id = absint( $location_id );
    if ( $location_id ) {
        delete_transient( 'tnt_product_location_verify_' . $location_id );
    }
}

/**
 * Clear both the short-lived admin cache and persisted last-verification record.
 *
 * Use this when the provider identity/reference changes or the replica is removed.
 *
 * @param int $location_id Location ID.
 * @return void
 */
function tnt_clear_release_location_verification_state( $location_id ) {
    tnt_clear_release_location_verification_cache( $location_id );
    tnt_clear_release_location_verification_record( $location_id );
}

/**
 * Verify one registered Release Location against its provider when possible.
 *
 * Local reference validation always runs. Live metadata/health checks require a
 * centrally configured provider adapter. Provider secrets never enter this
 * result or the Release Location row.
 *
 * @param int $location_id Location ID.
 * @return array|WP_Error
 */
function tnt_verify_product_release_location( $location_id ) {
    $location = tnt_get_product_release_location( $location_id );
    if ( ! $location ) {
        return new WP_Error( 'tnt_release_location_not_found', __( 'Release location not found.', 'toolntip-core' ) );
    }

    $release = tnt_get_product_release( $location->release_id );
    if ( ! $release ) {
        return new WP_Error( 'tnt_release_not_found', __( 'Release not found.', 'toolntip-core' ) );
    }

    $reference = tnt_product_provider_validate_reference( $location->provider, $location->provider_file_id );
    if ( is_wp_error( $reference ) ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => 'invalid_reference',
                'checked_at' => current_time( 'mysql', true ),
            )
        );
        return $reference;
    }

    $readiness = tnt_get_product_provider_readiness( $location->provider );

    if ( empty( $readiness['configured'] ) ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => 'not_configured',
                'checked_at' => current_time( 'mysql', true ),
            )
        );

        return new WP_Error(
            'tnt_provider_not_configured',
            __( 'The provider reference is valid locally, but this storage provider is not configured for live verification yet.', 'toolntip-core' )
        );
    }

    $context = array(
        'release_id' => absint( $release->id ),
        'location_id' => absint( $location->id ),
    );

    $metadata = tnt_product_provider_get_metadata( $location->provider, $location->provider_file_id, $context );
    if ( is_wp_error( $metadata ) ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => 'metadata_failed',
                'checked_at' => current_time( 'mysql', true ),
            )
        );
        return $metadata;
    }

    $remote_filename = '';
    if ( isset( $metadata['filename'] ) ) {
        $remote_filename = sanitize_file_name( $metadata['filename'] );
    } elseif ( isset( $metadata['name'] ) ) {
        $remote_filename = sanitize_file_name( $metadata['name'] );
    }

    $remote_size = isset( $metadata['size'] ) ? absint( $metadata['size'] ) : 0;

    if ( $remote_filename && (string) $release->artifact_filename !== $remote_filename ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => 'filename_mismatch',
                'checked_at' => current_time( 'mysql', true ),
            )
        );
        return new WP_Error( 'tnt_release_location_filename_mismatch', __( 'Provider metadata filename does not match the canonical Release artifact filename.', 'toolntip-core' ) );
    }

    if ( $remote_size && absint( $release->artifact_size ) !== $remote_size ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => 'size_mismatch',
                'checked_at' => current_time( 'mysql', true ),
            )
        );
        return new WP_Error( 'tnt_release_location_size_mismatch', __( 'Provider metadata size does not match the canonical Release artifact size.', 'toolntip-core' ) );
    }

    $health = tnt_product_provider_health_check( $location->provider, $location->provider_file_id, $context );
    if ( is_wp_error( $health ) ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => 'health_failed',
                'checked_at' => current_time( 'mysql', true ),
            )
        );
        return $health;
    }

    $health_status = isset( $health['status'] ) ? sanitize_key( $health['status'] ) : 'healthy';
    if ( ! in_array( $health_status, array( 'healthy', 'ok', 'available' ), true ) ) {
        tnt_set_release_location_verification_cache(
            $location->id,
            array(
                'status'     => $health_status ?: 'unhealthy',
                'checked_at' => current_time( 'mysql', true ),
            )
        );
        return new WP_Error( 'tnt_release_location_unhealthy', __( 'The storage provider did not report this Release location as healthy.', 'toolntip-core' ) );
    }

    $summary = array(
        'status'          => 'healthy',
        'checked_at'      => current_time( 'mysql', true ),
        'remote_filename' => $remote_filename,
        'remote_size'     => $remote_size,
    );

    tnt_set_release_location_verification_cache( $location->id, $summary );

    $audit = tnt_record_product_release_audit(
        'release_location_verified',
        array(
            'product_id' => tnt_get_product_id_for_release( $release ),
            'release_id' => absint( $release->id ),
            'context'    => array(
                'location_id' => absint( $location->id ),
                'provider'    => sanitize_key( $location->provider ),
                'status'      => 'healthy',
            ),
        )
    );

    if ( is_wp_error( $audit ) ) {
        return $audit;
    }

    return $summary;
}

/**
 * Process one Release Location administration action.
 *
 * This service is shared by the traditional POST/Redirect/GET fallback and
 * the progressively enhanced admin-ajax endpoint so both paths enforce the
 * same repository, lifecycle and audit rules.
 *
 * @param array $request Request-like array.
 * @return array{result:mixed,notice:string,location_id:int,was_update:bool,action:string}
 */
function tnt_process_release_location_admin_action( $request ) {
    $action       = isset( $request['tnt_release_location_action'] ) ? sanitize_key( wp_unslash( $request['tnt_release_location_action'] ) ) : '';
    $release_id   = isset( $request['release_id'] ) ? absint( $request['release_id'] ) : 0;
    $location_id  = isset( $request['location_id'] ) ? absint( $request['location_id'] ) : 0;
    $result       = null;
    $notice       = '';
    $message      = '';
    $message_type = 'success';
    $provider     = '';
    $was_update   = false;

    if ( 'save_location' === $action ) {
        $was_update        = $location_id > 0;
        $previous_location = $was_update ? tnt_get_product_release_location( $location_id ) : null;
        $result = tnt_save_product_release_location(
            array(
                'release_id'       => $release_id,
                'provider'         => isset( $request['provider'] ) ? wp_unslash( $request['provider'] ) : '',
                'provider_file_id' => isset( $request['provider_file_id'] ) ? wp_unslash( $request['provider_file_id'] ) : '',
                'enabled'          => isset( $request['enabled'] ) ? 1 : 0,
                'priority'         => isset( $request['priority'] ) ? wp_unslash( $request['priority'] ) : 100,
                'weight'           => isset( $request['weight'] ) ? wp_unslash( $request['weight'] ) : 100,
            ),
            $location_id
        );

        if ( ! is_wp_error( $result ) ) {
            $location_id    = absint( $result );
            $saved_location = tnt_get_product_release_location( $location_id );
            $provider       = $saved_location ? sanitize_key( $saved_location->provider ) : '';

            $identity_changed = $previous_location && $saved_location && (
                (string) $previous_location->provider !== (string) $saved_location->provider ||
                (string) $previous_location->provider_file_id !== (string) $saved_location->provider_file_id
            );

            $routing_changed = $previous_location && $saved_location && (
                (int) $previous_location->enabled !== (int) $saved_location->enabled ||
                absint( $previous_location->priority ) !== absint( $saved_location->priority ) ||
                absint( $previous_location->weight ) !== absint( $saved_location->weight )
            );

            if ( ! $was_update || $identity_changed ) {
                // A new provider identity/reference must not inherit old state.
                tnt_clear_release_location_verification_state( $location_id );
                if ( function_exists( 'tnt_clear_product_release_location_health_cache' ) ) {
                    tnt_clear_product_release_location_health_cache( $location_id );
                }

                // Verification-aware save: immediately validate the stored replica.
                $verification = tnt_verify_product_release_location( $location_id );
                if ( is_wp_error( $verification ) ) {
                    $notice       = $was_update ? 'location_updated_verification_warning' : 'location_added_verification_warning';
                    $message_type = 'warning';
                    $message      = sprintf(
                        /* translators: 1: provider label, 2: verification error */
                        __( '%1$s replica %2$s, but automatic verification requires attention: %3$s', 'toolntip-core' ),
                        tnt_product_provider_admin_label( $provider ),
                        $was_update ? __( 'updated', 'toolntip-core' ) : __( 'added', 'toolntip-core' ),
                        $verification->get_error_message()
                    );
                } else {
                    $notice  = $was_update ? 'location_updated_verified' : 'location_added_verified';
                    $message = tnt_get_release_location_admin_notice_message( $notice, $provider );
                }
            } else {
                // Routing-only edits preserve the last explicit provider verification,
                // but the live delivery selector must immediately see new policy.
                if ( function_exists( 'tnt_clear_product_release_location_health_cache' ) ) {
                    tnt_clear_product_release_location_health_cache( $location_id );
                }

                $notice = $routing_changed ? 'location_routing_updated' : 'location_updated';
                $message = tnt_get_release_location_admin_notice_message( $notice, $provider );
            }
        }
    } elseif ( 'delete_location' === $action ) {
        $location = tnt_get_product_release_location( $location_id );
        if ( ! $location || absint( $location->release_id ) !== $release_id ) {
            $result = new WP_Error( 'tnt_release_location_not_found', __( 'Release location not found for this Release.', 'toolntip-core' ) );
        } else {
            $provider = sanitize_key( $location->provider );
            $result = tnt_delete_product_release_location( $location_id );
            if ( true === $result ) {
                tnt_clear_release_location_verification_state( $location_id );
                if ( function_exists( 'tnt_clear_product_release_location_health_cache' ) ) {
                    tnt_clear_product_release_location_health_cache( $location_id );
                }
                $location_id = 0;
                $notice      = 'location_removed';
                $message     = tnt_get_release_location_admin_notice_message( $notice, $provider );
            }
        }
    } elseif ( 'verify_location' === $action ) {
        $location = tnt_get_product_release_location( $location_id );
        if ( ! $location || absint( $location->release_id ) !== $release_id ) {
            $result = new WP_Error( 'tnt_release_location_not_found', __( 'Release location not found for this Release.', 'toolntip-core' ) );
        } else {
            $provider = sanitize_key( $location->provider );
            $result = tnt_verify_product_release_location( $location_id );
            if ( is_wp_error( $result ) ) {
                $result = new WP_Error(
                    $result->get_error_code(),
                    sprintf(
                        /* translators: 1: provider label, 2: provider-safe verification reason */
                        __( '%1$s replica verification failed: %2$s', 'toolntip-core' ),
                        tnt_product_provider_admin_label( $provider ),
                        $result->get_error_message()
                    )
                );
            } else {
                $notice  = 'location_verified';
                $message = tnt_get_release_location_admin_notice_message( $notice, $provider );
            }
        }
    } else {
        $result = new WP_Error( 'tnt_release_location_invalid_action', __( 'Unknown storage replica action.', 'toolntip-core' ) );
    }

    return array(
        'result'       => $result,
        'notice'       => $notice,
        'message'      => $message,
        'message_type' => $message_type,
        'provider'     => $provider,
        'location_id'  => $location_id,
        'was_update'   => $was_update,
        'action'       => $action,
    );
}

/**
 * Return a storage-replica notice message by notice key.
 *
 * @param string $notice_key Notice key.
 * @return string
 */
function tnt_get_release_location_admin_notice_message( $notice_key, $provider = '' ) {
    $notice_key     = sanitize_key( $notice_key );
    $provider_label = $provider ? tnt_product_provider_admin_label( $provider ) : __( 'Storage', 'toolntip-core' );

    $map = array(
        'location_added_verified'              => sprintf( __( '%s replica added and verified successfully.', 'toolntip-core' ), $provider_label ),
        'location_updated_verified'            => sprintf( __( '%s replica updated and verified successfully.', 'toolntip-core' ), $provider_label ),
        'location_added_verification_warning'  => sprintf( __( '%s replica was added, but automatic verification requires attention.', 'toolntip-core' ), $provider_label ),
        'location_updated_verification_warning'=> sprintf( __( '%s replica was updated, but automatic verification requires attention.', 'toolntip-core' ), $provider_label ),
        'location_routing_updated'             => sprintf( __( '%s routing settings updated successfully. Existing verification status was preserved and delivery routing state was refreshed.', 'toolntip-core' ), $provider_label ),
        'location_updated'                     => sprintf( __( '%s replica updated successfully. Existing verification status was preserved.', 'toolntip-core' ), $provider_label ),
        'location_removed'                     => sprintf( __( '%s replica removed successfully. The external provider file was not deleted.', 'toolntip-core' ), $provider_label ),
        'location_verified'                    => sprintf( __( '%s replica verified successfully. Verification status, timestamp and routing health were refreshed.', 'toolntip-core' ), $provider_label ),
    );

    return isset( $map[ $notice_key ] )
        ? $map[ $notice_key ]
        : __( 'Storage replica operation completed.', 'toolntip-core' );
}

/**
 * Handle Release Location administration actions using the no-JavaScript
 * POST/Redirect/GET fallback.
 *
 * @return void
 */
function tnt_handle_release_location_admin_actions() {
    if ( ! is_admin() || wp_doing_ajax() || empty( $_POST['tnt_release_location_action'] ) ) {
        return;
    }

    if ( ! tnt_current_user_can_manage_release_locations() ) {
        wp_die( esc_html__( 'You are not allowed to manage Product Release storage locations.', 'toolntip-core' ) );
    }

    check_admin_referer( 'tnt_release_location_admin', 'tnt_release_location_nonce' );

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $edition_id = isset( $_POST['edition_id'] ) ? absint( $_POST['edition_id'] ) : 0;
    $release_id = isset( $_POST['release_id'] ) ? absint( $_POST['release_id'] ) : 0;
    $processed  = tnt_process_release_location_admin_action( $_POST );
    $result     = $processed['result'];

    $args = array(
        'product_id' => $product_id,
        'edition_id' => $edition_id,
        'release_id' => $release_id,
    );

    if ( is_wp_error( $result ) ) {
        if ( 'save_location' === $processed['action'] && $processed['was_update'] && $processed['location_id'] ) {
            $args['location_id'] = $processed['location_id'];
        }
        $args['tnt_storage_error'] = rawurlencode( $result->get_error_message() );
    } else {
        $args['tnt_storage_notice'] = $processed['notice'] ? $processed['notice'] : 'saved';
        if ( ! empty( $processed['provider'] ) ) {
            $args['tnt_storage_provider'] = sanitize_key( $processed['provider'] );
        }
        if ( 'warning' === $processed['message_type'] && $processed['message'] ) {
            $args['tnt_storage_warning'] = rawurlencode( $processed['message'] );
        }
    }

    $redirect_url = tnt_product_release_admin_url( $args ) . '#storage-replicas';
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_init', 'tnt_handle_release_location_admin_actions' );

/**
 * Handle the progressively enhanced Release Location admin request.
 *
 * Server-side capability, nonce, repository and lifecycle checks remain the
 * authority. JavaScript only avoids reconstructing the entire admin page.
 *
 * @return void
 */
function tnt_ajax_release_location_admin_action() {
    if ( ! tnt_current_user_can_manage_release_locations() ) {
        wp_send_json_error(
            array( 'message' => __( 'You are not allowed to manage Product Release storage locations.', 'toolntip-core' ) ),
            403
        );
    }

    check_ajax_referer( 'tnt_release_location_admin', 'tnt_release_location_nonce' );

    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $edition_id = isset( $_POST['edition_id'] ) ? absint( $_POST['edition_id'] ) : 0;
    $release_id = isset( $_POST['release_id'] ) ? absint( $_POST['release_id'] ) : 0;
    $edition    = $edition_id ? tnt_get_product_edition( $edition_id ) : null;
    $release    = $release_id ? tnt_get_product_release( $release_id ) : null;

    if (
        ! $product_id ||
        ! $edition ||
        ! $release ||
        absint( $edition->product_id ) !== $product_id ||
        absint( $release->edition_id ) !== $edition_id
    ) {
        wp_send_json_error(
            array( 'message' => __( 'The Product, Edition or Release context is invalid.', 'toolntip-core' ) ),
            400
        );
    }

    $processed = tnt_process_release_location_admin_action( $_POST );
    $result    = $processed['result'];
    $ui_state  = array();

    if ( is_wp_error( $result ) ) {
        $ui_state['error'] = $result->get_error_message();

        if ( 'save_location' === $processed['action'] && $processed['was_update'] && $processed['location_id'] ) {
            $ui_state['location_id'] = $processed['location_id'];
        }
    } else {
        $ui_state['notice']   = $processed['notice'] ? $processed['notice'] : 'saved';
        $ui_state['provider'] = $processed['provider'];
        if ( 'warning' === $processed['message_type'] && $processed['message'] ) {
            $ui_state['warning'] = $processed['message'];
        }
    }

    ob_start();
    tnt_render_release_location_admin_section( $product_id, $edition, $release, $ui_state );
    $html = ob_get_clean();

    $payload = array(
        'html'                 => $html,
        'message'              => is_wp_error( $result )
            ? $result->get_error_message()
            : ( $processed['message'] ?: tnt_get_release_location_admin_notice_message( $processed['notice'], $processed['provider'] ) ),
        'messageType'          => is_wp_error( $result ) ? 'error' : $processed['message_type'],
        'releaseId'            => absint( $release_id ),
        'replicaCount'         => count( tnt_get_product_release_locations( $release_id, false ) ),
        'enabledReplicaCount'  => count( tnt_get_product_release_locations( $release_id, true ) ),
    );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $payload, 400 );
    }

    wp_send_json_success( $payload );
}
add_action( 'wp_ajax_tnt_release_location_admin_action', 'tnt_ajax_release_location_admin_action' );

/**
 * Enqueue the Storage Replicas progressive-enhancement script only on the
 * Product Editions & Releases administration screen.
 *
 * @param string $hook_suffix Current admin hook suffix.
 * @return void
 */
function tnt_enqueue_release_location_admin_assets( $hook_suffix ) {
    if ( 'tnt_product_page_tnt-product-releases' !== $hook_suffix ) {
        return;
    }

    wp_enqueue_script(
        'tnt-release-location-admin',
        TNT_CORE_URL . 'assets/js/release-location-admin.js',
        array(),
        TNT_CORE_VERSION,
        true
    );

    wp_localize_script(
        'tnt-release-location-admin',
        'tntReleaseLocationAdmin',
        array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'ajaxAction'  => 'tnt_release_location_admin_action',
            'workingText'      => __( 'Working…', 'toolntip-core' ),
            'errorText'        => __( 'The storage replica operation could not be completed.', 'toolntip-core' ),
            'addHeading'       => __( 'Add Storage Replica', 'toolntip-core' ),
            'editHeading'      => __( 'Edit Storage Replica', 'toolntip-core' ),
            'addButton'        => __( 'Add Storage Replica', 'toolntip-core' ),
            'updateButton'     => __( 'Update Storage Replica', 'toolntip-core' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'tnt_enqueue_release_location_admin_assets' );

/**
 * Return a human-readable derived verification state.
 *
 * @param object $location Release Location row.
 * @return string
 */
function tnt_get_release_location_admin_health_label( $location ) {
    $readiness = tnt_get_product_provider_readiness( $location->provider );
    if ( empty( $readiness['registered'] ) ) {
        return __( 'Provider unavailable', 'toolntip-core' );
    }
    if ( empty( $readiness['configured'] ) ) {
        return __( 'Not configured', 'toolntip-core' );
    }

    $cache = tnt_get_release_location_verification_cache( $location->id );
    if ( empty( $cache['status'] ) ) {
        $cache = tnt_get_release_location_verification_record( $location->id );
    }
    if ( empty( $cache['status'] ) ) {
        return __( 'Not checked', 'toolntip-core' );
    }

    $map = array(
        'healthy'           => __( 'Healthy', 'toolntip-core' ),
        'invalid_reference' => __( 'Invalid reference', 'toolntip-core' ),
        'metadata_failed'   => __( 'Metadata check failed', 'toolntip-core' ),
        'filename_mismatch' => __( 'Filename mismatch', 'toolntip-core' ),
        'size_mismatch'     => __( 'Size mismatch', 'toolntip-core' ),
        'health_failed'     => __( 'Health check failed', 'toolntip-core' ),
        'not_configured'    => __( 'Not configured', 'toolntip-core' ),
    );

    return isset( $map[ $cache['status'] ] ) ? $map[ $cache['status'] ] : ucwords( str_replace( '_', ' ', sanitize_key( $cache['status'] ) ) );
}

/**
 * Return the localized timestamp of the last explicit verification.
 *
 * @param object $location Release Location row.
 * @return string
 */
function tnt_get_release_location_admin_verified_at( $location ) {
    $record = tnt_get_release_location_verification_record( $location->id );
    if ( empty( $record['checked_at'] ) ) {
        return __( 'Never', 'toolntip-core' );
    }

    $timestamp = strtotime( $record['checked_at'] . ' UTC' );
    if ( ! $timestamp ) {
        return __( 'Unknown', 'toolntip-core' );
    }

    return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp, wp_timezone() );
}

/**
 * Render the Release storage-replica administration panel.
 *
 * @param int    $product_id Product ID.
 * @param object $edition Edition row.
 * @param object $release Release row.
 * @return void
 */
function tnt_render_release_location_admin_section( $product_id, $edition, $release, $ui_state = array() ) {
    if ( ! $release || ! tnt_current_user_can_manage_release_locations() ) {
        return;
    }

    $location_id = isset( $ui_state['location_id'] )
        ? absint( $ui_state['location_id'] )
        : ( isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0 );
    $editing = $location_id ? tnt_get_product_release_location( $location_id ) : null;

    if ( $editing && absint( $editing->release_id ) !== absint( $release->id ) ) {
        $editing = null;
        $location_id = 0;
    }

    $locations = tnt_get_product_release_locations( $release->id, false );
    $adapters  = tnt_get_product_provider_adapters();
    ?>
    <hr>
    <section id="storage-replicas" class="tnt-storage-replicas" data-tnt-storage-replicas>
    <h2><?php esc_html_e( 'Storage Replicas', 'toolntip-core' ); ?></h2>
    <?php
    $storage_error = '';
    if ( ! empty( $ui_state['error'] ) ) {
        $storage_error = sanitize_text_field( $ui_state['error'] );
    } elseif ( ! empty( $_GET['tnt_storage_error'] ) ) {
        $storage_error = rawurldecode( sanitize_text_field( wp_unslash( $_GET['tnt_storage_error'] ) ) );
    }

    $storage_notice_key = '';
    if ( ! empty( $ui_state['notice'] ) ) {
        $storage_notice_key = sanitize_key( $ui_state['notice'] );
    } elseif ( ! empty( $_GET['tnt_storage_notice'] ) ) {
        $storage_notice_key = sanitize_key( wp_unslash( $_GET['tnt_storage_notice'] ) );
    }

    $storage_provider = '';
    if ( ! empty( $ui_state['provider'] ) ) {
        $storage_provider = sanitize_key( $ui_state['provider'] );
    } elseif ( ! empty( $_GET['tnt_storage_provider'] ) ) {
        $storage_provider = sanitize_key( wp_unslash( $_GET['tnt_storage_provider'] ) );
    }

    $storage_warning = '';
    if ( ! empty( $ui_state['warning'] ) ) {
        $storage_warning = sanitize_text_field( $ui_state['warning'] );
    } elseif ( ! empty( $_GET['tnt_storage_warning'] ) ) {
        $storage_warning = rawurldecode( sanitize_text_field( wp_unslash( $_GET['tnt_storage_warning'] ) ) );
    }

    echo '<div class="tnt-storage-replicas__notice" data-tnt-storage-notice aria-live="polite">';
    if ( $storage_error ) {
        echo '<div class="notice notice-error inline"><p>' . esc_html( $storage_error ) . '</p></div>';
    } elseif ( $storage_warning ) {
        echo '<div class="notice notice-warning inline is-dismissible"><p>' . esc_html( $storage_warning ) . '</p></div>';
    } elseif ( $storage_notice_key ) {
        echo '<div class="notice notice-success inline is-dismissible"><p>' . esc_html( tnt_get_release_location_admin_notice_message( $storage_notice_key, $storage_provider ) ) . '</p></div>';
    }
    echo '</div>';
    ?>
    <p><?php esc_html_e( 'Register provider file references for this Release. ToolNTip stores references and routing policy only; external artifact bytes remain with the provider.', 'toolntip-core' ); ?></p>

    <?php if ( $locations ) : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Provider', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'File reference', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Enabled', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Priority', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Weight', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Verification', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Last checked', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'toolntip-core' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $locations as $location ) : ?>
                <tr>
                    <td><?php echo esc_html( tnt_product_provider_admin_label( $location->provider ) ); ?></td>
                    <td><code><?php echo esc_html( $location->provider_file_id ); ?></code></td>
                    <td><?php echo (int) $location->enabled ? esc_html__( 'Yes', 'toolntip-core' ) : esc_html__( 'No', 'toolntip-core' ); ?></td>
                    <td><?php echo esc_html( absint( $location->priority ) ); ?></td>
                    <td><?php echo esc_html( absint( $location->weight ) ); ?></td>
                    <td><?php echo esc_html( tnt_get_release_location_admin_health_label( $location ) ); ?></td>
                    <td><?php echo esc_html( tnt_get_release_location_admin_verified_at( $location ) ); ?></td>
                    <td>
                        <a
                            class="button button-small"
                            data-tnt-storage-edit
                            data-location-id="<?php echo esc_attr( $location->id ); ?>"
                            data-provider="<?php echo esc_attr( $location->provider ); ?>"
                            data-file-reference="<?php echo esc_attr( $location->provider_file_id ); ?>"
                            data-enabled="<?php echo esc_attr( (int) $location->enabled ); ?>"
                            data-priority="<?php echo esc_attr( absint( $location->priority ) ); ?>"
                            data-weight="<?php echo esc_attr( absint( $location->weight ) ); ?>"
                            href="<?php echo esc_url( tnt_product_release_admin_url( array( 'product_id' => $product_id, 'edition_id' => $edition->id, 'release_id' => $release->id, 'location_id' => $location->id ) ) . '#storage-replicas' ); ?>"
                        ><?php esc_html_e( 'Edit', 'toolntip-core' ); ?></a>
                        <?php tnt_release_location_admin_action_form( 'verify_location', __( 'Verify', 'toolntip-core' ), $product_id, $edition->id, $release->id, $location->id ); ?>
                        <?php tnt_release_location_admin_action_form( 'delete_location', __( 'Remove', 'toolntip-core' ), $product_id, $edition->id, $release->id, $location->id, true ); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php esc_html_e( 'No storage replicas are registered for this Release.', 'toolntip-core' ); ?></p>
    <?php endif; ?>

    <h3 data-tnt-storage-form-heading><?php echo $editing ? esc_html__( 'Edit Storage Replica', 'toolntip-core' ) : esc_html__( 'Add Storage Replica', 'toolntip-core' ); ?></h3>
    <form method="post" data-tnt-storage-form>
        <?php wp_nonce_field( 'tnt_release_location_admin', 'tnt_release_location_nonce' ); ?>
        <input type="hidden" name="tnt_release_location_action" value="save_location">
        <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
        <input type="hidden" name="edition_id" value="<?php echo esc_attr( $edition->id ); ?>">
        <input type="hidden" name="release_id" value="<?php echo esc_attr( $release->id ); ?>">
        <input type="hidden" name="location_id" value="<?php echo esc_attr( $editing ? $editing->id : 0 ); ?>">
        <table class="form-table">
            <tr>
                <th><label for="tnt-release-location-provider"><?php esc_html_e( 'Provider', 'toolntip-core' ); ?></label></th>
                <td>
                    <select id="tnt-release-location-provider" name="provider" required>
                        <?php foreach ( $adapters as $key => $adapter ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $editing ? $editing->provider : 'google_drive', $key ); ?>><?php echo esc_html( $adapter['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Provider credentials are configured centrally and are never stored on this Release.', 'toolntip-core' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="tnt-release-location-file-id"><?php esc_html_e( 'Provider file reference', 'toolntip-core' ); ?></label></th>
                <td>
                    <input id="tnt-release-location-file-id" class="large-text" name="provider_file_id" maxlength="512" required value="<?php echo esc_attr( $editing ? $editing->provider_file_id : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Store the provider-specific durable file/item ID, not a public share URL.', 'toolntip-core' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Enabled', 'toolntip-core' ); ?></th>
                <td><label><input type="checkbox" name="enabled" value="1" <?php checked( $editing ? (int) $editing->enabled : 1, 1 ); ?>> <?php esc_html_e( 'Participate in delivery selection', 'toolntip-core' ); ?></label></td>
            </tr>
            <tr>
                <th><label for="tnt-release-location-priority"><?php esc_html_e( 'Priority', 'toolntip-core' ); ?></label></th>
                <td><input id="tnt-release-location-priority" type="number" min="1" name="priority" value="<?php echo esc_attr( $editing ? absint( $editing->priority ) : 100 ); ?>"><p class="description"><?php esc_html_e( 'Lower numbers are preferred. Equal priority locations are load-balanced by weight.', 'toolntip-core' ); ?></p></td>
            </tr>
            <tr>
                <th><label for="tnt-release-location-weight"><?php esc_html_e( 'Weight', 'toolntip-core' ); ?></label></th>
                <td><input id="tnt-release-location-weight" type="number" min="1" name="weight" value="<?php echo esc_attr( $editing ? absint( $editing->weight ) : 100 ); ?>"><p class="description"><?php esc_html_e( 'Relative traffic share among healthy locations with the same priority.', 'toolntip-core' ); ?></p></td>
            </tr>
        </table>
        <?php submit_button( $editing ? __( 'Update Storage Replica', 'toolntip-core' ) : __( 'Add Storage Replica', 'toolntip-core' ) ); ?>
        <a class="button" data-tnt-storage-cancel <?php echo $editing ? '' : 'hidden'; ?> href="<?php echo esc_url( tnt_product_release_admin_url( array( 'product_id' => $product_id, 'edition_id' => $edition->id, 'release_id' => $release->id ) ) . '#storage-replicas' ); ?>"><?php esc_html_e( 'Cancel Edit', 'toolntip-core' ); ?></a>
    </form>

    <p class="description"><?php esc_html_e( 'New replicas and provider/file-reference changes are verified automatically when saved. Priority, weight and enabled-state changes refresh delivery routing immediately without repeating provider API verification. Manual Verify remains available for on-demand checks.', 'toolntip-core' ); ?></p>
    </section>
    <?php
}

/**
 * Render a compact Release Location action form.
 *
 * @param string $action Action key.
 * @param string $label Button label.
 * @param int    $product_id Product ID.
 * @param int    $edition_id Edition ID.
 * @param int    $release_id Release ID.
 * @param int    $location_id Location ID.
 * @param bool   $confirm Whether to require confirmation.
 * @return void
 */
function tnt_release_location_admin_action_form( $action, $label, $product_id, $edition_id, $release_id, $location_id, $confirm = false ) {
    ?>
    <form method="post" style="display:inline" data-tnt-storage-form <?php if ( $confirm ) : ?>onsubmit="return confirm('<?php echo esc_js( __( 'Remove this storage replica registration? The external provider file will not be deleted.', 'toolntip-core' ) ); ?>');"<?php endif; ?>>
        <?php wp_nonce_field( 'tnt_release_location_admin', 'tnt_release_location_nonce' ); ?>
        <input type="hidden" name="tnt_release_location_action" value="<?php echo esc_attr( $action ); ?>">
        <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
        <input type="hidden" name="edition_id" value="<?php echo esc_attr( $edition_id ); ?>">
        <input type="hidden" name="release_id" value="<?php echo esc_attr( $release_id ); ?>">
        <input type="hidden" name="location_id" value="<?php echo esc_attr( $location_id ); ?>">
        <?php submit_button( $label, 'secondary small', '', false ); ?>
    </form>
    <?php
}
