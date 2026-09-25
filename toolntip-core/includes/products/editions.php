<?php
/**
 * ToolNTip Product Edition repository.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_product_edition_statuses() {
    return array( 'planned', 'available', 'discontinued' );
}

function tnt_sanitize_product_edition_key( $key ) {
    return sanitize_title( (string) $key );
}

function tnt_get_product_edition( $edition_id ) {
    global $wpdb;
    $tables = tnt_get_product_table_names();
    $id = absint( $edition_id );
    if ( ! $id || ! tnt_product_platform_ready() ) {
        return null;
    }
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['editions']} WHERE id = %d", $id ) );
}

function tnt_get_product_edition_by_key( $product_id, $edition_key ) {
    global $wpdb;
    $tables = tnt_get_product_table_names();
    $product_id = absint( $product_id );
    $edition_key = tnt_sanitize_product_edition_key( $edition_key );
    if ( ! $product_id || '' === $edition_key || ! tnt_product_platform_ready() ) {
        return null;
    }
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['editions']} WHERE product_id = %d AND edition_key = %s", $product_id, $edition_key ) );
}

function tnt_get_product_editions( $product_id, $include_discontinued = true ) {
    global $wpdb;
    $tables = tnt_get_product_table_names();
    $product_id = absint( $product_id );
    if ( ! $product_id || ! tnt_product_platform_ready() ) {
        return array();
    }
    if ( $include_discontinued ) {
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tables['editions']} WHERE product_id = %d ORDER BY display_order ASC, id ASC", $product_id ) );
    }
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tables['editions']} WHERE product_id = %d AND status <> 'discontinued' ORDER BY display_order ASC, id ASC", $product_id ) );
}

function tnt_validate_product_edition_data( $data, $existing = null ) {
    $product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : ( $existing ? absint( $existing->product_id ) : 0 );
    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return new WP_Error( 'tnt_invalid_product', __( 'A valid Product is required.', 'toolntip-core' ) );
    }
    $key = isset( $data['edition_key'] ) ? tnt_sanitize_product_edition_key( $data['edition_key'] ) : ( $existing ? $existing->edition_key : '' );
    $name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : ( $existing ? $existing->name : '' );
    $status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : ( $existing ? $existing->status : 'planned' );
    if ( '' === $key || '' === $name ) {
        return new WP_Error( 'tnt_invalid_edition', __( 'Edition key and name are required.', 'toolntip-core' ) );
    }
    if ( ! in_array( $status, tnt_get_product_edition_statuses(), true ) ) {
        return new WP_Error( 'tnt_invalid_edition_status', __( 'Invalid Edition status.', 'toolntip-core' ) );
    }
    return array(
        'product_id'        => $product_id,
        'edition_key'       => $key,
        'name'              => $name,
        'status'            => $status,
        'short_description' => isset( $data['short_description'] ) ? sanitize_textarea_field( $data['short_description'] ) : ( $existing ? $existing->short_description : '' ),
        'pricing_label'     => isset( $data['pricing_label'] ) ? sanitize_text_field( $data['pricing_label'] ) : ( $existing ? $existing->pricing_label : '' ),
        'display_order'     => isset( $data['display_order'] ) ? absint( $data['display_order'] ) : ( $existing ? absint( $existing->display_order ) : 10 ),
    );
}

function tnt_save_product_edition( $data, $edition_id = 0 ) {
    global $wpdb;
    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }
    $tables = tnt_get_product_table_names();
    $existing = $edition_id ? tnt_get_product_edition( $edition_id ) : null;
    if ( $edition_id && ! $existing ) {
        return new WP_Error( 'tnt_edition_not_found', __( 'Edition not found.', 'toolntip-core' ) );
    }
    $clean = tnt_validate_product_edition_data( $data, $existing );
    if ( is_wp_error( $clean ) ) {
        return $clean;
    }
    $duplicate = tnt_get_product_edition_by_key( $clean['product_id'], $clean['edition_key'] );
    if ( $duplicate && ( ! $existing || absint( $duplicate->id ) !== absint( $existing->id ) ) ) {
        return new WP_Error( 'tnt_duplicate_edition_key', __( 'That Edition key is already used by this Product.', 'toolntip-core' ) );
    }
    $now = current_time( 'mysql', true );
    if ( $existing ) {
        $clean['updated_at'] = $now;
        $ok = $wpdb->update( $tables['editions'], $clean, array( 'id' => absint( $existing->id ) ) );
        return false === $ok ? new WP_Error( 'tnt_edition_save_failed', __( 'Edition could not be saved.', 'toolntip-core' ) ) : absint( $existing->id );
    }
    $clean['created_at'] = $now;
    $clean['updated_at'] = $now;
    $ok = $wpdb->insert( $tables['editions'], $clean );
    return false === $ok ? new WP_Error( 'tnt_edition_save_failed', __( 'Edition could not be created.', 'toolntip-core' ) ) : absint( $wpdb->insert_id );
}

function tnt_delete_product_edition( $edition_id ) {
    global $wpdb;
    $edition = tnt_get_product_edition( $edition_id );
    if ( ! $edition ) {
        return new WP_Error( 'tnt_edition_not_found', __( 'Edition not found.', 'toolntip-core' ) );
    }
    if ( tnt_get_edition_releases( $edition->id ) ) {
        return new WP_Error( 'tnt_edition_has_releases', __( 'An Edition with Releases cannot be deleted.', 'toolntip-core' ) );
    }
    $tables = tnt_get_product_table_names();
    return false !== $wpdb->delete( $tables['editions'], array( 'id' => absint( $edition->id ) ) );
}
