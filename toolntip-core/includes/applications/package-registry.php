<?php
/**
 * ToolNTip Installed Application Package Registry.
 *
 * Discovers only committed, valid package versions from managed storage.
 * It does not activate runtimes, enqueue assets, provision Pages or mutate Tools.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Validate one committed installed package version directory. */
function tnt_validate_installed_application_package_version( $path, $expected_id = '', $expected_version = '' ) {
    $path = untrailingslashit( wp_normalize_path( (string) $path ) );
    if ( '' === $path || ! is_dir( $path ) || is_link( $path ) ) {
        return new WP_Error( 'tnt_installed_app_invalid_directory', __( 'Installed application version directory is invalid.', 'toolntip-core' ) );
    }

    $manifest_path = $path . '/manifest.json';
    if ( ! is_file( $manifest_path ) || is_link( $manifest_path ) ) {
        return new WP_Error( 'tnt_installed_app_manifest_missing', __( 'Installed application manifest.json is missing.', 'toolntip-core' ) );
    }
    $json = @file_get_contents( $manifest_path );
    $manifest = tnt_validate_application_package_manifest_json( false === $json ? '' : $json );
    if ( is_wp_error( $manifest ) ) {
        return $manifest;
    }
    if ( '' !== $expected_id && $manifest['id'] !== $expected_id ) {
        return new WP_Error( 'tnt_installed_app_id_mismatch', __( 'Installed application ID does not match its managed directory.', 'toolntip-core' ) );
    }
    if ( '' !== $expected_version && $manifest['version'] !== $expected_version ) {
        return new WP_Error( 'tnt_installed_app_version_mismatch', __( 'Installed application version does not match its managed directory.', 'toolntip-core' ) );
    }

    $required = array_merge( array( $manifest['runtime']['entry'] ), $manifest['assets']['styles'], $manifest['assets']['scripts'] );
    foreach ( $required as $relative ) {
        if ( ! is_file( $path . '/' . $relative ) || is_link( $path . '/' . $relative ) ) {
            return new WP_Error( 'tnt_installed_app_declared_file_missing', sprintf( __( 'Installed application is missing a manifest-declared file: %s', 'toolntip-core' ), $relative ) );
        }
    }

    $files = 0;
    $bytes = 0;
    try {
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ) );
        foreach ( $iterator as $file ) {
            if ( $file->isLink() ) {
                return new WP_Error( 'tnt_installed_app_symlink', __( 'Installed application contains a symbolic link.', 'toolntip-core' ) );
            }
            if ( $file->isFile() ) {
                $relative = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $path ) ) ), '/' );
                $valid = tnt_validate_application_package_file_path( $relative );
                if ( is_wp_error( $valid ) ) {
                    return $valid;
                }
                $files++;
                $bytes += max( 0, (int) $file->getSize() );
                if ( $files > tnt_get_application_package_max_files() || $bytes > tnt_get_application_package_max_uncompressed_bytes() ) {
                    return new WP_Error( 'tnt_installed_app_limits', __( 'Installed application exceeds the governed package limits.', 'toolntip-core' ) );
                }
            }
        }
    } catch ( UnexpectedValueException $e ) {
        return new WP_Error( 'tnt_installed_app_unreadable', __( 'Installed application directory could not be read safely.', 'toolntip-core' ) );
    }

    return array(
        'id'       => $manifest['id'],
        'name'     => $manifest['name'],
        'version'  => $manifest['version'],
        'path'     => $path,
        'manifest' => $manifest,
        'files'    => $files,
        'bytes'    => $bytes,
    );
}

/** Discover committed application packages. Invalid or temporary entries fail closed. */
function tnt_get_installed_application_packages( $refresh = false ) {
    static $cache = null;
    if ( null !== $cache && ! $refresh ) {
        return $cache;
    }
    $cache = array();
    $storage = tnt_get_application_package_storage();
    if ( is_wp_error( $storage ) || ! is_dir( $storage['root'] ) ) {
        return $cache;
    }
    $app_dirs = @scandir( $storage['root'] );
    if ( false === $app_dirs ) {
        return $cache;
    }
    foreach ( $app_dirs as $app_id ) {
        if ( '.' === $app_id || '..' === $app_id || '.staging' === $app_id || ! preg_match( '/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/', $app_id ) ) {
            continue;
        }
        $app_path = $storage['root'] . '/' . $app_id;
        if ( ! is_dir( $app_path ) || is_link( $app_path ) ) {
            continue;
        }
        $version_dirs = @scandir( $app_path );
        if ( false === $version_dirs ) {
            continue;
        }
        $versions = array();
        foreach ( $version_dirs as $version ) {
            if ( '.' === $version || '..' === $version ) {
                continue;
            }
            $record = tnt_validate_installed_application_package_version( $app_path . '/' . $version, $app_id, $version );
            if ( ! is_wp_error( $record ) ) {
                $versions[ $record['version'] ] = $record;
            }
        }
        if ( empty( $versions ) ) {
            continue;
        }
        uksort( $versions, 'version_compare' );
        $latest = end( $versions );
        $active_versions = get_option( 'tnt_application_package_active_versions', array() );
        $active_version  = is_array( $active_versions ) && isset( $active_versions[ $app_id ] ) && isset( $versions[ $active_versions[ $app_id ] ] )
            ? (string) $active_versions[ $app_id ] : null;
        $cache[ $app_id ] = array(
            'id'             => $app_id,
            'name'           => $latest['name'],
            'versions'       => $versions,
            'latest_version' => $latest['version'],
            'active_version' => $active_version,
            'active'         => null !== $active_version,
        );
    }
    ksort( $cache, SORT_NATURAL | SORT_FLAG_CASE );
    return $cache;
}

/** Return one installed application, or null when no valid committed version exists. */
function tnt_get_installed_application( $application_id, $refresh = false ) {
    $application_id = sanitize_key( (string) $application_id );
    $apps = tnt_get_installed_application_packages( $refresh );
    return isset( $apps[ $application_id ] ) ? $apps[ $application_id ] : null;
}

/** Return one installed application version, or null. */
function tnt_get_installed_application_version( $application_id, $version, $refresh = false ) {
    $app = tnt_get_installed_application( $application_id, $refresh );
    $version = (string) $version;
    return $app && isset( $app['versions'][ $version ] ) ? $app['versions'][ $version ] : null;
}

/** Read-only housekeeping status for the package admin checkpoint. */
function tnt_get_application_package_housekeeping_status() {
    $status = array( 'staging_entries' => 0, 'unexpected_root_entries' => 0 );
    $storage = tnt_get_application_package_storage();
    if ( is_wp_error( $storage ) ) {
        return $status;
    }
    if ( is_dir( $storage['staging'] ) ) {
        $items = @scandir( $storage['staging'] );
        if ( is_array( $items ) ) {
            $status['staging_entries'] = count( array_diff( $items, array( '.', '..' ) ) );
        }
    }
    if ( is_dir( $storage['root'] ) ) {
        $items = @scandir( $storage['root'] );
        if ( is_array( $items ) ) {
            foreach ( array_diff( $items, array( '.', '..', '.staging' ) ) as $item ) {
                if ( ! preg_match( '/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/', $item ) || ! is_dir( $storage['root'] . '/' . $item ) || is_link( $storage['root'] . '/' . $item ) ) {
                    $status['unexpected_root_entries']++;
                }
            }
        }
    }
    return $status;
}
