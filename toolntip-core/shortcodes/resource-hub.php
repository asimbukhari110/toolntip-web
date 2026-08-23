<?php
/**
 * Resource Hub shortcode.
 *
 * WEB-007.4 / 4.5
 *
 * Provides the /resources/ discovery surface while delegating Resource query
 * ownership to WEB-007.4 / 4.3 and card/collection presentation to 4.4.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * [tnt_resource_hub] shortcode.
 *
 * Public v1 attributes:
 * - limit
 * - orderby
 * - order
 *
 * Search state is intentionally read from the public resource_search GET
 * parameter so URLs remain bookmarkable and pagination can preserve state.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function tnt_render_resource_hub( $atts = array(), $query = null ) {

    if ( wp_style_is( 'tnt-resource-card', 'registered' ) ) {
        wp_enqueue_style( 'tnt-resource-card' );
    }

    if ( wp_style_is( 'tnt-resource-hub', 'registered' ) ) {
        wp_enqueue_style( 'tnt-resource-hub' );
    }

    $atts = shortcode_atts(
        array(
            'limit'   => 12,
            'orderby' => 'date',
            'order'   => 'DESC',
        ),
        (array) $atts,
        'tnt_resource_hub'
    );

    $search = tnt_resource_hub_search_value();
    $page   = tnt_resource_hub_current_page();

    if ( ! $query instanceof WP_Query ) {
        $query = tnt_get_resources(
            array(
                'limit'   => $atts['limit'],
                'page'    => $page,
                'search'  => $search,
                'orderby' => $atts['orderby'],
                'order'   => $atts['order'],
                'status'  => 'publish',
            )
        );
    }

    $types  = tnt_resource_hub_type_terms();
    $topics = tnt_resource_hub_topic_terms();
    $hub_url = tnt_resource_hub_url();

    $archive_term = is_tax( array( 'resource_type', 'tool_category' ) ) ? get_queried_object() : null;
    $is_taxonomy_collection = $archive_term instanceof WP_Term;
    $collection_title = '';
    $collection_kicker = '';

    if ( $is_taxonomy_collection ) {
        if ( 'resource_type' === $archive_term->taxonomy ) {
            $collection_kicker = __( 'Resource Type', 'toolntip-core' );
            $collection_title = sprintf( __( '%s Resources', 'toolntip-core' ), $archive_term->name );
        } else {
            $collection_kicker = __( 'Resource Topic', 'toolntip-core' );
            $collection_title = sprintf( __( '%s Resources', 'toolntip-core' ), $archive_term->name );
        }
    }

    ob_start();
    ?>
    <section class="tnt-resource-hub"<?php echo $is_taxonomy_collection ? '' : ' aria-labelledby="tnt-resource-hub-title"'; ?>>
        <?php if ( $is_taxonomy_collection ) : ?>
            <header class="tnt-resource-hub__collection-header">
                <p class="tnt-resource-hub__eyebrow"><?php echo esc_html( $collection_kicker ); ?></p>
                <h1 class="tnt-resource-hub__collection-title"><?php echo esc_html( $collection_title ); ?></h1>
                <p class="tnt-resource-hub__collection-back"><a href="<?php echo esc_url( $hub_url ); ?>"><?php echo esc_html__( 'Browse all Resources', 'toolntip-core' ); ?></a></p>
            </header>
        <?php else : ?>
        <header class="tnt-resource-hub__header">
            <div class="tnt-resource-hub__intro">
                <p class="tnt-resource-hub__eyebrow"><?php echo esc_html__( 'ToolNTip Resources', 'toolntip-core' ); ?></p>
                <h2 id="tnt-resource-hub-title" class="tnt-resource-hub__title">
                    <?php echo esc_html__( 'Learn, build and solve with practical resources', 'toolntip-core' ); ?>
                </h2>
                <p class="tnt-resource-hub__description">
                    <?php echo esc_html__( 'Search articles, tutorials and how-to guides, or browse by Resource Type and Topic.', 'toolntip-core' ); ?>
                </p>
            </div>

            <form class="tnt-resource-hub__search" method="get" action="<?php echo esc_url( $hub_url ); ?>" role="search">
                <label class="tnt-resource-hub__search-label" for="tnt-resource-search">
                    <?php echo esc_html__( 'Search Resources', 'toolntip-core' ); ?>
                </label>
                <div class="tnt-resource-hub__search-row">
                    <input
                        id="tnt-resource-search"
                        class="tnt-resource-hub__search-input"
                        type="search"
                        name="resource_search"
                        value="<?php echo esc_attr( $search ); ?>"
                        placeholder="<?php echo esc_attr__( 'Search resources…', 'toolntip-core' ); ?>"
                    >
                    <button class="tnt-resource-hub__search-button" type="submit">
                        <?php echo esc_html__( 'Search', 'toolntip-core' ); ?>
                    </button>
                    <?php if ( '' !== $search ) : ?>
                        <a class="tnt-resource-hub__clear" href="<?php echo esc_url( $hub_url ); ?>">
                            <?php echo esc_html__( 'Clear', 'toolntip-core' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </header>

        <div class="tnt-resource-hub__discovery">
            <?php if ( ! empty( $types ) ) : ?>
                <section class="tnt-resource-hub__discovery-group" aria-labelledby="tnt-resource-type-title">
                    <h3 id="tnt-resource-type-title" class="tnt-resource-hub__discovery-title">
                        <?php echo esc_html__( 'Browse by Type', 'toolntip-core' ); ?>
                    </h3>
                    <div class="tnt-resource-hub__chips">
                        <?php foreach ( $types as $type ) : ?>
                            <?php $type_url = get_term_link( $type ); ?>
                            <?php if ( is_wp_error( $type_url ) ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <a class="tnt-resource-hub__chip" href="<?php echo esc_url( $type_url ); ?>">
                                <?php echo esc_html( $type->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( ! empty( $topics ) ) : ?>
                <section class="tnt-resource-hub__discovery-group" aria-labelledby="tnt-resource-topic-title">
                    <h3 id="tnt-resource-topic-title" class="tnt-resource-hub__discovery-title">
                        <?php echo esc_html__( 'Browse by Topic', 'toolntip-core' ); ?>
                    </h3>
                    <div class="tnt-resource-hub__chips">
                        <?php foreach ( $topics as $topic ) : ?>
                            <?php $topic_url = tnt_resource_hub_topic_url( $topic ); ?>
                            <?php if ( '' === $topic_url ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <a class="tnt-resource-hub__chip" href="<?php echo esc_url( $topic_url ); ?>">
                                <?php echo esc_html( $topic->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="tnt-resource-hub__results">
            <div class="tnt-resource-hub__results-header">
                <div>
                    <p class="tnt-resource-hub__results-kicker">
                        <?php echo $is_taxonomy_collection ? esc_html__( 'Browse Resources', 'toolntip-core' ) : ( '' !== $search ? esc_html__( 'Search results', 'toolntip-core' ) : esc_html__( 'Latest resources', 'toolntip-core' ) ); ?>
                    </p>
                    <h3 class="tnt-resource-hub__results-title">
                        <?php if ( $is_taxonomy_collection ) : ?>
                            <?php echo esc_html( $collection_title ); ?>
                        <?php elseif ( '' !== $search ) : ?>
                            <?php
                            printf(
                                /* translators: %s: Resource search phrase. */
                                esc_html__( 'Results for “%s”', 'toolntip-core' ),
                                esc_html( $search )
                            );
                            ?>
                        <?php else : ?>
                            <?php echo esc_html__( 'Explore Resources', 'toolntip-core' ); ?>
                        <?php endif; ?>
                    </h3>
                </div>

                <p class="tnt-resource-hub__count">
                    <?php
                    printf(
                        /* translators: %s: number of Resources found. */
                        esc_html( _n( '%s Resource', '%s Resources', (int) $query->found_posts, 'toolntip-core' ) ),
                        esc_html( number_format_i18n( (int) $query->found_posts ) )
                    );
                    ?>
                </p>
            </div>

            <?php echo tnt_render_resource_collection( $query, array( 'monetization' => true, 'columns' => 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php echo tnt_resource_hub_pagination( $query, $search ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </section>
    <?php

    return ob_get_clean();
}
/**
 * [tnt_resource_hub] shortcode wrapper.
 *
 * Embedded use keeps the bounded 4.3 query path. The native /resources/
 * archive passes WordPress' already-executed main query directly to the same
 * renderer, preventing a duplicate Resource collection query.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function tnt_resource_hub_shortcode( $atts = array() ) {
    return tnt_render_resource_hub( $atts );
}

add_shortcode( 'tnt_resource_hub', 'tnt_resource_hub_shortcode' );