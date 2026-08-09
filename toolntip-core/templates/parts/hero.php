<?php
/**
 * Tool Hero Component.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="tnt-tool-hero">

    <?php tnt_render( 'logo', $tool ); ?>

    <div class="tnt-tool-hero-content">

        <?php tnt_render( 'title', $tool ); ?>

        <?php tnt_render( 'hero-meta', $tool ); ?>

        <?php tnt_render( 'badges', $tool ); ?>

        <?php tnt_render( 'buttons', $tool ); ?>

    </div>

</div>
