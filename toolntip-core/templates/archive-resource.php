<?php
/**
 * Resource Archive Template.
 *
 * Canonical WEB-007.4 / 4.5 Resource Hub surface for /resources/.
 * Reuses the native main archive query so the Hub does not execute a second
 * Resource collection query.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

global $wp_query;
?>

<main class="tnt-resource-archive" id="main">
    <div class="tnt-resource-archive__inner">
        <?php
        if ( function_exists( 'tnt_render_resource_hub' ) ) {
            echo tnt_render_resource_hub( array(), $wp_query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
    </div>
</main>

<?php
get_footer();
