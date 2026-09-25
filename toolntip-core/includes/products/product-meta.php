<?php
/**
 * Product controlled metadata contract.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return allowed Product lifecycle statuses.
 *
 * @return array
 */
function tnt_get_product_statuses() {
    return array(
        'development'  => __( 'In Development', 'toolntip-core' ),
        'available'    => __( 'Available', 'toolntip-core' ),
        'maintenance'  => __( 'Maintenance', 'toolntip-core' ),
        'discontinued' => __( 'Discontinued', 'toolntip-core' ),
    );
}

/**
 * Sanitize Product lifecycle status.
 *
 * Unknown values fail safely to development.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function tnt_sanitize_product_status( $value ) {
    $value = sanitize_key( (string) $value );

    return array_key_exists( $value, tnt_get_product_statuses() )
        ? $value
        : 'development';
}

/**
 * Sanitize a Product text value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function tnt_sanitize_product_text( $value ) {
    return sanitize_text_field( (string) $value );
}

/**
 * Sanitize a Product textarea value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function tnt_sanitize_product_textarea( $value ) {
    return sanitize_textarea_field( (string) $value );
}

/**
 * Authorize Product meta mutation through the owning Product post.
 *
 * @param bool   $allowed Existing authorization result.
 * @param string $meta_key Meta key.
 * @param int    $post_id Product post ID.
 * @param int    $user_id User ID.
 * @return bool
 */
function tnt_auth_product_meta( $allowed, $meta_key, $post_id, $user_id ) {
    unset( $allowed, $meta_key );

    $post_id = absint( $post_id );
    $user_id = absint( $user_id );

    if ( ! $post_id || ! $user_id || 'tnt_product' !== get_post_type( $post_id ) ) {
        return false;
    }

    return user_can( $user_id, 'edit_post', $post_id );
}

/**
 * Register controlled Product metadata.
 *
 * @return void
 */
function tnt_register_product_meta() {
    $text_fields = array(
        '_tnt_product_positioning',
        '_tnt_product_category',
        '_tnt_product_deployment',
        '_tnt_product_technology',
        '_tnt_product_supported_platform',
    );

    register_post_meta(
        'tnt_product',
        '_tnt_product_status',
        array(
            'type'              => 'string',
            'single'            => true,
            'default'           => 'development',
            'show_in_rest'      => true,
            'sanitize_callback' => 'tnt_sanitize_product_status',
            'auth_callback'     => 'tnt_auth_product_meta',
        )
    );

    foreach ( $text_fields as $meta_key ) {
        register_post_meta(
            'tnt_product',
            $meta_key,
            array(
                'type'              => 'string',
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => 'tnt_sanitize_product_text',
                'auth_callback'     => 'tnt_auth_product_meta',
            )
        );
    }

    register_post_meta(
        'tnt_product',
        '_tnt_product_system_requirements',
        array(
            'type'              => 'string',
            'single'            => true,
            'default'           => '',
            'show_in_rest'      => true,
            'sanitize_callback' => 'tnt_sanitize_product_textarea',
            'auth_callback'     => 'tnt_auth_product_meta',
        )
    );

    register_post_meta(
        'tnt_product',
        '_tnt_product_tool_ids',
        array(
            'type'              => 'integer',
            'single'            => false,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => 'tnt_auth_product_meta',
        )
    );

    register_post_meta(
        'tnt_product',
        '_tnt_product_tool_id',
        array(
            'type'              => 'integer',
            'single'            => true,
            'default'           => 0,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => 'tnt_auth_product_meta',
        )
    );
}
add_action( 'init', 'tnt_register_product_meta', 11 );

/**
 * Return a normalized Product status.
 *
 * @param WP_Post|int $product Product object or ID.
 * @return string
 */
function tnt_get_product_status( $product ) {
    $product_id = $product instanceof WP_Post ? $product->ID : absint( $product );

    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return 'development';
    }

    return tnt_sanitize_product_status(
        get_post_meta( $product_id, '_tnt_product_status', true )
    );
}
