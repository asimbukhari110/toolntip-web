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

		<div class="tnt-tool-directory__summary" aria-live="polite">

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
                    echo wp_kses_post(
                        paginate_links(
                            array(
                                'current'   => max( 1, get_query_var( 'paged' ) ),
                                'total'     => (int) $wp_query->max_num_pages,
                                'mid_size'  => 1,
                                'end_size'  => 1,
                                'prev_text' => esc_html__( 'Previous', 'toolntip-core' ),
                                'next_text' => esc_html__( 'Next', 'toolntip-core' ),
                                'type'      => 'list',
                            )
                        )
                    );
                    ?>
                </nav>

            <?php endif; ?>

        <?php else : ?>

            <section class="tnt-tool-directory__empty">

                <h2>
                    <?php echo esc_html__( 'No tools found', 'toolntip-core' ); ?>
                </h2>

                <p>
                    <?php echo esc_html__( 'There are no published tools available in the directory yet.', 'toolntip-core' ); ?>
                </p>

            </section>

        <?php endif; ?>

    </div>

</main>

<?php
get_footer();
