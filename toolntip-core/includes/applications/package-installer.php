<?php
/**
 * ToolNTip Application Package Transactional Installer.
 *
 * Stages, validates and installs declarative client-side application ZIPs.
 * This checkpoint does not activate runtimes, provision Pages or mutate Tool
 * records. Those responsibilities remain in later WEB-007.8 checkpoints.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Maximum extracted application payload size (50 MiB). */
function tnt_get_application_package_max_uncompressed_bytes() {
    return 50 * 1024 * 1024;
}

/** Maximum number of files in one application package. */
function tnt_get_application_package_max_files() {
    return 250;
}

/** Return managed application storage paths. */
function tnt_get_application_package_storage() {
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) ) {
        return new WP_Error( 'tnt_app_storage_unavailable', sanitize_text_field( $uploads['error'] ) );
    }

    $root = trailingslashit( wp_normalize_path( $uploads['basedir'] ) ) . 'toolntip-applications';
    return array(
        'root'    => $root,
        'staging' => $root . '/.staging',
    );
}

/** Recursively remove a ToolNTip-owned temporary directory without following links. */
function tnt_application_package_remove_tree( $path ) {
    $path = wp_normalize_path( (string) $path );
    if ( '' === $path || ! file_exists( $path ) ) {
        return true;
    }
    if ( is_link( $path ) || is_file( $path ) ) {
        return @unlink( $path );
    }
    $items = scandir( $path );
    if ( false === $items ) {
        return false;
    }
    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }
        if ( ! tnt_application_package_remove_tree( $path . '/' . $item ) ) {
            return false;
        }
    }
    return @rmdir( $path );
}

/** Clean stale staging transactions older than the supplied age. */
function tnt_cleanup_stale_application_package_transactions( $max_age = DAY_IN_SECONDS ) {
    $storage = tnt_get_application_package_storage();
    if ( is_wp_error( $storage ) || ! is_dir( $storage['staging'] ) ) {
        return;
    }
    $cutoff = time() - max( HOUR_IN_SECONDS, absint( $max_age ) );
    $items  = scandir( $storage['staging'] );
    if ( false === $items ) {
        return;
    }
    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }
        $path = $storage['staging'] . '/' . $item;
        $mtime = @filemtime( $path );
        if ( false !== $mtime && $mtime < $cutoff ) {
            tnt_application_package_remove_tree( $path );
        }
    }
}
add_action( 'admin_init', 'tnt_cleanup_stale_application_package_transactions', 5 );

/** Determine archive package root: archive root or one wrapper directory. */
function tnt_application_package_archive_root( $entries ) {
    if ( in_array( 'manifest.json', $entries, true ) ) {
        return '';
    }
    $roots = array();
    foreach ( $entries as $entry ) {
        $entry = trim( $entry, '/' );
        if ( '' === $entry ) {
            continue;
        }
        $parts = explode( '/', $entry );
        $roots[ $parts[0] ] = true;
    }
    if ( 1 !== count( $roots ) ) {
        return new WP_Error( 'tnt_app_package_manifest_location', __( 'Package must contain manifest.json at archive root or inside one top-level application folder.', 'toolntip-core' ) );
    }
    $root = key( $roots );
    return in_array( $root . '/manifest.json', $entries, true ) ? $root . '/' : new WP_Error( 'tnt_app_package_manifest_missing', __( 'Application package does not contain manifest.json.', 'toolntip-core' ) );
}

/** Inspect a ZIP without extracting it and return validated archive metadata. */
function tnt_inspect_application_package_zip( $zip_path ) {
    if ( ! class_exists( 'ZipArchive' ) ) {
        return new WP_Error( 'tnt_app_package_zip_unavailable', __( 'The server ZIP extension is required to install application packages.', 'toolntip-core' ) );
    }
    $zip = new ZipArchive();
    if ( true !== $zip->open( $zip_path ) ) {
        return new WP_Error( 'tnt_app_package_invalid_zip', __( 'The uploaded application package is not a readable ZIP archive.', 'toolntip-core' ) );
    }

    $entries = array();
    $files = 0;
    $bytes = 0;
    for ( $i = 0; $i < $zip->numFiles; $i++ ) {
        $stat = $zip->statIndex( $i );
        if ( ! is_array( $stat ) || empty( $stat['name'] ) ) {
            $zip->close();
            return new WP_Error( 'tnt_app_package_invalid_entry', __( 'Application package contains an unreadable archive entry.', 'toolntip-core' ) );
        }
        $raw = str_replace( '\\', '/', (string) $stat['name'] );
        $is_dir = '/' === substr( $raw, -1 );
        $check = trim( $raw, '/' );
        if ( '' === $check ) {
            continue;
        }
        $segments = explode( '/', $check );
        foreach ( $segments as $segment ) {
            if ( '' === $segment || '.' === $segment || '..' === $segment || false !== strpos( $segment, "\0" ) ) {
                $zip->close();
                return new WP_Error( 'tnt_app_package_unsafe_archive_path', __( 'Application package contains an unsafe archive path.', 'toolntip-core' ) );
            }
        }
        if ( preg_match( '#^/|^[A-Za-z]:/#', $raw ) ) {
            $zip->close();
            return new WP_Error( 'tnt_app_package_absolute_archive_path', __( 'Application package contains an absolute archive path.', 'toolntip-core' ) );
        }

        // Reject Unix symlinks using external file attributes when available.
        $opsys = 0;
        $attr  = 0;
        if ( method_exists( $zip, 'getExternalAttributesIndex' ) && $zip->getExternalAttributesIndex( $i, $opsys, $attr ) ) {
            $mode = ( $attr >> 16 ) & 0xF000;
            if ( 0xA000 === $mode ) {
                $zip->close();
                return new WP_Error( 'tnt_app_package_symlink', __( 'Symbolic links are not allowed in application packages.', 'toolntip-core' ) );
            }
        }

        if ( ! $is_dir ) {
            $files++;
            $bytes += isset( $stat['size'] ) ? absint( $stat['size'] ) : 0;
            if ( $files > tnt_get_application_package_max_files() || $bytes > tnt_get_application_package_max_uncompressed_bytes() ) {
                $zip->close();
                return new WP_Error( 'tnt_app_package_limits', __( 'Application package exceeds the allowed file-count or extracted-size limit.', 'toolntip-core' ) );
            }
        }
        $entries[] = $raw;
    }

    $root = tnt_application_package_archive_root( $entries );
    if ( is_wp_error( $root ) ) {
        $zip->close();
        return $root;
    }

    foreach ( $entries as $entry ) {
        if ( '/' === substr( $entry, -1 ) ) {
            continue;
        }
        $relative = '' === $root ? $entry : substr( $entry, strlen( $root ) );
        if ( '' === $relative ) {
            continue;
        }
        $valid = tnt_validate_application_package_file_path( $relative );
        if ( is_wp_error( $valid ) ) {
            $zip->close();
            return $valid;
        }
    }

    $manifest_json = $zip->getFromName( $root . 'manifest.json' );
    $zip->close();
    if ( false === $manifest_json ) {
        return new WP_Error( 'tnt_app_package_manifest_unreadable', __( 'Application package manifest.json could not be read.', 'toolntip-core' ) );
    }
    $manifest = tnt_validate_application_package_manifest_json( $manifest_json );
    if ( is_wp_error( $manifest ) ) {
        return $manifest;
    }

    $required = array_merge(
        array( $manifest['runtime']['entry'] ),
        $manifest['assets']['styles'],
        $manifest['assets']['scripts']
    );
    $relative_entries = array();
    foreach ( $entries as $entry ) {
        if ( '/' === substr( $entry, -1 ) ) {
            continue;
        }
        $relative_entries[] = '' === $root ? $entry : substr( $entry, strlen( $root ) );
    }
    foreach ( $required as $required_file ) {
        if ( ! in_array( $required_file, $relative_entries, true ) ) {
            return new WP_Error( 'tnt_app_package_declared_file_missing', sprintf( __( 'Manifest-declared file is missing from package: %s', 'toolntip-core' ), esc_html( $required_file ) ) );
        }
    }

    return array( 'manifest' => $manifest, 'root' => $root, 'files' => $files, 'bytes' => $bytes );
}

/** Install a validated package into managed versioned storage. */
function tnt_install_application_package_zip( $zip_path ) {
    $inspection = tnt_inspect_application_package_zip( $zip_path );
    if ( is_wp_error( $inspection ) ) {
        return $inspection;
    }
    $storage = tnt_get_application_package_storage();
    if ( is_wp_error( $storage ) ) {
        return $storage;
    }
    if ( ! wp_mkdir_p( $storage['staging'] ) ) {
        return new WP_Error( 'tnt_app_package_staging_create_failed', __( 'ToolNTip could not create the application staging directory.', 'toolntip-core' ) );
    }

    $manifest = $inspection['manifest'];
    $transaction = 'txn-' . wp_generate_uuid4();
    $stage = $storage['staging'] . '/' . $transaction;
    $extract = $stage . '/extract';
    $final = $storage['root'] . '/' . $manifest['id'] . '/' . $manifest['version'];

    if ( file_exists( $final ) ) {
        return new WP_Error( 'tnt_app_package_version_exists', __( 'This application version is already installed. Existing installed files were not changed.', 'toolntip-core' ) );
    }
    if ( ! wp_mkdir_p( $extract ) ) {
        return new WP_Error( 'tnt_app_package_stage_failed', __( 'ToolNTip could not create the application transaction workspace.', 'toolntip-core' ) );
    }

    $result = null;
    try {
        $zip = new ZipArchive();
        if ( true !== $zip->open( $zip_path ) || ! $zip->extractTo( $extract ) ) {
            if ( $zip instanceof ZipArchive ) {
                $zip->close();
            }
            throw new RuntimeException( 'extract' );
        }
        $zip->close();
        $source = trailingslashit( $extract ) . $inspection['root'];

        // Revalidate extracted payload and reject links or unexpected files.
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ) );
        foreach ( $iterator as $file ) {
            if ( $file->isLink() ) {
                throw new RuntimeException( 'link' );
            }
            if ( $file->isFile() ) {
                $relative = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $source ) ) ), '/' );
                if ( is_wp_error( tnt_validate_application_package_file_path( $relative ) ) ) {
                    throw new RuntimeException( 'payload' );
                }
            }
        }

        $post_manifest = @file_get_contents( $source . 'manifest.json' );
        $post_manifest = tnt_validate_application_package_manifest_json( false === $post_manifest ? '' : $post_manifest );
        if ( is_wp_error( $post_manifest ) || $post_manifest['id'] !== $manifest['id'] || $post_manifest['version'] !== $manifest['version'] ) {
            throw new RuntimeException( 'manifest' );
        }

        if ( ! wp_mkdir_p( dirname( $final ) ) ) {
            throw new RuntimeException( 'destination' );
        }
        if ( ! @rename( untrailingslashit( $source ), $final ) ) {
            throw new RuntimeException( 'commit' );
        }
        $result = array(
            'id'       => $manifest['id'],
            'name'     => $manifest['name'],
            'version'  => $manifest['version'],
            'path'     => $final,
            'manifest' => $manifest,
        );
    } catch ( Exception $e ) {
        if ( file_exists( $final ) ) {
            tnt_application_package_remove_tree( $final );
        }
        $result = new WP_Error( 'tnt_app_package_install_failed', __( 'Application package installation failed safely. No existing installed application was changed.', 'toolntip-core' ) );
    } finally {
        tnt_application_package_remove_tree( $stage );
    }

    return $result;
}

/** Temporary 8.2-C admin upload endpoint. Replaced by Applications Admin in 8.2-F. */
function tnt_application_package_installer_admin_menu() {
    add_submenu_page( 'edit.php?post_type=tool', __( 'ToolNTip Application Packages', 'toolntip-core' ), __( 'Application Packages', 'toolntip-core' ), 'manage_options', 'tnt-package-installer', 'tnt_render_application_package_installer_page' );
}
add_action( 'admin_menu', 'tnt_application_package_installer_admin_menu' );

function tnt_render_application_package_installer_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to install ToolNTip application packages.', 'toolntip-core' ) );
    }
    $notice = get_transient( 'tnt_app_package_notice_' . get_current_user_id() );
    if ( $notice ) {
        delete_transient( 'tnt_app_package_notice_' . get_current_user_id() );
    }
    ?>
    <div class="wrap"><h1><?php esc_html_e( 'ToolNTip Application Packages', 'toolntip-core' ); ?></h1>
    <p><?php esc_html_e( 'WEB-007.8 application package installer and installed-package registry. Valid client-side packages are installed into managed storage, registered through the ToolNTip runtime framework, and receive one governed Draft runtime Page.', 'toolntip-core' ); ?></p>
    <?php if ( is_array( $notice ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="tnt_install_application_package">
        <?php wp_nonce_field( 'tnt_install_application_package', 'tnt_app_package_nonce' ); ?>
        <input type="file" name="application_package" accept=".zip,application/zip" required>
        <?php submit_button( __( 'Validate & Install Package', 'toolntip-core' ) ); ?>
    </form>
    <?php
    $installed = function_exists( 'tnt_get_installed_application_packages' ) ? tnt_get_installed_application_packages( true ) : array();
    $housekeeping = function_exists( 'tnt_get_application_package_housekeeping_status' ) ? tnt_get_application_package_housekeeping_status() : array( 'staging_entries' => 0, 'unexpected_root_entries' => 0 );
    ?>
    <hr>
    <h2><?php esc_html_e( 'Installed Packages', 'toolntip-core' ); ?></h2>
    <?php if ( empty( $installed ) ) : ?>
        <p><?php esc_html_e( 'No valid committed application packages are installed.', 'toolntip-core' ); ?></p>
    <?php else : ?>
        <table class="widefat striped" style="max-width:1000px">
            <thead><tr><th><?php esc_html_e( 'Application', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'ID', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Installed Versions', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Active Version', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Runtime', 'toolntip-core' ); ?></th></tr></thead>
            <tbody>
            <?php foreach ( $installed as $app ) : ?>
                <?php $runtime_statuses = function_exists( 'tnt_get_application_package_runtime_statuses' ) ? tnt_get_application_package_runtime_statuses() : array(); $runtime_status = isset( $runtime_statuses[ $app['id'] ] ) ? $runtime_statuses[ $app['id'] ] : array(); ?>
                <tr><td><?php echo esc_html( $app['name'] ); ?></td><td><code><?php echo esc_html( $app['id'] ); ?></code></td><td><?php echo esc_html( implode( ', ', array_keys( $app['versions'] ) ) ); ?></td><td><?php echo $app['active_version'] ? esc_html( $app['active_version'] ) : esc_html__( 'Not active', 'toolntip-core' ); ?></td><td><?php echo ! empty( $runtime_status['registered'] ) ? esc_html__( 'Registered', 'toolntip-core' ) : esc_html__( 'Not registered', 'toolntip-core' ); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <h2><?php esc_html_e( 'Housekeeping Status', 'toolntip-core' ); ?></h2>
    <p><strong><?php esc_html_e( 'Staging:', 'toolntip-core' ); ?></strong> <?php echo 0 === (int) $housekeeping['staging_entries'] ? esc_html__( 'Clean', 'toolntip-core' ) : esc_html( sprintf( __( '%d transaction artifact(s) present', 'toolntip-core' ), (int) $housekeeping['staging_entries'] ) ); ?> &nbsp; <strong><?php esc_html_e( 'Unexpected root artifacts:', 'toolntip-core' ); ?></strong> <?php echo esc_html( (string) (int) $housekeeping['unexpected_root_entries'] ); ?></p>
    <p class="description"><?php esc_html_e( 'Runtime registration, Draft Page provisioning, Tool association and installed-version lifecycle state are observable in ToolNTip Applications.', 'toolntip-core' ); ?></p>
    </div>
    <?php
}

function tnt_handle_application_package_upload() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to install ToolNTip application packages.', 'toolntip-core' ) );
    }
    check_admin_referer( 'tnt_install_application_package', 'tnt_app_package_nonce' );
    $notice_key = 'tnt_app_package_notice_' . get_current_user_id();
    $tmp = '';

    if ( empty( $_FILES['application_package'] ) || ! is_array( $_FILES['application_package'] ) ) {
        set_transient( $notice_key, array( 'type' => 'error', 'message' => __( 'Choose an application ZIP package.', 'toolntip-core' ) ), MINUTE_IN_SECONDS );
        wp_safe_redirect( admin_url( 'edit.php?post_type=tool&page=tnt-package-installer' ) ); exit;
    }
    $upload = $_FILES['application_package'];
    $tmp = isset( $upload['tmp_name'] ) ? (string) $upload['tmp_name'] : '';
    $name = isset( $upload['name'] ) ? sanitize_file_name( wp_unslash( $upload['name'] ) ) : '';
    $error = isset( $upload['error'] ) ? absint( $upload['error'] ) : UPLOAD_ERR_NO_FILE;

    if ( UPLOAD_ERR_OK !== $error || ! is_uploaded_file( $tmp ) || 'zip' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
        set_transient( $notice_key, array( 'type' => 'error', 'message' => __( 'Upload failed or the selected file is not a ZIP package.', 'toolntip-core' ) ), MINUTE_IN_SECONDS );
        wp_safe_redirect( admin_url( 'edit.php?post_type=tool&page=tnt-package-installer' ) ); exit;
    }

    $result = tnt_install_application_package_zip( $tmp );
    // PHP normally removes upload temp files; explicitly consume it now as part of package housekeeping.
    if ( file_exists( $tmp ) ) {
        @unlink( $tmp );
    }
    if ( is_wp_error( $result ) ) {
        set_transient( $notice_key, array( 'type' => 'error', 'message' => $result->get_error_message() ), MINUTE_IN_SECONDS );
    } else {
        $page_result = function_exists( 'tnt_provision_application_package_page' ) ? tnt_provision_application_package_page( $result['id'] ) : null;
        if ( is_wp_error( $page_result ) ) {
            set_transient( $notice_key, array( 'type' => 'warning', 'message' => sprintf( __( '%1$s %2$s installed successfully, but its Draft runtime Page was not provisioned: %3$s', 'toolntip-core' ), $result['name'], $result['version'], $page_result->get_error_message() ) ), MINUTE_IN_SECONDS );
        } else {
            set_transient( $notice_key, array( 'type' => 'success', 'message' => sprintf( __( '%1$s %2$s installed successfully and its governed Draft runtime Page is provisioned.', 'toolntip-core' ), $result['name'], $result['version'] ) ), MINUTE_IN_SECONDS );
        }
    }
    wp_safe_redirect( admin_url( 'edit.php?post_type=tool&page=tnt-package-installer' ) ); exit;
}
add_action( 'admin_post_tnt_install_application_package', 'tnt_handle_application_package_upload' );
