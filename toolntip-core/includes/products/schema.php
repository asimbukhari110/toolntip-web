<?php
/**
 * ToolNTip Product Platform database schema and migrations.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return Product-platform custom table names.
 *
 * @return array
 */
function tnt_get_product_table_names() {
    global $wpdb;

    return array(
        'editions'          => $wpdb->prefix . 'tnt_product_editions',
        'releases'          => $wpdb->prefix . 'tnt_product_releases',
        'release_audit'     => $wpdb->prefix . 'tnt_product_release_audit',
        'resources'         => $wpdb->prefix . 'tnt_product_resources',
        'release_locations' => $wpdb->prefix . 'tnt_product_release_locations',
        'downloads'         => $wpdb->prefix . 'tnt_product_downloads',
    );
}

/**
 * Test whether a custom table exists.
 *
 * @param string $table_name Full table name.
 * @return bool
 */
function tnt_product_table_exists( $table_name ) {
    global $wpdb;

    if ( ! is_string( $table_name ) || '' === $table_name ) {
        return false;
    }

    $found = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like( $table_name )
        )
    );

    return $table_name === $found;
}

/**
 * Verify that every table required by schema v1 exists.
 *
 * @return bool
 */
function tnt_verify_product_schema_v1() {
    foreach ( tnt_get_product_table_names() as $table_name ) {
        if ( ! tnt_product_table_exists( $table_name ) ) {
            return false;
        }
    }

    return true;
}

/**
 * Store a migration error outside Product release audit data.
 *
 * @param string $message Error message.
 * @return void
 */
function tnt_set_product_db_migration_error( $message ) {
    update_option(
        TNT_PRODUCT_DB_ERROR_OPTION,
        array(
            'message'    => sanitize_text_field( (string) $message ),
            'created_at' => time(),
        ),
        false
    );
}

/**
 * Acquire the short-lived Product migration lock.
 *
 * add_option() provides the atomic first-writer operation. A stale lock may be
 * reclaimed after the configured TTL.
 *
 * @return string|false Lock token or false when another request owns the lock.
 */
function tnt_acquire_product_db_migration_lock() {
    $token = wp_generate_uuid4();
    $now   = time();
    $value = array(
        'token'      => $token,
        'created_at' => $now,
    );

    if ( add_option( TNT_PRODUCT_DB_LOCK_OPTION, $value, '', false ) ) {
        return $token;
    }

    $current = get_option( TNT_PRODUCT_DB_LOCK_OPTION, array() );

    if ( ! is_array( $current ) || empty( $current['created_at'] ) ) {
        return false;
    }

    $created_at = absint( $current['created_at'] );

    if ( $created_at && ( $now - $created_at ) < TNT_PRODUCT_DB_LOCK_TTL ) {
        return false;
    }

    delete_option( TNT_PRODUCT_DB_LOCK_OPTION );

    if ( add_option( TNT_PRODUCT_DB_LOCK_OPTION, $value, '', false ) ) {
        return $token;
    }

    return false;
}

/**
 * Release a migration lock owned by this request.
 *
 * @param string $token Lock token.
 * @return void
 */
function tnt_release_product_db_migration_lock( $token ) {
    $current = get_option( TNT_PRODUCT_DB_LOCK_OPTION, array() );

    if (
        is_array( $current ) &&
        ! empty( $current['token'] ) &&
        is_string( $token ) &&
        hash_equals( (string) $current['token'], $token )
    ) {
        delete_option( TNT_PRODUCT_DB_LOCK_OPTION );
    }
}

/**
 * Install Product database schema version 1.
 *
 * @return bool
 */
function tnt_install_product_db_v1() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tables          = tnt_get_product_table_names();
    $charset_collate = $wpdb->get_charset_collate();

    $statements = array(
        "CREATE TABLE {$tables['editions']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            edition_key VARCHAR(100) NOT NULL,
            name VARCHAR(191) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'planned',
            short_description TEXT NULL,
            pricing_label VARCHAR(100) NULL,
            display_order INT UNSIGNED NOT NULL DEFAULT 10,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY product_edition_key (product_id, edition_key),
            KEY product_status_order (product_id, status, display_order),
            KEY product_order (product_id, display_order)
        ) {$charset_collate};",
        "CREATE TABLE {$tables['releases']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            edition_id BIGINT UNSIGNED NOT NULL,
            version VARCHAR(100) NOT NULL,
            release_date DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            platform VARCHAR(191) NOT NULL,
            architecture VARCHAR(100) NULL,
            artifact_filename VARCHAR(255) NULL,
            artifact_size BIGINT UNSIGNED NULL,
            artifact_sha256 CHAR(64) NULL,
            release_notes LONGTEXT NULL,
            system_requirements LONGTEXT NULL,
            license_reference TEXT NULL,
            documentation_reference TEXT NULL,
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY edition_version (edition_id, version),
            KEY edition_status (edition_id, status),
            KEY edition_status_published (edition_id, status, published_at)
        ) {$charset_collate};",
        "CREATE TABLE {$tables['release_audit']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            edition_id BIGINT UNSIGNED NULL,
            release_id BIGINT UNSIGNED NULL,
            action VARCHAR(50) NOT NULL,
            actor_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            version_snapshot VARCHAR(100) NULL,
            artifact_filename_snapshot VARCHAR(255) NULL,
            context LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY product_created (product_id, created_at),
            KEY release_created (release_id, created_at),
            KEY action_created (action, created_at),
            KEY actor_created (actor_user_id, created_at)
        ) {$charset_collate};",
        "CREATE TABLE {$tables['resources']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            resource_id BIGINT UNSIGNED NOT NULL,
            display_order INT UNSIGNED NOT NULL DEFAULT 10,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY product_resource (product_id, resource_id),
            KEY product_order (product_id, display_order),
            KEY resource_product (resource_id, product_id)
        ) {$charset_collate};",
        "CREATE TABLE {$tables['release_locations']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            release_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(50) NOT NULL,
            provider_file_id VARCHAR(512) NOT NULL,
            enabled TINYINT UNSIGNED NOT NULL DEFAULT 1,
            priority INT UNSIGNED NOT NULL DEFAULT 100,
            weight INT UNSIGNED NOT NULL DEFAULT 100,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY release_provider_file (release_id, provider, provider_file_id),
            KEY release_enabled_priority (release_id, enabled, priority)
        ) {$charset_collate};",
        "CREATE TABLE {$tables['downloads']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            edition_id BIGINT UNSIGNED NOT NULL,
            release_id BIGINT UNSIGNED NOT NULL,
            location_id BIGINT UNSIGNED NULL,
            provider VARCHAR(50) NULL,
            version_snapshot VARCHAR(100) NOT NULL,
            artifact_filename_snapshot VARCHAR(255) NULL,
            event_status VARCHAR(30) NOT NULL,
            country_code CHAR(2) NULL,
            country_name VARCHAR(100) NULL,
            region_name VARCHAR(191) NULL,
            city_name VARCHAR(191) NULL,
            timezone_name VARCHAR(100) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_created (user_id, created_at),
            KEY release_created (release_id, created_at),
            KEY product_created (product_id, created_at),
            KEY provider_created (provider, created_at),
            KEY event_status_created (event_status, created_at),
            KEY country_created (country_code, created_at)
        ) {$charset_collate};",
    );

    foreach ( $statements as $statement ) {
        dbDelta( $statement );
    }

    return tnt_verify_product_schema_v1();
}

/**
 * Return whether the Product platform database is ready for use.
 *
 * @return bool
 */
function tnt_product_platform_ready() {
    $installed = (string) get_option( TNT_PRODUCT_DB_VERSION_OPTION, '' );

    if ( TNT_PRODUCT_DB_VERSION !== $installed ) {
        return false;
    }

    return tnt_verify_product_schema_v1();
}

/**
 * Install or upgrade the Product database schema.
 *
 * The version option advances only after the corresponding migration succeeds
 * and its required tables are verified.
 *
 * @return void
 */
function tnt_maybe_install_product_db() {
    $installed = (string) get_option( TNT_PRODUCT_DB_VERSION_OPTION, '' );

    if ( TNT_PRODUCT_DB_VERSION === $installed ) {
        return;
    }

    if ( '' !== $installed && version_compare( $installed, TNT_PRODUCT_DB_VERSION, '>' ) ) {
        tnt_set_product_db_migration_error(
            __( 'The installed Product database schema is newer than this ToolNTip Core build.', 'toolntip-core' )
        );
        return;
    }

    $token = tnt_acquire_product_db_migration_lock();

    if ( false === $token ) {
        return;
    }

    try {
        $success = true;

        if ( version_compare( $installed ?: '0', '1', '<' ) ) {
            $success = tnt_install_product_db_v1();
        }

        if ( $success && tnt_verify_product_schema_v1() ) {
            update_option( TNT_PRODUCT_DB_VERSION_OPTION, TNT_PRODUCT_DB_VERSION, false );
            delete_option( TNT_PRODUCT_DB_ERROR_OPTION );
        } else {
            tnt_set_product_db_migration_error(
                __( 'ToolNTip Product database migration did not complete successfully.', 'toolntip-core' )
            );
        }
    } catch ( Throwable $error ) {
        tnt_set_product_db_migration_error(
            __( 'ToolNTip Product database migration failed. Review the server error log and retry.', 'toolntip-core' )
        );
        error_log( 'ToolNTip Product DB migration: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    } finally {
        tnt_release_product_db_migration_lock( $token );
    }
}

/**
 * Show an administrator-only migration warning without exposing internals.
 *
 * @return void
 */
function tnt_product_db_migration_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $error = get_option( TNT_PRODUCT_DB_ERROR_OPTION, array() );

    if ( empty( $error['message'] ) ) {
        return;
    }

    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html( (string) $error['message'] )
    );
}

add_action( 'init', 'tnt_maybe_install_product_db', 101 );
add_action( 'admin_notices', 'tnt_product_db_migration_admin_notice' );
