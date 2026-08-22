<?php
/**
 * Resource Hub helpers.
 *
 * WEB-007.4 / 4.5
 *
 * Thin discovery/orchestration helpers layered over the frozen Resource Query
 * Engine (4.3) and Resource Card/Collection presentation (4.4).
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the canonical Resource Hub URL.
 *
 * @return string
 */
function tnt_resource_hub_url() {

    $url = get_post_type_archive_link( 'resource' );

    return $url ? $url : home_url( '/resources/' );
}

/**
 * Read the public Resource Hub search value.
 *
 * @return string
 */
function tnt_resource_hub_search_value() {

    if ( ! isset( $_GET['resource_search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return '';
    }

    return sanitize_text_field(
        wp_unslash( $_GET['resource_search'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    );
}

/**
 * Return the current Hub result page.
 *
 * Pretty archive pagination is preferred. resource_page remains supported as
 * a fail-safe query-string transport for embedded/Page-builder contexts.
 *
 * @return int
 */
function tnt_resource_hub_current_page() {

    $page = max(
        1,
        absint( get_query_var( 'paged' ) ),
        absint( get_query_var( 'page' ) )
    );

    if ( isset( $_GET['resource_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = max(
            1,
            absint( wp_unslash( $_GET['resource_page'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        );
    }

    return $page;
}

/**
 * Return Resource Type discovery terms.
 *
 * resource_type belongs only to Resources, so native hide_empty counts are
 * sufficient and do not expose unrelated Tool taxonomy state.
 *
 * @return WP_Term[]
 */
function tnt_resource_hub_type_terms() {

    $terms = get_terms(
        array(
            'taxonomy'   => 'resource_type',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Return shared Tool Category terms that are actually used by published
 * Resources.
 *
 * tool_category is shared by Tools and Resources. Native taxonomy term counts
 * therefore cannot distinguish Resource-only usage. A targeted relationship
 * query selects only term IDs attached to published Resource posts, avoiding a
 * PHP-side Resource catalog scan and preventing Tool-only categories from
 * appearing in Resource Hub discovery.
 *
 * @return WP_Term[]
 */
function tnt_resource_hub_topic_terms() {

    global $wpdb;

    $term_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT tt.term_id
             FROM {$wpdb->term_taxonomy} AS tt
             INNER JOIN {$wpdb->term_relationships} AS tr
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->posts} AS p
                ON p.ID = tr.object_id
             WHERE tt.taxonomy = %s
               AND p.post_type = %s
               AND p.post_status = %s",
            'tool_category',
            'resource',
            'publish'
        )
    );

    $term_ids = array_values(
        array_unique(
            array_filter(
                array_map( 'absint', $term_ids )
            )
        )
    );

    if ( empty( $term_ids ) ) {
        return array();
    }

    $terms = get_terms(
        array(
            'taxonomy'   => 'tool_category',
            'include'    => $term_ids,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Return a canonical Resource Topic URL for a shared tool_category term.
 *
 * The shared taxonomy's native permalink is /tool-category/{slug}/. Resource
 * discovery owns the frozen /resources/topic/{slug}/ route instead.
 *
 * @param WP_Term $term Topic term.
 * @return string
 */
function tnt_resource_hub_topic_url( $term ) {

    if ( ! $term instanceof WP_Term || 'tool_category' !== $term->taxonomy ) {
        return '';
    }

    return home_url(
        user_trailingslashit(
            'resources/topic/' . $term->slug
        )
    );
}

/**
 * Return pagination markup for a Resource Hub query.
 *
 * Search state is preserved. Pretty /resources/page/N/ links are used when the
 * Hub is rendered on the Resource archive; otherwise resource_page is used so
 * embedded/Page-builder contexts remain deterministic.
 *
 * @param WP_Query $query  Resource query.
 * @param string   $search Current search value.
 * @return string
 */
function tnt_resource_hub_pagination( $query, $search = '' ) {

    if ( ! $query instanceof WP_Query || $query->max_num_pages <= 1 ) {
        return '';
    }

    $current = tnt_resource_hub_current_page();
    $total   = max( 1, absint( $query->max_num_pages ) );
    $search  = sanitize_text_field( (string) $search );

    if ( is_post_type_archive( 'resource' ) ) {
        $base = trailingslashit( tnt_resource_hub_url() ) . '%_%';
        $format = 'page/%#%/';
        $add_args = array();
    } else {
        $base = add_query_arg( 'resource_page', '%#%', tnt_resource_hub_url() );
        $format = '';
        $add_args = array();
    }

    if ( '' !== $search ) {
        $add_args['resource_search'] = $search;
    }

    $links = paginate_links(
        array(
            'base'      => $base,
            'format'    => $format,
            'current'   => $current,
            'total'     => $total,
            'type'      => 'list',
            'mid_size'  => 2,
            'end_size'  => 1,
            'prev_text' => esc_html__( 'Previous', 'toolntip-core' ),
            'next_text' => esc_html__( 'Next', 'toolntip-core' ),
            'add_args'  => $add_args,
        )
    );

    if ( ! $links ) {
        return '';
    }

    return '<nav class="tnt-resource-hub__pagination" aria-label="' .
        esc_attr__( 'Resource pagination', 'toolntip-core' ) . '">' .
        $links .
        '</nav>';
}

/**
 * Keep the native Resource archive pagination envelope aligned with the Hub.
 *
 * The Hub renders its own canonical query, but WordPress resolves pretty
 * /resources/page/N/ requests through the main archive query first. Matching
 * the frozen Hub page size prevents valid Hub pages from being rejected as 404
 * because the main query used a different posts_per_page value.
 *
 * @param WP_Query $query Main query.
 * @return void
 */
function tnt_resource_hub_align_main_archive_query( $query ) {

    if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
        return;
    }

    $is_resource_archive = $query->is_post_type_archive( 'resource' );
    $is_resource_type    = $query->is_tax( 'resource_type' );

    $is_resource_topic = $query->is_tax( 'tool_category' )
        && 'resource' === $query->get( 'post_type' );

    if ( ! $is_resource_archive && ! $is_resource_type && ! $is_resource_topic ) {
        return;
    }

    $query->set( 'posts_per_page', 12 );
    $query->set( 'post_status', 'publish' );
    $query->set( 'orderby', 'date' );
    $query->set( 'order', 'DESC' );

    if ( $is_resource_archive ) {
        $search = tnt_resource_hub_search_value();

        if ( '' !== $search ) {
            $query->set( 's', $search );
        }
    }
}
add_action( 'pre_get_posts', 'tnt_resource_hub_align_main_archive_query', 20 );
