<?php
/**
 * ToolNTip Product relationship repositories.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the Product associated with a Tool.
 *
 * A Tool can belong to only one Product, while a Product may own multiple Tools.
 * The legacy single-Tool meta remains queryable for backward compatibility.
 *
 * @param WP_Post|int $tool Tool object or ID.
 * @param bool        $public_only Require a published Product.
 * @return WP_Post|null
 */
function tnt_get_product_for_tool( $tool, $public_only = false ) {
    $tool_id = $tool instanceof WP_Post ? absint( $tool->ID ) : absint( $tool );

    if ( ! $tool_id || 'tool' !== get_post_type( $tool_id ) ) {
        return null;
    }

    $query = new WP_Query(
        array(
            'post_type'              => 'tnt_product',
            'post_status'            => $public_only ? 'publish' : array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                'relation' => 'OR',
                array(
                    'key'     => '_tnt_product_tool_ids',
                    'value'   => $tool_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_tnt_product_tool_id',
                    'value'   => $tool_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        )
    );

    if ( empty( $query->posts ) ) {
        return null;
    }

    return get_post( absint( $query->posts[0] ) );
}

/**
 * Normalize ordered Product Tool IDs.
 *
 * @param mixed $tool_ids Candidate IDs.
 * @return int[]
 */
function tnt_normalize_product_tool_ids( $tool_ids ) {
    if ( ! is_array( $tool_ids ) ) {
        $tool_ids = empty( $tool_ids ) ? array() : array( $tool_ids );
    }

    $normalized = array();
    $seen       = array();

    foreach ( $tool_ids as $tool_id ) {
        $tool_id = absint( $tool_id );
        if ( ! $tool_id || isset( $seen[ $tool_id ] ) || 'tool' !== get_post_type( $tool_id ) || 'trash' === get_post_status( $tool_id ) ) {
            continue;
        }
        $seen[ $tool_id ] = true;
        $normalized[]     = $tool_id;
    }

    return $normalized;
}

/**
 * Return ordered Tool IDs associated with a Product.
 *
 * Legacy Products that only have _tnt_product_tool_id are normalized on read.
 *
 * @param WP_Post|int $product Product object or ID.
 * @param bool        $public_only Require published Tools.
 * @param int         $limit Optional maximum number of IDs; zero means unlimited.
 * @return int[]
 */
function tnt_get_product_tool_ids( $product, $public_only = false, $limit = 0 ) {
    $product_id = $product instanceof WP_Post ? absint( $product->ID ) : absint( $product );
    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return array();
    }

    $tool_ids = get_post_meta( $product_id, '_tnt_product_tool_ids', false );
    if ( empty( $tool_ids ) ) {
        $legacy_tool_id = absint( get_post_meta( $product_id, '_tnt_product_tool_id', true ) );
        $tool_ids       = $legacy_tool_id ? array( $legacy_tool_id ) : array();
    }

    $tool_ids = tnt_normalize_product_tool_ids( $tool_ids );
    if ( $public_only ) {
        $tool_ids = array_values( array_filter( $tool_ids, static function ( $tool_id ) {
            return 'publish' === get_post_status( $tool_id );
        } ) );
    }

    if ( $limit > 0 ) {
        $tool_ids = array_slice( $tool_ids, 0, $limit );
    }

    return $tool_ids;
}

/**
 * Replace the ordered Product-to-Tool relationship set.
 *
 * Product remains the relationship authority. A Tool cannot be owned by a
 * second Product. The first selected Tool is mirrored to the legacy single
 * meta key so older callers remain compatible.
 *
 * @param int   $product_id Product ID.
 * @param int[] $tool_ids Ordered Tool IDs.
 * @return true|WP_Error
 */
function tnt_set_product_tools( $product_id, $tool_ids ) {
    $product_id = absint( $product_id );
    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return new WP_Error( 'tnt_invalid_product', __( 'A valid Product is required.', 'toolntip-core' ) );
    }

    $tool_ids = tnt_normalize_product_tool_ids( $tool_ids );
    foreach ( $tool_ids as $tool_id ) {
        $owner = tnt_get_product_for_tool( $tool_id, false );
        if ( $owner && absint( $owner->ID ) !== $product_id ) {
            return new WP_Error( 'tnt_tool_already_linked', sprintf( __( 'The Tool “%s” is already associated with another Product.', 'toolntip-core' ), get_the_title( $tool_id ) ) );
        }
    }

    delete_post_meta( $product_id, '_tnt_product_tool_ids' );
    foreach ( $tool_ids as $tool_id ) {
        add_post_meta( $product_id, '_tnt_product_tool_ids', $tool_id, false );
    }

    if ( ! empty( $tool_ids ) ) {
        update_post_meta( $product_id, '_tnt_product_tool_id', $tool_ids[0] );
    } else {
        delete_post_meta( $product_id, '_tnt_product_tool_id' );
    }

    return true;
}

/**
 * Backward-compatible single Tool setter.
 *
 * @param int $product_id Product ID.
 * @param int $tool_id Tool ID, or zero to clear.
 * @return true|WP_Error
 */
function tnt_set_product_tool( $product_id, $tool_id ) {
    $tool_id = absint( $tool_id );
    return tnt_set_product_tools( $product_id, $tool_id ? array( $tool_id ) : array() );
}

/**
 * Return Tools associated with a Product.
 *
 * @param WP_Post|int $product Product object or ID.
 * @param bool        $public_only Require published Tools.
 * @param int         $limit Optional maximum number of Tools.
 * @return WP_Post[]
 */
function tnt_get_tools_for_product( $product, $public_only = false, $limit = 0 ) {
    $tools = array();
    foreach ( tnt_get_product_tool_ids( $product, $public_only, $limit ) as $tool_id ) {
        $tool = get_post( $tool_id );
        if ( $tool instanceof WP_Post ) {
            $tools[] = $tool;
        }
    }
    return $tools;
}

/**
 * Backward-compatible first Tool accessor.
 *
 * @param WP_Post|int $product Product object or ID.
 * @return WP_Post|null
 */
function tnt_get_tool_for_product( $product ) {
    $tools = tnt_get_tools_for_product( $product, false, 1 );
    return ! empty( $tools ) ? $tools[0] : null;
}

/**
 * Normalize ordered Product Resource IDs.
 *
 * @param mixed $resource_ids Candidate IDs.
 * @return int[]
 */
function tnt_normalize_product_resource_ids( $resource_ids ) {
    if ( ! is_array( $resource_ids ) ) {
        $resource_ids = empty( $resource_ids ) ? array() : array( $resource_ids );
    }

    $normalized = array();
    $seen       = array();

    foreach ( $resource_ids as $resource_id ) {
        $resource_id = absint( $resource_id );

        if ( ! $resource_id || isset( $seen[ $resource_id ] ) || 'resource' !== get_post_type( $resource_id ) ) {
            continue;
        }

        $seen[ $resource_id ] = true;
        $normalized[]         = $resource_id;
    }

    return $normalized;
}

/**
 * Replace the ordered Product-to-Resource relationship set.
 *
 * @param int   $product_id Product ID.
 * @param mixed $resource_ids Ordered Resource IDs.
 * @return true|WP_Error
 */
function tnt_set_product_resources( $product_id, $resource_ids ) {
    global $wpdb;

    $product_id = absint( $product_id );

    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return new WP_Error( 'tnt_invalid_product', __( 'A valid Product is required.', 'toolntip-core' ) );
    }

    if ( ! tnt_product_platform_ready() ) {
        return new WP_Error( 'tnt_product_platform_unavailable', __( 'Product platform database is unavailable.', 'toolntip-core' ) );
    }

    $resource_ids = tnt_normalize_product_resource_ids( $resource_ids );
    $tables       = tnt_get_product_table_names();
    $now          = current_time( 'mysql', true );

    $deleted = $wpdb->delete( $tables['resources'], array( 'product_id' => $product_id ), array( '%d' ) );

    if ( false === $deleted ) {
        return new WP_Error( 'tnt_product_resources_save_failed', __( 'Product Resources could not be saved.', 'toolntip-core' ) );
    }

    foreach ( $resource_ids as $index => $resource_id ) {
        $inserted = $wpdb->insert(
            $tables['resources'],
            array(
                'product_id'    => $product_id,
                'resource_id'   => $resource_id,
                'display_order' => ( $index + 1 ) * 10,
                'created_at'    => $now,
            ),
            array( '%d', '%d', '%d', '%s' )
        );

        if ( false === $inserted ) {
            return new WP_Error( 'tnt_product_resources_save_failed', __( 'Product Resources could not be saved.', 'toolntip-core' ) );
        }
    }

    return true;
}

/**
 * Return ordered Resource IDs for a Product.
 *
 * @param int  $product_id Product ID.
 * @param bool $published_only Require published Resources.
 * @param int  $limit Optional maximum number of IDs; zero means unlimited.
 * @return int[]
 */
function tnt_get_product_resource_ids( $product_id, $published_only = false, $limit = 0 ) {
    global $wpdb;

    $product_id = absint( $product_id );
    $limit      = absint( $limit );

    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) || ! tnt_product_platform_ready() ) {
        return array();
    }

    $tables = tnt_get_product_table_names();
    $ids    = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT resource_id FROM {$tables['resources']} WHERE product_id = %d ORDER BY display_order ASC, id ASC",
            $product_id
        )
    );

    $ids = array_values( array_map( 'absint', $ids ) );

    if ( $published_only ) {
        $ids = array_values(
            array_filter(
                $ids,
                static function ( $resource_id ) {
                    return 'resource' === get_post_type( $resource_id ) && 'publish' === get_post_status( $resource_id );
                }
            )
        );
    }

    return $limit ? array_slice( $ids, 0, $limit ) : $ids;
}

/**
 * Return ordered Product IDs related to a Resource.
 *
 * @param int  $resource_id Resource ID.
 * @param bool $published_only Require published Products.
 * @return int[]
 */
function tnt_get_products_for_resource( $resource_id, $published_only = false ) {
    global $wpdb;

    $resource_id = absint( $resource_id );

    if ( ! $resource_id || 'resource' !== get_post_type( $resource_id ) || ! tnt_product_platform_ready() ) {
        return array();
    }

    $tables = tnt_get_product_table_names();
    $ids    = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT product_id FROM {$tables['resources']} WHERE resource_id = %d ORDER BY product_id ASC, id ASC",
            $resource_id
        )
    );

    $ids = array_values( array_unique( array_map( 'absint', $ids ) ) );

    if ( $published_only ) {
        $ids = array_values(
            array_filter(
                $ids,
                static function ( $product_id ) {
                    return 'tnt_product' === get_post_type( $product_id ) && 'publish' === get_post_status( $product_id );
                }
            )
        );
    }

    return $ids;
}
