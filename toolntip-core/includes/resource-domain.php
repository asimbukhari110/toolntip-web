<?php
/**
 * Resource Domain Foundation.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resource domain foundation schema version.
 *
 * Increment only when registration defaults or rewrite contracts require
 * one-time installation work.
 */
const TNT_RESOURCE_DOMAIN_VERSION = '1.3';


/**
 * Register canonical Resource taxonomy rewrite routes.
 *
 * WordPress exposes the taxonomy query vars correctly, but some hosting/plugin
 * rewrite stacks can fail to persist nested taxonomy permastructs beneath the
 * Resource CPT base. Explicit top-priority rules keep the frozen public URL
 * contract deterministic without changing taxonomy ownership or term data.
 */
function tnt_register_resource_taxonomy_rewrite_rules() {

    // Base routes are added first; pagination routes are added afterwards so
    // they receive higher priority within WordPress' "top" rewrite rules.
    add_rewrite_rule(
        '^resources/type/(.+?)/?$',
        'index.php?resource_type=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^resources/type/(.+?)/page/([0-9]{1,})/?$',
        'index.php?resource_type=$matches[1]&paged=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^resources/topic/(.+?)/?$',
        'index.php?tool_category=$matches[1]&post_type=resource',
        'top'
    );

    add_rewrite_rule(
        '^resources/topic/(.+?)/page/([0-9]{1,})/?$',
        'index.php?tool_category=$matches[1]&post_type=resource&paged=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^resources/tag/([^/]+)/?$',
        'index.php?resource_tag=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^resources/tag/([^/]+)/page/([0-9]{1,})/?$',
        'index.php?resource_tag=$matches[1]&paged=$matches[2]',
        'top'
    );
}

add_action( 'init', 'tnt_register_resource_taxonomy_rewrite_rules', 20 );

/**
 * Ensure the initial controlled Resource Type vocabulary exists.
 */
function tnt_seed_resource_types() {

    $types = array(
        'article'      => __( 'Article', 'toolntip-core' ),
        'tutorial'     => __( 'Tutorial', 'toolntip-core' ),
        'how-to-guide' => __( 'How-To Guide', 'toolntip-core' ),
    );

    foreach ( $types as $slug => $name ) {
        if ( ! term_exists( $slug, 'resource_type' ) ) {
            wp_insert_term(
                $name,
                'resource_type',
                array( 'slug' => $slug )
            );
        }
    }
}


/**
 * Migrate legacy Resource Topics into the canonical shared tool_category taxonomy.
 *
 * The migration is additive and idempotent:
 * - existing canonical terms are reused by slug;
 * - missing canonical terms are created;
 * - Resource assignments are merged, never blindly replaced;
 * - legacy resource_topic terms/relationships remain intact for rollback.
 *
 * @return void
 */
function tnt_migrate_resource_topics_to_shared_categories() {

    $legacy_terms = get_terms(
        array(
            'taxonomy'   => 'resource_topic',
            'hide_empty' => false,
        )
    );

    if ( is_wp_error( $legacy_terms ) || empty( $legacy_terms ) ) {
        return;
    }

    $term_map = array();

    // Pass 1: create/reuse every canonical term without parents.
    foreach ( $legacy_terms as $legacy_term ) {

        $canonical = get_term_by( 'slug', $legacy_term->slug, 'tool_category' );

        if ( ! $canonical ) {
            $created = wp_insert_term(
                $legacy_term->name,
                'tool_category',
                array(
                    'slug'        => $legacy_term->slug,
                    'description' => $legacy_term->description,
                )
            );

            if ( is_wp_error( $created ) ) {
                continue;
            }

            $canonical_id = absint( $created['term_id'] );
        } else {
            $canonical_id = absint( $canonical->term_id );
        }

        $term_map[ absint( $legacy_term->term_id ) ] = $canonical_id;
    }

    // Pass 2: preserve hierarchy where the parent was migrated successfully.
    foreach ( $legacy_terms as $legacy_term ) {

        $legacy_id = absint( $legacy_term->term_id );

        if ( empty( $term_map[ $legacy_id ] ) ) {
            continue;
        }

        $parent_id = 0;

        if (
            $legacy_term->parent
            && ! empty( $term_map[ absint( $legacy_term->parent ) ] )
        ) {
            $parent_id = absint( $term_map[ absint( $legacy_term->parent ) ] );
        }

        wp_update_term(
            $term_map[ $legacy_id ],
            'tool_category',
            array( 'parent' => $parent_id )
        );
    }

    // Pass 3: merge legacy assignments into canonical Resource assignments.
    $resource_ids = get_posts(
        array(
            'post_type'      => 'resource',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );

    foreach ( $resource_ids as $resource_id ) {

        $legacy_ids = wp_get_object_terms(
            $resource_id,
            'resource_topic',
            array( 'fields' => 'ids' )
        );

        if ( is_wp_error( $legacy_ids ) || empty( $legacy_ids ) ) {
            continue;
        }

        $canonical_ids = wp_get_object_terms(
            $resource_id,
            'tool_category',
            array( 'fields' => 'ids' )
        );

        if ( is_wp_error( $canonical_ids ) ) {
            $canonical_ids = array();
        }

        foreach ( $legacy_ids as $legacy_id ) {
            if ( ! empty( $term_map[ absint( $legacy_id ) ] ) ) {
                $canonical_ids[] = absint( $term_map[ absint( $legacy_id ) ] );
            }
        }

        $canonical_ids = array_values(
            array_unique(
                array_filter(
                    array_map( 'absint', $canonical_ids )
                )
            )
        );

        wp_set_object_terms( $resource_id, $canonical_ids, 'tool_category', false );
    }
}

/**
 * Resource Topics use WordPress's native tool_category taxonomy UI/persistence.
 *
 * Gutenberg saves native taxonomy relationships through REST. Keeping one
 * canonical native save path prevents later REST updates from clearing a
 * custom metabox selection.
 */



/**
 * Perform one-time Resource domain installation work after an update.
 *
 * CPT/taxonomy registrations are already attached to init. This callback runs
 * later on the same hook so rewrite rules contain the canonical Resource routes.
 */
function tnt_maybe_install_resource_domain() {

    $installed_version = (string) get_option( 'tnt_resource_domain_version', '' );

    if ( TNT_RESOURCE_DOMAIN_VERSION === $installed_version ) {
        return;
    }

    tnt_seed_resource_types();
    tnt_migrate_resource_topics_to_shared_categories();
    flush_rewrite_rules( false );
    update_option( 'tnt_resource_domain_version', TNT_RESOURCE_DOMAIN_VERSION, false );
}

add_action( 'init', 'tnt_maybe_install_resource_domain', 99 );
