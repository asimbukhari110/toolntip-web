<?php
/**
 * ToolNTip Product Platform Foundation.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'TNT_PRODUCT_DB_VERSION' ) ) {
    define( 'TNT_PRODUCT_DB_VERSION', '1' );
}

if ( ! defined( 'TNT_PRODUCT_DB_VERSION_OPTION' ) ) {
    define( 'TNT_PRODUCT_DB_VERSION_OPTION', 'tnt_product_db_version' );
}

if ( ! defined( 'TNT_PRODUCT_DB_LOCK_OPTION' ) ) {
    define( 'TNT_PRODUCT_DB_LOCK_OPTION', 'tnt_product_db_migration_lock' );
}

if ( ! defined( 'TNT_PRODUCT_DB_ERROR_OPTION' ) ) {
    define( 'TNT_PRODUCT_DB_ERROR_OPTION', 'tnt_product_db_migration_error' );
}

if ( ! defined( 'TNT_PRODUCT_DB_LOCK_TTL' ) ) {
    define( 'TNT_PRODUCT_DB_LOCK_TTL', 300 );
}
