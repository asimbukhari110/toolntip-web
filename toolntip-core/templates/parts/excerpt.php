<?php
/**
 * Tool Excerpt
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['excerpt'] ) ) {
    return;
}
?>

<p class="tnt-tool-excerpt">

    <?php echo esc_html( $tool['excerpt'] ); ?>

</p>