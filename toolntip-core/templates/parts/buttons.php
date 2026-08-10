<?php
/**
 * Tool Buttons
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$button = $tool['use_tool'];
?>

<div class="tnt-tool-actions">

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

    <?php if ( ! empty( $tool['official_website'] ) ) : ?>

        <a
            href="<?php echo esc_url( $tool['official_website'] ); ?>"
            class="tnt-btn tnt-btn-secondary"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?php echo esc_html__( 'Official Website', 'toolntip-core' ); ?>
        </a>

    <?php endif; ?>

</div>
