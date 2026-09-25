<?php
/**
 * Native Product archive template.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main tnt-product-archive">
    <div class="tnt-product-shell">
        <header class="tnt-product-archive__header">
            <h1><?php esc_html_e( 'Products', 'toolntip-core' ); ?></h1>
            <p><?php esc_html_e( 'Software built by ToolNTip for real-world technical and organizational challenges.', 'toolntip-core' ); ?></p>
            <p><?php esc_html_e( 'Explore ToolNTip products, their capabilities, availability, editions and supporting resources.', 'toolntip-core' ); ?></p>
        </header>
        <?php echo tnt_render_product_collection(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</main>
<?php
get_footer();
