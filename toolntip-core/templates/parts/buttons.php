<?php
/**
 * Tool Buttons.
 *
 * Transparent action contract:
 * Use Tool, Official Website and Partner Link remain independent.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$actions = ! empty( $tool['actions'] ) && is_array( $tool['actions'] )
    ? $tool['actions']
    : array();

$use_tool = $actions['use_tool'] ?? ( $tool['use_tool'] ?? array() );
$official = $actions['official'] ?? array();
$affiliate = $actions['affiliate'] ?? array();
?>

<div class="tnt-tool-actions">

    <?php if ( ! empty( $use_tool ) ) : ?>

        <?php if ( ! empty( $use_tool['disabled'] ) ) : ?>

            <span
                class="tnt-btn tnt-btn-primary is-disabled"
                aria-disabled="true"
            >
                <?php echo esc_html__( 'Coming Soon', 'toolntip-core' ); ?>
            </span>

        <?php else : ?>

            <a
                href="<?php echo esc_url( $use_tool['url'] ); ?>"
                class="tnt-btn tnt-btn-primary"
                <?php echo ! empty( $use_tool['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
            >
                <?php echo esc_html( $use_tool['label'] ); ?>
            </a>

        <?php endif; ?>

    <?php endif; ?>

    <?php if ( ! empty( $official['url'] ) ) : ?>
        <a
            href="<?php echo esc_url( $official['url'] ); ?>"
            class="tnt-btn tnt-btn-secondary"
            <?php echo ! empty( $official['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
        >
            <?php echo esc_html( $official['label'] ); ?>
        </a>
    <?php endif; ?>

    <?php if ( ! empty( $affiliate['url'] ) ) : ?>
        <a
            href="<?php echo esc_url( $affiliate['url'] ); ?>"
            class="tnt-btn tnt-btn-secondary tnt-btn-partner"
            <?php echo ! empty( $affiliate['external'] ) ? 'target="_blank"' : ''; ?>
            rel="<?php echo esc_attr( $affiliate['rel'] ?? 'sponsored nofollow' ); ?>"
        >
            <?php echo esc_html( $affiliate['label'] ); ?>
        </a>
    <?php endif; ?>

</div>
