<?php
/**
 * Tool Detail Page
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<article class="tnt-tool-detail">

    <header class="tnt-tool-header">

        <?php tnt_render( 'hero', $tool ); ?>

    </header>

    <main class="tnt-tool-content">

        <?php tnt_render( 'screenshots', $tool ); ?>

        <?php tnt_render( 'about', $tool ); ?>

        <?php tnt_render( 'features', $tool ); ?>

        <?php tnt_render( 'pros-cons', $tool ); ?>

        <?php tnt_render( 'video', $tool ); ?>

        <?php tnt_render( 'faq', $tool ); ?>

        <?php tnt_render( 'similar-tools', $tool ); ?>

        <?php tnt_render( 'tags', $tool ); ?>

    </main>

</article>