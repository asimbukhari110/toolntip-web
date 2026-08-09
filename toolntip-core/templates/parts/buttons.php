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

    <a
        href="<?php echo esc_url( $button['url'] ); ?>"
        class="tnt-btn tnt-btn-primary <?php echo $button['disabled'] ? 'is-disabled' : ''; ?>"
        <?php echo $button['external'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>

        <?php echo esc_html( $button['label'] ); ?>

    </a>

    <?php if ( ! empty( $tool['official_website'] ) ) : ?>

        <a
            href="<?php echo esc_url( $tool['official_website'] ); ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="tnt-btn tnt-btn-secondary">

            Official Website

        </a>

    <?php endif; ?>

</div>