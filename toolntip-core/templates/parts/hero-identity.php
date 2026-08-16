<?php
/**
 * Tool Hero Identity Component.
 *
 * Canonical native Tool Detail Hero identity column.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="tnt-tool-hero__identity">
    <?php tnt_render( 'hero-identity-content', $tool ); ?>
    <?php tnt_render( 'buttons', $tool ); ?>
</div>
