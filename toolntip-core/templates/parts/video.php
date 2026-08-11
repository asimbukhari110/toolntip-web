<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['video'] ) ) {
    return;
}
?>

<div id="video" class="tnt-tool-detail-anchor">

    <section class="tnt-tool-video">

        <header class="tnt-content-panel__header">

            <span
                class="tnt-content-panel__icon"
                aria-hidden="true"
            >
                &#9654;
            </span>

            <h2 class="tnt-section-title">
                <?php echo esc_html__( 'Video Overview', 'toolntip-core' ); ?>
            </h2>

        </header>

        <div class="tnt-tool-video__media">

            <?php if ( ! empty( $tool['video']['embed'] ) ) : ?>

                <?php echo $tool['video']['embed']; ?>

            <?php else : ?>

                <a
                    href="<?php echo esc_url( $tool['video']['url'] ); ?>"
                    class="tnt-tool-video__fallback"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php echo esc_html__( 'Watch Video Overview', 'toolntip-core' ); ?>
                </a>

            <?php endif; ?>

        </div>

    </section>

</div>
