<?php
/**
 * Tool Badges
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['badges'] ) ) {
    return;
}
?>

<div class="tnt-tool-badges">

    <?php foreach ( $tool['badges'] as $badge ) : ?>

        <span class="tnt-badge tnt-badge-<?php echo esc_attr( $badge['class'] ); ?>">

            <?php echo esc_html( $badge['label'] ); ?>

        </span>

    <?php endforeach; ?>

</div>