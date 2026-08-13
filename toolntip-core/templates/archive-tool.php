<?php
/**
 * Tool Archive Template.
 *
 * Canonical discovery surface for the native Tool post type archive.
 * Reuses the normalized Tool data contract and existing Tool Card component.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

global $wp_query;

$result_count = isset( $wp_query->found_posts )
    ? (int) $wp_query->found_posts
    : 0;

$search_term   = function_exists( 'tnt_get_tool_directory_search_term' )
    ? tnt_get_tool_directory_search_term()
    : '';
$is_searching  = '' !== $search_term;
$archive_url   = get_post_type_archive_link( 'tool' );
$filters       = function_exists( 'tnt_get_tool_directory_filters' )
    ? tnt_get_tool_directory_filters()
    : array( 'category' => '', 'type' => '', 'pricing' => '', 'featured' => '', 'sort' => '' );
$categories    = function_exists( 'tnt_get_tool_directory_categories' )
    ? tnt_get_tool_directory_categories()
    : array();
$type_choices  = function_exists( 'tnt_get_tool_directory_field_choices' )
    ? tnt_get_tool_directory_field_choices( 'tool_type' )
    : array();
$price_choices = function_exists( 'tnt_get_tool_directory_field_choices' )
    ? tnt_get_tool_directory_field_choices( 'pricing' )
    : array();
$has_featured  = function_exists( 'tnt_tool_directory_has_featured_tools' )
    ? tnt_tool_directory_has_featured_tools()
    : false;
$has_filters   = '' !== $filters['category'] || '' !== $filters['type'] || '' !== $filters['pricing'] || '1' === $filters['featured'];
$has_state     = $is_searching || $has_filters || '' !== $filters['sort'];
?>

<main class="tnt-tool-directory" id="main">

    <div class="tnt-tool-directory__inner">

        <header class="tnt-tool-directory__hero">

            <p class="tnt-tool-directory__eyebrow">
                <?php echo esc_html__( 'ToolNTip Library', 'toolntip-core' ); ?>
            </p>

            <h1 class="tnt-tool-directory__title">
                <?php echo esc_html__( 'All Tools', 'toolntip-core' ); ?>
            </h1>

            <p class="tnt-tool-directory__intro">
                <?php echo esc_html__( 'Discover practical tools for development, productivity, design, AI, and modern digital workflows.', 'toolntip-core' ); ?>
            </p>

        </header>

        <section class="tnt-tool-directory__search" aria-label="<?php echo esc_attr__( 'Search and filter tools', 'toolntip-core' ); ?>">

            <form class="tnt-tool-directory__search-form" method="get" action="<?php echo esc_url( $archive_url ); ?>" role="search">

                <label class="screen-reader-text" for="tnt-tool-search">
                    <?php echo esc_html__( 'Search the ToolNTip tool directory', 'toolntip-core' ); ?>
                </label>

                <div class="tnt-tool-directory__search-field">
                    <span class="tnt-tool-directory__search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>
                    </span>

                    <input
                        id="tnt-tool-search"
                        class="tnt-tool-directory__search-input"
                        type="search"
                        name="tool_search"
                        value="<?php echo esc_attr( $search_term ); ?>"
                        placeholder="<?php echo esc_attr__( 'Search tools by name, purpose, or keyword...', 'toolntip-core' ); ?>"
                        autocomplete="off"
                    >
                </div>

                <button class="tnt-tool-directory__search-submit" type="submit">
                    <?php echo esc_html__( 'Apply', 'toolntip-core' ); ?>
                </button>

                <?php if ( $has_state ) : ?>
                    <a class="tnt-tool-directory__search-clear" href="<?php echo esc_url( $archive_url ); ?>">
                        <?php echo esc_html__( 'Reset', 'toolntip-core' ); ?>
                    </a>
                <?php endif; ?>

                <?php if ( ! empty( $categories ) || ! empty( $type_choices ) || ! empty( $price_choices ) || $has_featured ) : ?>
                    <div class="tnt-tool-directory__filters">

                        <?php if ( ! empty( $categories ) ) : ?>
                            <label class="tnt-tool-directory__filter">
                                <span><?php echo esc_html__( 'Category', 'toolntip-core' ); ?></span>
                                <select name="category">
                                    <option value=""><?php echo esc_html__( 'All categories', 'toolntip-core' ); ?></option>
                                    <?php foreach ( $categories as $category ) : ?>
                                        <option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $filters['category'], $category->slug ); ?>>
                                            <?php echo esc_html( $category->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>

                        <?php if ( ! empty( $type_choices ) ) : ?>
                            <label class="tnt-tool-directory__filter">
                                <span><?php echo esc_html__( 'Tool Type', 'toolntip-core' ); ?></span>
                                <select name="type">
                                    <option value=""><?php echo esc_html__( 'All types', 'toolntip-core' ); ?></option>
                                    <?php foreach ( $type_choices as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['type'], (string) $value ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>

                        <?php if ( ! empty( $price_choices ) ) : ?>
                            <label class="tnt-tool-directory__filter">
                                <span><?php echo esc_html__( 'Pricing', 'toolntip-core' ); ?></span>
                                <select name="pricing">
                                    <option value=""><?php echo esc_html__( 'All pricing', 'toolntip-core' ); ?></option>
                                    <?php foreach ( $price_choices as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['pricing'], (string) $value ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>

                        <?php if ( $has_featured ) : ?>
                            <label class="tnt-tool-directory__featured-filter">
                                <input type="checkbox" name="featured" value="1" <?php checked( $filters['featured'], '1' ); ?>>
                                <span><?php echo esc_html__( 'Featured only', 'toolntip-core' ); ?></span>
                            </label>
                        <?php endif; ?>

                        <label class="tnt-tool-directory__filter tnt-tool-directory__filter--sort">
                            <span><?php echo esc_html__( 'Sort by', 'toolntip-core' ); ?></span>
                            <select name="sort">
                                <?php if ( $is_searching ) : ?>
                                    <option value="relevance" <?php selected( $filters['sort'], 'relevance' ); ?>><?php echo esc_html__( 'Relevance', 'toolntip-core' ); ?></option>
                                <?php endif; ?>
                                <option value="newest" <?php selected( $filters['sort'], 'newest' ); ?>><?php echo esc_html__( 'Newest', 'toolntip-core' ); ?></option>
                                <option value="rating" <?php selected( $filters['sort'], 'rating' ); ?>><?php echo esc_html__( 'Rating', 'toolntip-core' ); ?></option>
                                <option value="name" <?php selected( $filters['sort'], 'name' ); ?>><?php echo esc_html__( 'Name A–Z', 'toolntip-core' ); ?></option>
                            </select>
                        </label>

                    </div>
                <?php endif; ?>

            </form>

        </section>

        <div class="tnt-tool-directory__summary" aria-live="polite">

            <?php if ( $is_searching ) : ?>
                <span class="tnt-tool-directory__summary-context">
                    <?php
                    printf(
                        /* translators: %s: active Tool Directory search term. */
                        esc_html__( 'Results for “%s”', 'toolntip-core' ),
                        esc_html( $search_term )
                    );
                    ?>
                </span>
            <?php endif; ?>

            <span class="tnt-tool-directory__count">
                <?php
                printf(
                    /* translators: %s: number of published tools. */
                    esc_html( _n( '%s tool', '%s tools', $result_count, 'toolntip-core' ) ),
                    esc_html( number_format_i18n( $result_count ) )
                );
                ?>
            </span>

        </div>

        <?php if ( have_posts() ) : ?>

            <div class="tnt-tool-directory__grid">

                <?php while ( have_posts() ) : ?>

                    <?php
                    the_post();

                    $tool = tnt_get_tool_data( get_post() );

                    if ( ! $tool ) {
                        continue;
                    }
                    ?>

                    <?php tnt_render( 'tool-card', $tool ); ?>

                <?php endwhile; ?>

            </div>

            <?php if ( $wp_query->max_num_pages > 1 ) : ?>

                <nav
                    class="tnt-tool-directory__pagination"
                    aria-label="<?php echo esc_attr__( 'Tool directory pagination', 'toolntip-core' ); ?>"
                >
                    <?php
                    $pagination_args = array(
                        'current'   => max( 1, get_query_var( 'paged' ) ),
                        'total'     => (int) $wp_query->max_num_pages,
                        'mid_size'  => 1,
                        'end_size'  => 1,
                        'prev_text' => esc_html__( 'Previous', 'toolntip-core' ),
                        'next_text' => esc_html__( 'Next', 'toolntip-core' ),
                        'type'      => 'list',
                    );

                    $pagination_state = array_filter(
                        array(
                            'tool_search' => $search_term,
                            'category'    => $filters['category'],
                            'type'        => $filters['type'],
                            'pricing'     => $filters['pricing'],
                            'featured'    => $filters['featured'],
                            'sort'        => $filters['sort'],
                        ),
                        static function ( $value ) {
                            return '' !== $value;
                        }
                    );

                    if ( ! empty( $pagination_state ) ) {
                        $pagination_args['add_args'] = $pagination_state;
                    }

                    echo wp_kses_post( paginate_links( $pagination_args ) );
                    ?>
                </nav>

            <?php endif; ?>

        <?php else : ?>

            <section class="tnt-tool-directory__empty">

                <h2>
                    <?php
                    echo esc_html(
                        $has_state
                            ? __( 'No matching tools found', 'toolntip-core' )
                            : __( 'No tools found', 'toolntip-core' )
                    );
                    ?>
                </h2>

                <p>
                    <?php if ( $has_state ) : ?>
                        <?php echo esc_html__( 'No tools match the current search or filter combination. Adjust the criteria or reset the directory.', 'toolntip-core' ); ?>
                    <?php else : ?>
                        <?php echo esc_html__( 'There are no published tools available in the directory yet.', 'toolntip-core' ); ?>
                    <?php endif; ?>
                </p>

                <?php if ( $has_state ) : ?>
                    <a class="tnt-tool-directory__empty-clear" href="<?php echo esc_url( $archive_url ); ?>">
                        <?php echo esc_html__( 'View all tools', 'toolntip-core' ); ?>
                    </a>
                <?php endif; ?>

            </section>

        <?php endif; ?>

    </div>

</main>

<?php
get_footer();
