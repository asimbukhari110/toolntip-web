<?php
/**
 * Tool Directory Filter and Sort Helpers.
 *
 * WEB-006.3 - URL-driven filtering and sorting for the native Tool archive.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return a sanitized scalar Tool Directory request value.
 *
 * @param string $key Query-string key.
 * @return string
 */
function tnt_get_tool_directory_request_value( $key ) {

    if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return '';
    }

    return sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Return the active Tool Directory filter state.
 *
 * @return array
 */
function tnt_get_tool_directory_filters() {

    $category = sanitize_title( tnt_get_tool_directory_request_value( 'category' ) );
    $type     = tnt_get_tool_directory_request_value( 'type' );
    $pricing  = tnt_get_tool_directory_request_value( 'pricing' );
    $featured = '1' === tnt_get_tool_directory_request_value( 'featured' ) ? '1' : '';
    $sort     = tnt_get_tool_directory_request_value( 'sort' );

    $allowed_sorts = array( 'relevance', 'newest', 'rating', 'name' );

    if ( ! in_array( $sort, $allowed_sorts, true ) ) {
        $sort = '';
    }

    return array(
        'category' => $category,
        'type'     => $type,
        'pricing'  => $pricing,
        'featured' => $featured,
        'sort'     => $sort,
    );
}

/**
 * Return select choices for an ACF field, with published values as fallback.
 *
 * @param string $field_name ACF field name / post-meta key.
 * @return array Value => label pairs.
 */
function tnt_get_tool_directory_field_choices( $field_name ) {

    $choices = array();

    if ( function_exists( 'get_field_object' ) ) {
        $field = get_field_object( $field_name, false, false, false );

        if ( is_array( $field ) && ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
            foreach ( $field['choices'] as $value => $label ) {
                $value = trim( (string) $value );
                $label = trim( (string) $label );

                if ( '' !== $value ) {
                    $choices[ $value ] = '' !== $label ? $label : $value;
                }
            }
        }
    }

    if ( ! empty( $choices ) ) {
        return $choices;
    }

    global $wpdb;

    $values = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
               AND p.post_type = 'tool'
               AND p.post_status = 'publish'
               AND pm.meta_value <> ''
             ORDER BY pm.meta_value ASC",
            $field_name
        )
    );

    foreach ( $values as $value ) {
        $value = trim( (string) $value );

        if ( '' !== $value && ! is_serialized( $value ) ) {
            $choices[ $value ] = $value;
        }
    }

    return $choices;
}

/**
 * Return Tool Categories that are actually assigned to published tools.
 *
 * @return WP_Term[]
 */
function tnt_get_tool_directory_categories() {

    $terms = get_terms(
        array(
            'taxonomy'   => 'tool_category',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Return whether any published Tool is marked featured.
 *
 * @return bool
 */
function tnt_tool_directory_has_featured_tools() {

    $ids = get_posts(
        array(
            'post_type'              => 'tool',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => 'featured_tool',
                    'value'   => array( '1', 1, 'true' ),
                    'compare' => 'IN',
                ),
            ),
        )
    );

    return ! empty( $ids );
}

/**
 * Apply WEB-006.3 filters and sorting to the native Tool archive query.
 *
 * @param WP_Query $query Current WordPress query.
 * @return void
 */
function tnt_apply_tool_directory_filters( $query ) {

    if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'tool' ) ) {
        return;
    }

    $filters    = tnt_get_tool_directory_filters();
    $meta_query = array();

    if ( '' !== $filters['category'] ) {
        $query->set(
            'tax_query',
            array(
                array(
                    'taxonomy' => 'tool_category',
                    'field'    => 'slug',
                    'terms'    => $filters['category'],
                ),
            )
        );
    }

    if ( '' !== $filters['type'] ) {
        $meta_query[] = array(
            'key'     => 'tool_type',
            'value'   => $filters['type'],
            'compare' => '=',
        );
    }

    if ( '' !== $filters['pricing'] ) {
        $meta_query[] = array(
            'key'     => 'pricing',
            'value'   => $filters['pricing'],
            'compare' => '=',
        );
    }

    if ( '1' === $filters['featured'] ) {
        $meta_query[] = array(
            'key'     => 'featured_tool',
            'value'   => array( '1', 1, 'true' ),
            'compare' => 'IN',
        );
    }

    if ( ! empty( $meta_query ) ) {
        if ( count( $meta_query ) > 1 ) {
            $meta_query['relation'] = 'AND';
        }

        $query->set( 'meta_query', $meta_query );
    }

    switch ( $filters['sort'] ) {
        case 'name':
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'ASC' );
            break;

        case 'rating':
            $query->set( 'meta_key', 'editor_rating' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'DESC' );
            break;

        case 'relevance':
            if ( '' !== tnt_get_tool_directory_search_term() ) {
                $query->set( 'orderby', 'relevance' );
            } else {
                $query->set( 'orderby', 'date' );
                $query->set( 'order', 'DESC' );
            }
            break;

        case 'newest':
        default:
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
            break;
    }
}

add_action( 'pre_get_posts', 'tnt_apply_tool_directory_filters', 20 );
