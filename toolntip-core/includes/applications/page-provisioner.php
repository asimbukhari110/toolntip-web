<?php
/**
 * ToolNTip package-backed Application Page provisioner.
 *
 * Creates exactly one Draft Page for each installed application package using
 * governed manifest metadata. Tool association remains a later checkpoint.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_get_application_package_page_map() {
    $map = get_option( 'tnt_application_package_pages', array() );
    return is_array( $map ) ? $map : array();
}

function tnt_get_application_package_page_id( $application_id ) {
    $application_id = sanitize_key( (string) $application_id );
    $map = tnt_get_application_package_page_map();
    $page_id = isset( $map[ $application_id ] ) ? absint( $map[ $application_id ] ) : 0;
    if ( $page_id && 'page' === get_post_type( $page_id ) ) {
        return $page_id;
    }
    return 0;
}

function tnt_find_application_package_page_by_meta( $application_id ) {
    $posts = get_posts( array(
        'post_type' => 'page', 'post_status' => array( 'draft', 'pending', 'private', 'publish', 'future', 'trash' ),
        'posts_per_page' => 2, 'fields' => 'ids', 'no_found_rows' => true,
        'meta_key' => '_tnt_application_package_id', 'meta_value' => sanitize_key( (string) $application_id ),
    ) );
    return 1 === count( $posts ) ? absint( $posts[0] ) : 0;
}

function tnt_application_page_slug_owner( $slug, $exclude_id = 0 ) {
    global $wpdb;
    $slug = sanitize_title( (string) $slug );
    if ( '' === $slug ) { return 0; }
    $sql = "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND ID <> %d LIMIT 1";
    return absint( $wpdb->get_var( $wpdb->prepare( $sql, $slug, absint( $exclude_id ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Safely adopt an existing application Page whose slug matches a package.
 *
 * Adoption is deliberately strict: the slug owner must be a Page already using
 * the governed application shell and linked to a Tool whose configured runtime
 * ID matches the package ID. This prevents arbitrary same-slug content from
 * being claimed by a package while allowing production runtimes to migrate
 * without URL or post-identity changes.
 */
function tnt_try_adopt_existing_application_page( $application_id, $slug ) {
    $application_id = sanitize_key( (string) $application_id );
    $owner_id = tnt_application_page_slug_owner( $slug );
    if ( ! $owner_id || 'page' !== get_post_type( $owner_id ) ) {
        return new WP_Error( 'tnt_application_page_adoption_unavailable', __( 'The existing slug owner is not an adoptable application Page.', 'toolntip-core' ) );
    }
    $page = get_post( $owner_id );
    if ( ! $page instanceof WP_Post || false === strpos( (string) $page->post_content, '[tnt_application_shell]' ) ) {
        return new WP_Error( 'tnt_application_page_adoption_shell_missing', __( 'The existing Page does not use the governed ToolNTip application shell.', 'toolntip-core' ) );
    }
    $tool_id = absint( get_post_meta( $owner_id, '_tnt_tool_context_id', true ) );
    if ( ! $tool_id || 'tool' !== get_post_type( $tool_id ) ) {
        return new WP_Error( 'tnt_application_page_adoption_tool_missing', __( 'The existing application Page is not linked to a valid Tool.', 'toolntip-core' ) );
    }
    $runtime_id = function_exists( 'tnt_get_tool_application_config_value' )
        ? sanitize_key( (string) tnt_get_tool_application_config_value( $tool_id, 'runtime_module' ) )
        : sanitize_key( (string) get_post_meta( $tool_id, 'runtime_module', true ) );
    if ( $application_id !== $runtime_id ) {
        // Backward-compatible recovery for Pages preserved by the first
        // uninstall implementation (dev39), which removed the package marker.
        // Keep this deliberately narrow: only a Draft Page using both governed
        // shell shortcodes and already linked to a valid Tool may be reclaimed.
        $has_support = false !== strpos( (string) $page->post_content, '[tnt_application_support]' );
        $existing_package_id = sanitize_key( (string) get_post_meta( $owner_id, '_tnt_application_package_id', true ) );
        if ( 'draft' !== $page->post_status || ! $has_support || '' !== $existing_package_id ) {
            return new WP_Error( 'tnt_application_page_adoption_runtime_mismatch', __( 'The existing application Page Tool is configured for a different runtime.', 'toolntip-core' ) );
        }
    }
    update_post_meta( $owner_id, '_tnt_application_package_id', $application_id );
    $map = tnt_get_application_package_page_map();
    $map[ $application_id ] = $owner_id;
    update_option( 'tnt_application_package_pages', $map, false );
    return array( 'page_id' => $owner_id, 'created' => false, 'adopted' => true, 'status' => get_post_status( $owner_id ) );
}

function tnt_provision_application_package_page( $application_id ) {
    $application_id = sanitize_key( (string) $application_id );
    $app = tnt_get_installed_application( $application_id, true );
    if ( ! $app || empty( $app['versions'] ) ) {
        return new WP_Error( 'tnt_application_page_app_missing', __( 'Installed application could not be resolved for Page provisioning.', 'toolntip-core' ) );
    }
    $version = $app['active_version'] && isset( $app['versions'][ $app['active_version'] ] ) ? $app['active_version'] : $app['latest_version'];
    $record = isset( $app['versions'][ $version ] ) ? $app['versions'][ $version ] : null;
    if ( ! is_array( $record ) || empty( $record['manifest']['page'] ) ) {
        return new WP_Error( 'tnt_application_page_manifest_missing', __( 'Application Page metadata is unavailable.', 'toolntip-core' ) );
    }
    $title = sanitize_text_field( $record['manifest']['page']['title'] );
    $slug  = sanitize_title( $record['manifest']['page']['slug'] );

    $page_id = tnt_get_application_package_page_id( $application_id );
    if ( ! $page_id ) {
        $page_id = tnt_find_application_package_page_by_meta( $application_id );
    }
    if ( $page_id ) {
        $owner = tnt_application_page_slug_owner( $slug, $page_id );
        if ( $owner ) {
            return new WP_Error( 'tnt_application_page_slug_collision', __( 'The requested application Page slug is already used by another WordPress object. Resolve the slug collision before provisioning.', 'toolntip-core' ) );
        }
        $map = tnt_get_application_package_page_map();
        $map[ $application_id ] = $page_id;
        update_option( 'tnt_application_package_pages', $map, false );
        return array( 'page_id' => $page_id, 'created' => false, 'status' => get_post_status( $page_id ) );
    }

    if ( tnt_application_page_slug_owner( $slug ) ) {
        $adoption = tnt_try_adopt_existing_application_page( $application_id, $slug );
        if ( ! is_wp_error( $adoption ) ) {
            return $adoption;
        }
        return new WP_Error( 'tnt_application_page_slug_collision', __( 'The requested application Page slug is already used by another WordPress object and could not be safely adopted. Resolve the slug collision before provisioning.', 'toolntip-core' ) );
    }

    $page_id = wp_insert_post( array(
        'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title, 'post_name' => $slug,
        'post_content' => "[tnt_application_shell]\n\n[tnt_application_support]",
        'post_author' => get_current_user_id(),
    ), true );
    if ( is_wp_error( $page_id ) ) { return $page_id; }

    update_post_meta( $page_id, '_tnt_application_package_id', $application_id );
    $map = tnt_get_application_package_page_map();
    $map[ $application_id ] = absint( $page_id );
    update_option( 'tnt_application_package_pages', $map, false );
    return array( 'page_id' => absint( $page_id ), 'created' => true, 'status' => 'draft' );
}

function tnt_get_application_package_page_status( $application_id ) {
    $page_id = tnt_get_application_package_page_id( $application_id );
    if ( ! $page_id ) { $page_id = tnt_find_application_package_page_by_meta( $application_id ); }
    if ( $page_id ) {
        return array( 'state' => 'provisioned', 'page_id' => $page_id, 'post_status' => get_post_status( $page_id ), 'message' => '' );
    }
    $app = tnt_get_installed_application( $application_id );
    if ( $app ) {
        $version = $app['active_version'] && isset( $app['versions'][ $app['active_version'] ] ) ? $app['active_version'] : $app['latest_version'];
        $slug = $app['versions'][ $version ]['manifest']['page']['slug'];
        if ( tnt_application_page_slug_owner( $slug ) ) {
            return array( 'state' => 'collision', 'page_id' => 0, 'post_status' => '', 'message' => __( 'Slug collision — administrator resolution required.', 'toolntip-core' ) );
        }
    }
    return array( 'state' => 'missing', 'page_id' => 0, 'post_status' => '', 'message' => __( 'Not provisioned', 'toolntip-core' ) );
}

function tnt_reconcile_application_package_pages() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
    $apps = tnt_get_installed_application_packages( true );
    foreach ( array_keys( $apps ) as $application_id ) {
        tnt_provision_application_package_page( $application_id );
    }
}
add_action( 'admin_init', 'tnt_reconcile_application_package_pages', 30 );
