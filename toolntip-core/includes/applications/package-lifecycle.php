<?php
/**
 * ToolNTip Application Package Version Lifecycle.
 *
 * Explicitly switches a package-backed application's active installed version.
 * Installation remains side-by-side; switching never mutates the governed Page
 * or its Page -> Tool relationship.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Preflight an installed version before it may become active. */
function tnt_preflight_application_package_version( $application_id, $version ) {
    $application_id = sanitize_key( (string) $application_id );
    $version = (string) $version;
    $record = tnt_get_installed_application_version( $application_id, $version, true );
    if ( ! is_array( $record ) ) {
        return new WP_Error( 'tnt_package_version_not_installed', __( 'The requested application version is not a valid installed package.', 'toolntip-core' ) );
    }

    $entry = wp_normalize_path( $record['path'] . '/' . $record['manifest']['runtime']['entry'] );
    $html = @file_get_contents( $entry );
    $html_check = tnt_validate_application_package_runtime_html( false === $html ? '' : $html );
    if ( is_wp_error( $html_check ) ) {
        return $html_check;
    }

    foreach ( array_merge( $record['manifest']['assets']['styles'], $record['manifest']['assets']['scripts'] ) as $relative ) {
        if ( '' === tnt_get_application_package_file_url( $application_id, $version, $relative ) ) {
            return new WP_Error( 'tnt_package_version_asset_unavailable', __( 'A manifest-declared runtime asset could not be resolved safely.', 'toolntip-core' ) );
        }
    }

    return $record;
}

/** Explicitly select one valid installed package version. */
function tnt_activate_application_package_version( $application_id, $version ) {
    $application_id = sanitize_key( (string) $application_id );
    $version = (string) $version;
    $record = tnt_preflight_application_package_version( $application_id, $version );
    if ( is_wp_error( $record ) ) {
        return $record;
    }

    $selected = tnt_get_application_package_active_versions();
    $previous = isset( $selected[ $application_id ] ) ? (string) $selected[ $application_id ] : '';
    if ( $previous === $version ) {
        return array( 'application_id' => $application_id, 'version' => $version, 'previous_version' => $previous, 'changed' => false );
    }

    $selected[ $application_id ] = $version;
    if ( ! update_option( 'tnt_application_package_active_versions', $selected, false ) ) {
        // update_option() also returns false when unchanged, handled above.
        $verify = tnt_get_application_package_active_versions();
        if ( ! isset( $verify[ $application_id ] ) || $verify[ $application_id ] !== $version ) {
            return new WP_Error( 'tnt_package_version_activation_failed', __( 'The active application version could not be saved. The previous version remains selected.', 'toolntip-core' ) );
        }
    }

    return array( 'application_id' => $application_id, 'version' => $version, 'previous_version' => $previous, 'changed' => true );
}

/** Handle governed administrator version switching. */
function tnt_handle_application_package_version_activation() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to manage ToolNTip application versions.', 'toolntip-core' ) );
    }
    $application_id = isset( $_POST['application_id'] ) ? sanitize_key( wp_unslash( $_POST['application_id'] ) ) : '';
    $version = isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : '';
    check_admin_referer( 'tnt_activate_application_package_version_' . $application_id . '_' . $version );

    $result = tnt_activate_application_package_version( $application_id, $version );
    $args = array( 'post_type' => 'tool', 'page' => 'tnt-applications' );
    if ( is_wp_error( $result ) ) {
        $args['tnt_lifecycle'] = 'error';
        $args['tnt_message'] = rawurlencode( $result->get_error_message() );
    } else {
        $args['tnt_lifecycle'] = 'activated';
        $args['tnt_app'] = $application_id;
        $args['tnt_version'] = $version;
        if ( ! empty( $result['previous_version'] ) ) {
            $args['tnt_previous'] = $result['previous_version'];
        }
    }
    wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php' ) ) );
    exit;
}
add_action( 'admin_post_tnt_activate_application_package_version', 'tnt_handle_application_package_version_activation' );
/**
 * Governed uninstall for one package-backed application.
 *
 * Package files and package binding are removed, while the WordPress Tool,
 * runtime Page, Page -> Tool relationship, and editorial content are preserved.
 * The runtime Page is returned to Draft so a removed runtime cannot remain live.
 */
function tnt_uninstall_application_package( $application_id ) {
    $application_id = sanitize_key( (string) $application_id );
    if ( '' === $application_id ) {
        return new WP_Error( 'tnt_package_uninstall_invalid_id', __( 'A valid application ID is required.', 'toolntip-core' ) );
    }

    $app = tnt_get_installed_application( $application_id, true );
    if ( ! $app || empty( $app['versions'] ) ) {
        return new WP_Error( 'tnt_package_uninstall_not_installed', __( 'The application is not installed.', 'toolntip-core' ) );
    }

    $storage = tnt_get_application_package_storage();
    if ( is_wp_error( $storage ) ) {
        return $storage;
    }
    $app_dir = wp_normalize_path( $storage['root'] . '/' . $application_id );
    $root = trailingslashit( wp_normalize_path( $storage['root'] ) );
    if ( 0 !== strpos( trailingslashit( $app_dir ), $root ) || ! is_dir( $app_dir ) || is_link( $app_dir ) ) {
        return new WP_Error( 'tnt_package_uninstall_path_invalid', __( 'The managed application directory could not be verified safely.', 'toolntip-core' ) );
    }

    $page_id = tnt_get_application_package_page_id( $application_id );
    if ( ! $page_id ) {
        $page_id = tnt_find_application_package_page_by_meta( $application_id );
    }
    $page_status = $page_id ? get_post_status( $page_id ) : false;
    $page_binding = $page_id ? get_post_meta( $page_id, '_tnt_application_package_id', true ) : '';
    $active = tnt_get_application_package_active_versions();
    $page_map = tnt_get_application_package_page_map();
    $had_active = array_key_exists( $application_id, $active );
    $old_active = $had_active ? $active[ $application_id ] : null;
    $had_map = array_key_exists( $application_id, $page_map );
    $old_map = $had_map ? $page_map[ $application_id ] : null;

    // Make the runtime unavailable before destructive filesystem work.
    unset( $active[ $application_id ] );
    update_option( 'tnt_application_package_active_versions', $active, false );
    $verify_active = tnt_get_application_package_active_versions();
    if ( isset( $verify_active[ $application_id ] ) ) {
        return new WP_Error( 'tnt_package_uninstall_deactivate_failed', __( 'The application could not be deactivated. No package files were removed.', 'toolntip-core' ) );
    }

    $rollback = function() use ( $application_id, $page_id, $page_status, $page_binding, $had_active, $old_active, $had_map, $old_map ) {
        $active_restore = tnt_get_application_package_active_versions();
        if ( $had_active ) { $active_restore[ $application_id ] = $old_active; } else { unset( $active_restore[ $application_id ] ); }
        update_option( 'tnt_application_package_active_versions', $active_restore, false );
        $map_restore = tnt_get_application_package_page_map();
        if ( $had_map ) { $map_restore[ $application_id ] = $old_map; } else { unset( $map_restore[ $application_id ] ); }
        update_option( 'tnt_application_package_pages', $map_restore, false );
        if ( $page_id ) {
            if ( '' !== $page_binding ) { update_post_meta( $page_id, '_tnt_application_package_id', $page_binding ); }
            if ( $page_status && get_post_status( $page_id ) !== $page_status ) {
                wp_update_post( array( 'ID' => $page_id, 'post_status' => $page_status ) );
            }
        }
    };

    if ( $page_id ) {
        $drafted = wp_update_post( array( 'ID' => $page_id, 'post_status' => 'draft' ), true );
        if ( is_wp_error( $drafted ) ) {
            $rollback();
            return new WP_Error( 'tnt_package_uninstall_page_draft_failed', __( 'The runtime Page could not be returned to Draft. The application remains installed.', 'toolntip-core' ) );
        }
        // Preserve the governed package ownership marker on the Draft Page.
        // Active-version state and the package Page map are removed below, so
        // the runtime remains unavailable. Keeping this non-executable marker
        // allows a later reinstall to re-adopt the exact same WordPress Page.
        update_post_meta( $page_id, '_tnt_application_package_id', $application_id );
    }

    unset( $page_map[ $application_id ] );
    update_option( 'tnt_application_package_pages', $page_map, false );
    $verify_map = tnt_get_application_package_page_map();
    if ( isset( $verify_map[ $application_id ] ) || ( $page_id && $application_id !== sanitize_key( (string) get_post_meta( $page_id, '_tnt_application_package_id', true ) ) ) ) {
        $rollback();
        return new WP_Error( 'tnt_package_uninstall_binding_failed', __( 'The package Page ownership marker could not be preserved safely. The application remains installed.', 'toolntip-core' ) );
    }

    if ( ! is_dir( $storage['staging'] ) && ! wp_mkdir_p( $storage['staging'] ) ) {
        $rollback();
        return new WP_Error( 'tnt_package_uninstall_staging_failed', __( 'The uninstall transaction area could not be prepared. The application remains installed.', 'toolntip-core' ) );
    }
    $quarantine = wp_normalize_path( $storage['staging'] . '/uninstall-' . $application_id . '-' . wp_generate_uuid4() );
    if ( ! @rename( $app_dir, $quarantine ) ) {
        $rollback();
        return new WP_Error( 'tnt_package_uninstall_quarantine_failed', __( 'The application files could not be moved into the uninstall transaction. The application remains installed.', 'toolntip-core' ) );
    }

    // Metadata state is committed and package discovery can no longer see the app.
    // Physical deletion is last; stale transaction housekeeping can safely finish
    // cleanup if the server cannot remove every quarantined file immediately.
    $cleanup_complete = tnt_application_package_remove_tree( $quarantine );

    return array(
        'application_id' => $application_id,
        'page_id' => absint( $page_id ),
        'page_preserved' => (bool) $page_id,
        'cleanup_complete' => (bool) $cleanup_complete,
    );
}

/** Handle the final, nonce-protected administrator uninstall confirmation. */
function tnt_handle_application_package_uninstall() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to uninstall ToolNTip applications.', 'toolntip-core' ) );
    }
    $application_id = isset( $_POST['application_id'] ) ? sanitize_key( wp_unslash( $_POST['application_id'] ) ) : '';
    check_admin_referer( 'tnt_uninstall_application_package_' . $application_id );
    $result = tnt_uninstall_application_package( $application_id );
    $args = array( 'post_type' => 'tool', 'page' => 'tnt-applications' );
    if ( is_wp_error( $result ) ) {
        $args['tnt_lifecycle'] = 'error';
        $args['tnt_message'] = rawurlencode( $result->get_error_message() );
    } else {
        $args['tnt_lifecycle'] = 'uninstalled';
        $args['tnt_app'] = $application_id;
        if ( empty( $result['cleanup_complete'] ) ) { $args['tnt_cleanup'] = 'pending'; }
    }
    wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php' ) ) );
    exit;
}
add_action( 'admin_post_tnt_uninstall_application_package', 'tnt_handle_application_package_uninstall' );
