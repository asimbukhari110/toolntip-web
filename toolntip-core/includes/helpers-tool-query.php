<?php
/**
 * Tool Shortcode Query Engine.
 *
 * Shared parsing, query construction, and focused card-data helpers for
 * ToolNTip Tool collection shortcodes.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parse a shortcode/request boolean value.
 *
 * @param mixed $value   Incoming value.
 * @param bool  $default Fallback value.
 * @return bool
 */
function tnt_tool_query_parse_bool( $value, $default = false ) {

    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return 1 === (int) $value;
    }

    $value = strtolower( trim( (string) $value ) );

    if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
        return true;
    }

    if ( in_array( $value, array( '0', 'false', 'no', 'off' ), true ) ) {
        return false;
    }

    return (bool) $default;
}

/**
 * Normalize a comma- or whitespace-separated slug/value list.
 *
 * @param mixed $value Raw list.
 * @return string[]
 */
function tnt_tool_query_parse_list( $value ) {

    if ( is_array( $value ) ) {
        $parts = $value;
    } else {
        $parts = preg_split( '/[\s,]+/', trim( (string) $value ), -1, PREG_SPLIT_NO_EMPTY );
    }

    if ( empty( $parts ) ) {
        return array();
    }

    $parts = array_map(
        static function ( $part ) {
            return sanitize_key( trim( (string) $part ) );
        },
        $parts
    );

    return array_values( array_unique( array_filter( $parts ) ) );
}

/**
 * Normalize a supported grid column count.
 *
 * @param mixed $value   Requested columns.
 * @param int[] $allowed Allowed values.
 * @param int   $default Default columns.
 * @return int
 */
function tnt_tool_query_normalize_columns( $value, $allowed, $default ) {

    $columns = absint( $value );

    return in_array( $columns, $allowed, true ) ? $columns : $default;
}

/**
 * Return a sanitized GET value while preserving whether the key was present.
 *
 * Presence matters because an explicitly empty GET value must be able to clear
 * a shortcode fallback filter.
 *
 * @param string $key Request key.
 * @return array{present:bool,value:string}
 */
function tnt_tool_query_request_value( $key ) {

    if ( ! array_key_exists( $key, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return array(
            'present' => false,
            'value'   => '',
        );
    }

    $value = $_GET[ $key ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( is_array( $value ) ) {
        $value = implode( ',', array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
    } else {
        $value = sanitize_text_field( wp_unslash( $value ) );
    }

    return array(
        'present' => true,
        'value'   => trim( (string) $value ),
    );
}

/**
 * Resolve master-archive state: GET wins; shortcode values are fallbacks.
 *
 * The filter fallbacks are intentionally accepted even though the primary
 * archive attribute matrix focuses on presentation controls. This lets a page
 * establish an initial curated archive state while remaining URL-interactive.
 *
 * @param array $atts Parsed shortcode attributes.
 * @return array
 */
function tnt_get_tool_shortcode_archive_state( $atts ) {

    $keys  = array( 'tool_search', 'category', 'type', 'pricing', 'featured', 'sort' );
    $state = array();

    foreach ( $keys as $key ) {
        $request = tnt_tool_query_request_value( $key );
        $state[ $key ] = $request['present']
            ? $request['value']
            : (string) ( $atts[ $key ] ?? '' );
    }

    $state['tool_search'] = sanitize_text_field( $state['tool_search'] );
    $state['category']    = implode( ',', tnt_tool_query_parse_list( $state['category'] ) );
    $state['type']        = implode( ',', tnt_tool_query_parse_list( $state['type'] ) );
    $state['pricing']     = implode( ',', tnt_tool_query_parse_list( $state['pricing'] ) );

    $featured = strtolower( trim( (string) $state['featured'] ) );
    if ( in_array( $featured, array( '1', 'true', 'yes', 'on' ), true ) ) {
        $state['featured'] = '1';
    } elseif ( in_array( $featured, array( '0', 'false', 'no', 'off' ), true ) ) {
        $state['featured'] = '0';
    } else {
        $state['featured'] = '';
    }

    $allowed_sorts = array( 'relevance', 'newest', 'rating', 'name' );
    $state['sort']  = sanitize_key( $state['sort'] );

    if ( ! in_array( $state['sort'], $allowed_sorts, true ) ) {
        $state['sort'] = '';
    }

    return $state;
}

/**
 * Append Tool taxonomy filters to WP_Query arguments.
 *
 * @param array $args    Query arguments.
 * @param array $filters Normalized filters.
 * @return array
 */
function tnt_tool_query_apply_tax_filters( $args, $filters ) {

    $tax_query  = array();
    $categories = tnt_tool_query_parse_list( $filters['category'] ?? '' );
    $tags       = tnt_tool_query_parse_list( $filters['tag'] ?? '' );

    if ( ! empty( $categories ) ) {
        $tax_query[] = array(
            'taxonomy' => 'tool_category',
            'field'    => 'slug',
            'terms'    => $categories,
            'operator' => 'IN',
        );
    }

    if ( ! empty( $tags ) ) {
        $tax_query[] = array(
            'taxonomy' => 'tool_tag',
            'field'    => 'slug',
            'terms'    => $tags,
            'operator' => 'IN',
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    return $args;
}

/**
 * Append Tool meta filters to WP_Query arguments.
 *
 * @param array $args    Query arguments.
 * @param array $filters Normalized filters.
 * @return array
 */
function tnt_tool_query_apply_meta_filters( $args, $filters ) {

    $meta_query = array();
    $types      = tnt_tool_query_parse_list( $filters['type'] ?? '' );
    $pricing    = tnt_tool_query_parse_list( $filters['pricing'] ?? '' );
    $featured   = $filters['featured'] ?? '';

    if ( ! empty( $types ) ) {
        $meta_query[] = array(
            'key'     => 'tool_type',
            'value'   => count( $types ) > 1 ? $types : $types[0],
            'compare' => count( $types ) > 1 ? 'IN' : '=',
        );
    }

    if ( ! empty( $pricing ) ) {
        $meta_query[] = array(
            'key'     => 'pricing',
            'value'   => count( $pricing ) > 1 ? $pricing : $pricing[0],
            'compare' => count( $pricing ) > 1 ? 'IN' : '=',
        );
    }

    if ( '' !== $featured ) {
        if ( tnt_tool_query_parse_bool( $featured, false ) ) {
            $meta_query[] = array(
                'key'     => 'featured_tool',
                'value'   => array( '1', 1, 'true' ),
                'compare' => 'IN',
            );
        } else {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'featured_tool',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'featured_tool',
                    'value'   => array( '1', 1, 'true' ),
                    'compare' => 'NOT IN',
                ),
            );
        }
    }

    if ( count( $meta_query ) > 1 ) {
        $meta_query['relation'] = 'AND';
    }

    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }

    return $args;
}

/**
 * Build WP_Query arguments for the interactive archive shortcode.
 *
 * @param array $state          Normalized URL/shortcode state.
 * @param int   $posts_per_page Page size.
 * @param int   $paged          Page number.
 * @return array
 */
function tnt_build_tool_archive_shortcode_query_args( $state, $posts_per_page, $paged ) {

    $args = array(
        'post_type'           => 'tool',
        'post_status'         => 'publish',
        'posts_per_page'      => max( 1, absint( $posts_per_page ) ),
        'paged'               => max( 1, absint( $paged ) ),
        'ignore_sticky_posts' => true,
    );

    $search = trim( (string) ( $state['tool_search'] ?? '' ) );

    if ( '' !== $search ) {
        $args['s'] = $search;
    }

    $args = tnt_tool_query_apply_tax_filters( $args, $state );
    $args = tnt_tool_query_apply_meta_filters( $args, $state );

    switch ( $state['sort'] ?? '' ) {
        case 'name':
            $args['orderby'] = 'title';
            $args['order']   = 'ASC';
            break;

        case 'rating':
            $args['meta_key'] = 'editor_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
            break;

        case 'relevance':
            if ( '' !== $search ) {
                $args['orderby'] = 'relevance';
            } else {
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
            }
            break;

        case 'newest':
        default:
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
            break;
    }

    return $args;
}

/**
 * Build WP_Query arguments for a curated Tool collection.
 *
 * @param array $atts Parsed shortcode attributes.
 * @return array
 */
function tnt_build_tools_shortcode_query_args( $atts ) {

    $limit   = absint( $atts['limit'] ?? 8 );
    $orderby = sanitize_key( $atts['orderby'] ?? 'date' );
    $order   = strtoupper( sanitize_key( $atts['order'] ?? 'DESC' ) );

    if ( $limit < 1 ) {
        $limit = 8;
    }

    if ( ! in_array( $orderby, array( 'date', 'title', 'rating', 'rand' ), true ) ) {
        $orderby = 'date';
    }

    if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
        $order = 'DESC';
    }

    $args = array(
        'post_type'              => 'tool',
        'post_status'            => 'publish',
        'posts_per_page'         => $limit,
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'order'                  => $order,
    );

    $filters = array(
        'category' => $atts['category'] ?? '',
        'tag'      => $atts['tag'] ?? '',
        'type'     => $atts['type'] ?? '',
        'pricing'  => $atts['pricing'] ?? '',
        'featured' => $atts['featured'] ?? '',
    );

    $args = tnt_tool_query_apply_tax_filters( $args, $filters );
    $args = tnt_tool_query_apply_meta_filters( $args, $filters );

    if ( 'rating' === $orderby ) {
        $args['meta_key'] = 'editor_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $args['orderby']  = 'meta_value_num';
    } else {
        $args['orderby'] = $orderby;
    }

    return $args;
}

/**
 * Return focused Tool Card data without loading Tool Detail-only domains.
 *
 * @param WP_Post|int|string $tool Tool object, ID, or slug.
 * @return array|null
 */
function tnt_get_tool_card_data( $tool ) {

    if ( ! $tool instanceof WP_Post ) {
        $tool = tnt_get_tool( $tool );
    }

    if ( ! $tool || 'tool' !== $tool->post_type ) {
        return null;
    }

    $platforms = get_field( 'platform', $tool->ID );

    if ( empty( $platforms ) ) {
        $platforms = array();
    } elseif ( ! is_array( $platforms ) ) {
        $platforms = array( $platforms );
    }

    $platforms = array_values(
        array_filter(
            array_map(
                static function ( $platform ) {
                    return trim( (string) $platform );
                },
                $platforms
            )
        )
    );

    $editor_rating = (float) get_field( 'editor_rating', $tool->ID );
    $actions       = tnt_get_tool_actions( $tool );

    return array(
        'id'         => $tool->ID,
        'post'       => $tool,
        'post_id'    => $tool->ID,
        'title'      => get_the_title( $tool ),
        'slug'       => $tool->post_name,
        'pricing'    => get_field( 'pricing', $tool->ID ),
        'platform'   => $platforms,
        'featured'   => (bool) get_field( 'featured_tool', $tool->ID ),
        'features'   => tnt_get_tool_features( $tool ),
        'hero'       => array(
            'tagline' => get_field( 'tool_tagline', $tool->ID ),
        ),
        'rating'     => array(
            'value' => $editor_rating,
            'max'   => 5,
        ),
        'categories' => tnt_get_tool_categories( $tool ),
        'logo'       => tnt_get_tool_logo( $tool ),
        'actions'    => $actions,
        'use_tool'   => ! empty( $actions['use_tool'] ) ? $actions['use_tool'] : array(),
    );
}

/**
 * Enqueue the semantic collection-grid stylesheet.
 *
 * @return void
 */
function tnt_enqueue_tool_collection_assets() {

    wp_enqueue_style(
        'tnt-tool-collections',
        TNT_CORE_URL . 'assets/css/tool-collections.css',
        array( 'toolntip-core', 'tnt-tool-card' ),
        TNT_CORE_VERSION
    );
}
