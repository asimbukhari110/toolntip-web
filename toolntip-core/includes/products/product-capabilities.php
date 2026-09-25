<?php
/**
 * Product capability data contract and access service.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the controlled capability icon identifiers supported by Core.
 *
 * Empty is valid and means that no icon is requested.
 *
 * @return array<string,string>
 */
function tnt_get_product_capability_icons() {
    return apply_filters(
        'tnt_product_capability_icons',
        array(
            'identity'   => __( 'Identity', 'toolntip-core' ),
            'lifecycle'  => __( 'Lifecycle', 'toolntip-core' ),
            'policy'     => __( 'Policy', 'toolntip-core' ),
            'compliance' => __( 'Compliance', 'toolntip-core' ),
            'evidence'   => __( 'Evidence', 'toolntip-core' ),
            'approval'   => __( 'Approval', 'toolntip-core' ),
            'access'     => __( 'Access', 'toolntip-core' ),
            'directory'  => __( 'Directory', 'toolntip-core' ),
            'enforcement'=> __( 'Enforcement', 'toolntip-core' ),
            'audit'      => __( 'Audit', 'toolntip-core' ),
            'reporting'  => __( 'Reporting', 'toolntip-core' ),
            'passport'   => __( 'Passport', 'toolntip-core' ),
        )
    );
}


/**
 * Render a controlled public capability icon.
 *
 * Icons are inline SVG so Product presentation does not depend on a theme
 * icon font or third-party asset library. The icon key is already governed
 * by tnt_sanitize_product_capability_icon().
 *
 * @param string $icon Capability icon key.
 * @return string
 */
function tnt_render_product_capability_icon( $icon ) {
    $icon = tnt_sanitize_product_capability_icon( $icon );

    if ( '' === $icon ) {
        return '';
    }

    $paths = array(
        'identity'    => '<circle cx="12" cy="8" r="3"></circle><path d="M5.5 20a6.5 6.5 0 0 1 13 0"></path>',
        'lifecycle'   => '<path d="M20 7v5h-5"></path><path d="M4 17v-5h5"></path><path d="M6.1 7.5A7 7 0 0 1 18 5l2 2"></path><path d="M17.9 16.5A7 7 0 0 1 6 19l-2-2"></path>',
        'policy'      => '<path d="M12 3l7 3v5c0 4.8-2.8 8-7 10-4.2-2-7-5.2-7-10V6l7-3z"></path><path d="M9 12l2 2 4-5"></path>',
        'compliance'  => '<circle cx="12" cy="12" r="8"></circle><path d="M8.5 12l2.2 2.2L15.8 9"></path>',
        'evidence'    => '<path d="M7 3h7l4 4v14H7z"></path><path d="M14 3v5h5"></path><path d="M9 14l2 2 4-4"></path>',
        'approval'    => '<circle cx="12" cy="12" r="8"></circle><path d="M8.5 12l2.2 2.2L15.8 9"></path>',
        'access'      => '<circle cx="8" cy="12" r="3"></circle><path d="M11 12h9"></path><path d="M17 12v3"></path><path d="M14 12v2"></path>',
        'directory'   => '<rect x="9" y="3" width="6" height="5" rx="1"></rect><rect x="3" y="16" width="6" height="5" rx="1"></rect><rect x="15" y="16" width="6" height="5" rx="1"></rect><path d="M12 8v4M6 16v-2h12v2"></path>',
        'enforcement' => '<path d="M12 3l7 3v5c0 4.8-2.8 8-7 10-4.2-2-7-5.2-7-10V6l7-3z"></path><path d="M13 7l-3 6h3l-2 4 5-7h-3z"></path>',
        'audit'       => '<rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4.5V3h6v1.5"></path><path d="M9 10h6M9 14h6M9 18h4"></path>',
        'reporting'   => '<path d="M4 20V10h4v10M10 20V5h4v15M16 20v-7h4v7"></path>',
        'passport'    => '<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="10" cy="10" r="2"></circle><path d="M7 16a3 3 0 0 1 6 0M15 9h3M15 13h3"></path>',
    );

    if ( ! isset( $paths[ $icon ] ) ) {
        return '';
    }

    return sprintf(
        '<span class="tnt-product-capability__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">%s</svg></span>',
        $paths[ $icon ]
    );
}

/**
 * Sanitize a capability icon identifier.
 *
 * @param mixed $value Raw icon value.
 * @return string
 */
function tnt_sanitize_product_capability_icon( $value ) {
    $value = sanitize_key( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    return array_key_exists( $value, tnt_get_product_capability_icons() )
        ? $value
        : '';
}

/**
 * Normalize a single Product capability record.
 *
 * A capability is valid only when both title and description are present.
 *
 * @param mixed $capability Raw capability value.
 * @param int   $fallback_order Stable fallback order.
 * @return array<string,mixed>|null
 */
function tnt_normalize_product_capability( $capability, $fallback_order = 0 ) {
    if ( ! is_array( $capability ) ) {
        return null;
    }

    $title       = isset( $capability['title'] ) ? sanitize_text_field( (string) $capability['title'] ) : '';
    $description = isset( $capability['description'] ) ? sanitize_textarea_field( (string) $capability['description'] ) : '';

    if ( '' === $title || '' === $description ) {
        return null;
    }

    $order = isset( $capability['order'] ) ? absint( $capability['order'] ) : absint( $fallback_order );

    return array(
        'title'       => $title,
        'description' => $description,
        'icon'        => isset( $capability['icon'] ) ? tnt_sanitize_product_capability_icon( $capability['icon'] ) : '',
        'order'       => $order,
    );
}

/**
 * Sanitize and deterministically order a Product capability collection.
 *
 * @param mixed $value Raw capability collection.
 * @return array<int,array<string,mixed>>
 */
function tnt_sanitize_product_capabilities( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    $normalized = array();
    $sequence   = 0;

    foreach ( $value as $capability ) {
        ++$sequence;

        $item = tnt_normalize_product_capability( $capability, $sequence * 10 );

        if ( null === $item ) {
            continue;
        }

        $item['_sequence'] = $sequence;
        $normalized[]      = $item;
    }

    usort(
        $normalized,
        static function ( $left, $right ) {
            $order_compare = $left['order'] <=> $right['order'];

            if ( 0 !== $order_compare ) {
                return $order_compare;
            }

            return $left['_sequence'] <=> $right['_sequence'];
        }
    );

    foreach ( $normalized as &$item ) {
        unset( $item['_sequence'] );
    }
    unset( $item );

    return array_values( $normalized );
}

/**
 * Register the controlled Product capability collection.
 *
 * @return void
 */
function tnt_register_product_capabilities_meta() {
    register_post_meta(
        'tnt_product',
        '_tnt_product_capabilities',
        array(
            'type'              => 'array',
            'single'            => true,
            'default'           => array(),
            'show_in_rest'      => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'properties'           => array(
                            'title'       => array( 'type' => 'string' ),
                            'description' => array( 'type' => 'string' ),
                            'icon'        => array( 'type' => 'string' ),
                            'order'       => array( 'type' => 'integer' ),
                        ),
                    ),
                ),
            ),
            'sanitize_callback' => 'tnt_sanitize_product_capabilities',
            'auth_callback'     => 'tnt_auth_product_meta',
        )
    );
}
add_action( 'init', 'tnt_register_product_capabilities_meta', 12 );

/**
 * Return normalized Product capabilities in deterministic display order.
 *
 * @param WP_Post|int $product Product object or ID.
 * @return array<int,array<string,mixed>>
 */
function tnt_get_product_capabilities( $product ) {
    $product_id = $product instanceof WP_Post ? $product->ID : absint( $product );

    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return array();
    }

    return tnt_sanitize_product_capabilities(
        get_post_meta( $product_id, '_tnt_product_capabilities', true )
    );
}

/**
 * Persist the complete normalized Product capability collection.
 *
 * This is the authoritative write service for server-side Product capability
 * mutations. UI and REST mutations remain protected by Product meta auth.
 *
 * @param WP_Post|int $product      Product object or ID.
 * @param mixed       $capabilities Raw capability collection.
 * @return bool|WP_Error
 */
function tnt_set_product_capabilities( $product, $capabilities ) {
    $product_id = $product instanceof WP_Post ? $product->ID : absint( $product );

    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return new WP_Error(
            'tnt_invalid_product',
            __( 'A valid Product is required.', 'toolntip-core' )
        );
    }

    $normalized = tnt_sanitize_product_capabilities( $capabilities );

    if ( empty( $normalized ) ) {
        return delete_post_meta( $product_id, '_tnt_product_capabilities' ) ||
            ! metadata_exists( 'post', $product_id, '_tnt_product_capabilities' );
    }

    return false !== update_post_meta(
        $product_id,
        '_tnt_product_capabilities',
        $normalized
    );
}
