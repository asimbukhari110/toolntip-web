<?php
/**
 * Tool Detail Page.
 *
 * Orchestrates the complete Tool Detail experience.
 * Presentation components consume the normalized $tool data contract.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<article class="tnt-tool-detail">

    <header class="tnt-tool-detail__hero">

        <?php tnt_render( 'hero', $tool ); ?>

    </header>

    <nav
        class="tnt-tool-detail__nav"
        aria-label="<?php echo esc_attr__( 'Tool page sections', 'toolntip-core' ); ?>"
    >

        <?php tnt_render( 'tool-detail-nav', $tool ); ?>

    </nav>

    <div class="tnt-tool-detail__layout">

        <main class="tnt-tool-detail__main">

            <section
                id="overview"
                class="tnt-tool-detail__section tnt-tool-detail__section--overview"
            >

                <?php tnt_render( 'about', $tool ); ?>

                <?php tnt_render( 'features', $tool ); ?>

            </section>

            <section class="tnt-tool-detail__section tnt-tool-detail__section--media">

                <?php tnt_render( 'screenshots', $tool ); ?>

                <?php tnt_render( 'video', $tool ); ?>

            </section>

            <section class="tnt-tool-detail__section tnt-tool-detail__section--evaluation">

                <?php tnt_render( 'pros-cons', $tool ); ?>

                <?php tnt_render( 'faq', $tool ); ?>

            </section>

			<section class="tnt-tool-detail__section tnt-tool-detail__section--reviews">

				<?php tnt_render( 'reviews', $tool ); ?>

			</section>

        </main>

        <aside
            class="tnt-tool-detail__sidebar"
            aria-label="<?php echo esc_attr__( 'Tool information and actions', 'toolntip-core' ); ?>"
        >

            <div class="tnt-tool-detail__sidebar-inner">

                <?php tnt_render( 'tool-information', $tool ); ?>

                <?php tnt_render( 'tool-quick-actions', $tool ); ?>

				<?php tnt_render( 'similar-tools', $tool ); ?>
            </div>

        </aside>

    </div>

    <?php tnt_render( 'schema', $tool ); ?>

</article>
