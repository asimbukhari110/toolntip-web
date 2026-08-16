<?php
/**
 * Tool collection and interactive archive shortcodes.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the current shortcode page URL without Tool filter state.
 *
 * @return string
 */
function tnt_tool_archive_shortcode_action_url() {

    $queried_id = get_queried_object_id();

    if ( $queried_id ) {
        $url = get_permalink( $queried_id );
        if ( $url ) {
            return $url;
        }
    }

    $archive_url = get_post_type_archive_link( 'tool' );

    return $archive_url ? $archive_url : home_url( '/' );
}

/**
 * Render a focused Tool card grid from a WP_Query.
 *
 * @param WP_Query $query   Tool query.
 * @param int      $columns Column count.
 * @param string   $layout  grid|compact.
 * @return string
 */
function tnt_render_tool_shortcode_grid( $query, $columns, $layout = 'grid' ) {

    if ( ! $query instanceof WP_Query || ! $query->have_posts() ) {
        return '<div class="tnt-empty">' . esc_html__( 'No tools found.', 'toolntip-core' ) . '</div>';
    }

    $classes = array(
        'tnt-grid',
        'tnt-grid--columns-' . absint( $columns ),
    );

    if ( 'compact' === $layout ) {
        $classes[] = 'tnt-grid--compact';
    }

    ob_start();
    ?>
    <div
        class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
        data-tnt-columns="<?php echo esc_attr( absint( $columns ) ); ?>"
        data-tnt-layout="<?php echo esc_attr( $layout ); ?>"
    >
        <?php
        while ( $query->have_posts() ) {
            $query->the_post();

            $tool = tnt_get_tool_card_data( get_post() );

            if ( ! $tool ) {
                continue;
            }
            ?>
            <div class="tnt-card" data-tnt-tool-id="<?php echo esc_attr( $tool['post_id'] ); ?>">
                <?php tnt_render( 'tool-card', $tool ); ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Render the interactive archive controls.
 *
 * @param array $state        Active filter state.
 * @param bool  $show_search  Show search field.
 * @param bool  $show_filters Show filter controls.
 * @param bool  $show_sorting Show sort control.
 * @return string
 */
function tnt_render_tool_archive_shortcode_controls( $state, $show_search, $show_filters, $show_sorting ) {

    if ( ! $show_search && ! $show_filters && ! $show_sorting ) {
        return '';
    }

    $categories    = $show_filters && function_exists( 'tnt_get_tool_directory_categories' )
        ? tnt_get_tool_directory_categories()
        : array();
    $type_choices  = $show_filters && function_exists( 'tnt_get_tool_directory_field_choices' )
        ? tnt_get_tool_directory_field_choices( 'tool_type' )
        : array();
    $price_choices = $show_filters && function_exists( 'tnt_get_tool_directory_field_choices' )
        ? tnt_get_tool_directory_field_choices( 'pricing' )
        : array();

    $active_categories = tnt_tool_query_parse_list( $state['category'] ?? '' );
    $active_category   = 1 === count( $active_categories ) ? $active_categories[0] : '';

    ob_start();
    ?>
    <form class="tnt-archive-controls" method="get" action="<?php echo esc_url( tnt_tool_archive_shortcode_action_url() ); ?>">

        <?php if ( $show_search ) : ?>
            <label class="tnt-field tnt-field--search">
                <span class="screen-reader-text"><?php echo esc_html__( 'Search tools', 'toolntip-core' ); ?></span>
                <input
                    type="search"
                    name="tool_search"
                    value="<?php echo esc_attr( $state['tool_search'] ?? '' ); ?>"
                    placeholder="<?php echo esc_attr__( 'Search tools...', 'toolntip-core' ); ?>"
                >
            </label>
        <?php elseif ( '' !== ( $state['tool_search'] ?? '' ) ) : ?>
            <input type="hidden" name="tool_search" value="<?php echo esc_attr( $state['tool_search'] ); ?>">
        <?php endif; ?>

        <?php if ( $show_filters ) : ?>
            <label class="tnt-field">
                <span><?php echo esc_html__( 'Category', 'toolntip-core' ); ?></span>
                <select name="category">
                    <option value=""><?php echo esc_html__( 'All categories', 'toolntip-core' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $active_category, $category->slug ); ?>>
                            <?php echo esc_html( $category->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="tnt-field">
                <span><?php echo esc_html__( 'Tool Type', 'toolntip-core' ); ?></span>
                <select name="type">
                    <option value=""><?php echo esc_html__( 'All types', 'toolntip-core' ); ?></option>
                    <?php foreach ( $type_choices as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $state['type'] ?? '', (string) $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="tnt-field">
                <span><?php echo esc_html__( 'Pricing', 'toolntip-core' ); ?></span>
                <select name="pricing">
                    <option value=""><?php echo esc_html__( 'All pricing', 'toolntip-core' ); ?></option>
                    <?php foreach ( $price_choices as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $state['pricing'] ?? '', (string) $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <input type="hidden" name="featured" value="0">
            <label class="tnt-field tnt-field--checkbox">
                <input type="checkbox" name="featured" value="1" <?php checked( $state['featured'] ?? '', '1' ); ?>>
                <span><?php echo esc_html__( 'Featured only', 'toolntip-core' ); ?></span>
            </label>
        <?php else : ?>
            <?php foreach ( array( 'category', 'type', 'pricing', 'featured' ) as $hidden_key ) : ?>
                <?php if ( '' !== ( $state[ $hidden_key ] ?? '' ) ) : ?>
                    <input type="hidden" name="<?php echo esc_attr( $hidden_key ); ?>" value="<?php echo esc_attr( $state[ $hidden_key ] ); ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ( $show_sorting ) : ?>
            <label class="tnt-field">
                <span><?php echo esc_html__( 'Sort by', 'toolntip-core' ); ?></span>
                <select name="sort">
                    <?php if ( '' !== ( $state['tool_search'] ?? '' ) ) : ?>
                        <option value="relevance" <?php selected( $state['sort'] ?? '', 'relevance' ); ?>><?php echo esc_html__( 'Relevance', 'toolntip-core' ); ?></option>
                    <?php endif; ?>
                    <option value="newest" <?php selected( $state['sort'] ?? '', 'newest' ); ?>><?php echo esc_html__( 'Newest', 'toolntip-core' ); ?></option>
                    <option value="rating" <?php selected( $state['sort'] ?? '', 'rating' ); ?>><?php echo esc_html__( 'Rating', 'toolntip-core' ); ?></option>
                    <option value="name" <?php selected( $state['sort'] ?? '', 'name' ); ?>><?php echo esc_html__( 'Name A–Z', 'toolntip-core' ); ?></option>
                </select>
            </label>
        <?php elseif ( '' !== ( $state['sort'] ?? '' ) ) : ?>
            <input type="hidden" name="sort" value="<?php echo esc_attr( $state['sort'] ); ?>">
        <?php endif; ?>

        <button class="tnt-action" type="submit"><?php echo esc_html__( 'Apply', 'toolntip-core' ); ?></button>
        <a class="tnt-action tnt-action--secondary" href="<?php echo esc_url( tnt_tool_archive_shortcode_action_url() ); ?>">
            <?php echo esc_html__( 'Reset', 'toolntip-core' ); ?>
        </a>
    </form>
    <?php

    return ob_get_clean();
}

/**
 * Master interactive Tool archive shortcode.
 *
 * GET filter values take precedence over shortcode filter fallbacks.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tnt_tool_archive_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'posts_per_page' => 16,
            'columns'        => 4,
            'show_search'    => 'true',
            'show_filters'   => 'true',
            'show_sorting'   => 'true',
            'layout'         => 'grid',

            // Optional initial-state fallbacks; URL GET values override them.
            'tool_search'    => '',
            'category'       => '',
            'type'           => '',
            'pricing'        => '',
            'featured'       => '',
            'sort'           => '',
        ),
        $atts,
        'tnt_tool_archive'
    );

    $posts_per_page = max( 1, min( 100, absint( $atts['posts_per_page'] ) ) );
    $columns        = tnt_tool_query_normalize_columns( $atts['columns'], array( 2, 3, 4, 6 ), 4 );
    $layout         = sanitize_key( $atts['layout'] );
    $layout         = in_array( $layout, array( 'grid', 'compact' ), true ) ? $layout : 'grid';
    $show_search    = tnt_tool_query_parse_bool( $atts['show_search'], true );
    $show_filters   = tnt_tool_query_parse_bool( $atts['show_filters'], true );
    $show_sorting   = tnt_tool_query_parse_bool( $atts['show_sorting'], true );
    $state          = tnt_get_tool_shortcode_archive_state( $atts );

    if ( '' === $state['sort'] ) {
        $state['sort'] = '' !== $state['tool_search'] ? 'relevance' : 'newest';
    }

    $paged = max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) );
    $query = new WP_Query(
        tnt_build_tool_archive_shortcode_query_args( $state, $posts_per_page, $paged )
    );

    tnt_enqueue_tool_collection_assets();

    $container_classes = array( 'tnt-archive-container' );
    if ( 'compact' === $layout ) {
        $container_classes[] = 'tnt-archive-container--compact';
    }

    ob_start();
    ?>
    <section
        class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>"
        data-tnt-component="tool-archive"
        data-tnt-layout="<?php echo esc_attr( $layout ); ?>"
    >
        <?php echo tnt_render_tool_archive_shortcode_controls( $state, $show_search, $show_filters, $show_sorting ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <div class="tnt-archive-summary" aria-live="polite">
            <?php
            printf(
                /* translators: %s: Tool result count. */
                esc_html( _n( '%s tool', '%s tools', (int) $query->found_posts, 'toolntip-core' ) ),
                esc_html( number_format_i18n( (int) $query->found_posts ) )
            );
            ?>
        </div>

        <?php echo tnt_render_tool_shortcode_grid( $query, $columns, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php if ( $query->max_num_pages > 1 ) : ?>
            <nav class="tnt-pagination" aria-label="<?php echo esc_attr__( 'Tool pagination', 'toolntip-core' ); ?>">
                <?php
                $add_args = array_filter(
                    array(
                        'tool_search' => $state['tool_search'],
                        'category'    => $state['category'],
                        'type'        => $state['type'],
                        'pricing'     => $state['pricing'],
                        'featured'    => $state['featured'],
                        'sort'        => $state['sort'],
                    ),
                    static function ( $value ) {
                        return '' !== (string) $value;
                    }
                );

                echo wp_kses_post(
                    paginate_links(
                        array(
                            'current'   => $paged,
                            'total'     => (int) $query->max_num_pages,
                            'mid_size'  => 1,
                            'end_size'  => 1,
                            'prev_text' => esc_html__( 'Previous', 'toolntip-core' ),
                            'next_text' => esc_html__( 'Next', 'toolntip-core' ),
                            'type'      => 'list',
                            'add_args'  => $add_args,
                        )
                    )
                );
                ?>
            </nav>
        <?php endif; ?>
    </section>
    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Curated Tool collection shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tnt_tools_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'category' => '',
            'type'     => '',
            'pricing'  => '',
            'featured' => '',
            'tag'      => '',
            'limit'    => 8,
            'columns'  => 4,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ),
        $atts,
        'tnt_tools'
    );

    $columns = tnt_tool_query_normalize_columns( $atts['columns'], array( 2, 3, 4 ), 4 );
    $query   = new WP_Query( tnt_build_tools_shortcode_query_args( $atts ) );

    tnt_enqueue_tool_collection_assets();

    ob_start();
    ?>
    <section
        class="tnt-archive-container tnt-archive-container--curated"
        data-tnt-component="tool-collection"
    >
        <?php echo tnt_render_tool_shortcode_grid( $query, $columns, 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </section>
    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode( 'tnt_tool_archive', 'tnt_tool_archive_shortcode' );
add_shortcode( 'tnt_tools', 'tnt_tools_shortcode' );
