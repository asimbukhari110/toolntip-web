<?php
/**
 * Tool Quick Actions.
 *
 * Displays available Tool actions in the Tool Detail sidebar.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$button = $tool['use_tool'] ?? array();
?>

<section class="tnt-tool-quick-actions">

    <header class="tnt-tool-quick-actions__header">

        <span
            class="tnt-tool-quick-actions__icon"
            aria-hidden="true"
        >
            &#9889;
        </span>

        <h2 class="tnt-tool-quick-actions__title">
            <?php echo esc_html__( 'Quick Actions', 'toolntip-core' ); ?>
        </h2>

    </header>

    <div class="tnt-tool-quick-actions__list">

        <?php if ( ! empty( $button ) ) : ?>

            <?php if ( ! empty( $button['disabled'] ) ) : ?>

                <span
                    class="tnt-btn tnt-btn-primary is-disabled"
                    aria-disabled="true"
                >
                    <span class="tnt-tool-action__label">
                        <?php echo esc_html( $button['label'] ); ?>
                    </span>
                </span>

            <?php else : ?>

                <a
                    href="<?php echo esc_url( $button['url'] ); ?>"
                    class="tnt-btn tnt-btn-primary"
                    <?php echo ! empty( $button['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                >
                    <span class="tnt-tool-action__icon" aria-hidden="true">
                        &#9889;
                    </span>

                    <span class="tnt-tool-action__label">
                        <?php echo esc_html( $button['label'] ); ?>
                    </span>
                </a>

            <?php endif; ?>

        <?php endif; ?>

        <?php if ( ! empty( $tool['official_website'] ) ) : ?>

            <a
                href="<?php echo esc_url( $tool['official_website'] ); ?>"
                class="tnt-btn tnt-btn-secondary"
                target="_blank"
                rel="noopener noreferrer"
            >
                <span class="tnt-tool-action__label">
                    <?php echo esc_html__( 'Official Website', 'toolntip-core' ); ?>
                </span>

                <span class="tnt-tool-action__external" aria-hidden="true">
                    &#8599;
                </span>
            </a>

        <?php endif; ?>

    </div>

</section>
