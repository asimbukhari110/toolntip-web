<?php
/**
 * Tool Quick Actions.
 *
 * Displays primary and secondary Tool actions in the Tool Detail sidebar.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$button = $tool['use_tool'] ?? array();
?>

<section class="tnt-tool-quick-actions">

    <h2 class="tnt-tool-quick-actions__title">
        Quick Actions
    </h2>

    <div class="tnt-tool-quick-actions__list">

        <?php if ( ! empty( $button ) ) : ?>

            <?php if ( ! empty( $button['disabled'] ) ) : ?>

                <span
                    class="tnt-btn tnt-btn-primary is-disabled"
                    aria-disabled="true"
                >
                    <?php echo esc_html( $button['label'] ); ?>
                </span>

            <?php else : ?>

                <a
                    href="<?php echo esc_url( $button['url'] ); ?>"
                    class="tnt-btn tnt-btn-primary"
                    <?php echo ! empty( $button['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                >
                    <?php echo esc_html( $button['label'] ); ?>
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
                Official Website
            </a>

        <?php endif; ?>

    </div>

</section>