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

        <section class="tnt-tool-directory__search" aria-label="<?php echo esc_attr__( 'Search tools', 'toolntip-core' ); ?>">

            <form class="tnt-tool-directory__search-form" method="get" action="<?php echo esc_url( $archive_url ); ?>" role="search">

                <label class="screen-reader-text" for="tnt-tool-search">
                    <?php echo esc_html__( 'Search the ToolNTip tool directory', 'toolntip-core' ); ?>
                </label>

                <div class="tnt-tool-directory__search-field">

				<span class="tnt-tool-directory__search-icon" aria-hidden="true">
					<svg
						viewBox="0 0 24 24"
						width="20"
						height="20"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						focusable="false"
					>
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
                    <?php echo esc_html__( 'Search', 'toolntip-core' ); ?>
                </button>

                <?php if ( $is_searching ) : ?>
                    <a class="tnt-tool-directory__search-clear" href="<?php echo esc_url( $archive_url ); ?>">
                        <?php echo esc_html__( 'Clear', 'toolntip-core' ); ?>
                    </a>
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

                    if ( $is_searching ) {
                        $pagination_args['add_args'] = array(
                            'tool_search' => $search_term,
                        );
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
                        $is_searching
                            ? __( 'No matching tools found', 'toolntip-core' )
                            : __( 'No tools found', 'toolntip-core' )
                    );
                    ?>
                </h2>

                <p>
                    <?php if ( $is_searching ) : ?>
                        <?php
                        printf(
                            /* translators: %s: active Tool Directory search term. */
                            esc_html__( 'We could not find any tools matching “%s”. Try another keyword or clear the search.', 'toolntip-core' ),
                            esc_html( $search_term )
                        );
                        ?>
                    <?php else : ?>
                        <?php echo esc_html__( 'There are no published tools available in the directory yet.', 'toolntip-core' ); ?>
                    <?php endif; ?>
                </p>

                <?php if ( $is_searching ) : ?>
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
