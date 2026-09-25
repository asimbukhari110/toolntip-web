<?php
/**
 * Native single Product template.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main tnt-product-single">
    <div class="tnt-product-shell">
        <?php
        while ( have_posts() ) {
            the_post();
            echo tnt_render_product_detail( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
    </div>
</main>
<?php
get_footer();
