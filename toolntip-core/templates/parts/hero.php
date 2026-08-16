<?php
/**
 * Tool Hero Component.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$preview_image = '';
$preview_alt   = '';

if ( ! empty( $tool['featured_image'] ) ) {
    $preview_image = $tool['featured_image'];
    $preview_alt   = sprintf(
        /* translators: %s: Tool name. */
        __( '%s preview', 'toolntip-core' ),
        $tool['title']
    );
} elseif ( ! empty( $tool['screenshots'][0] ) ) {
    $preview_image = ! empty( $tool['screenshots'][0]['large'] )
        ? $tool['screenshots'][0]['large']
        : $tool['screenshots'][0]['url'];

    $preview_alt = ! empty( $tool['screenshots'][0]['alt'] )
        ? $tool['screenshots'][0]['alt']
        : sprintf(
            /* translators: %s: Tool name. */
            __( '%s preview', 'toolntip-core' ),
            $tool['title']
        );
}
?>

<div class="tnt-tool-hero">

    <?php tnt_render( 'hero-identity', $tool ); ?>

    <div class="tnt-tool-hero__preview" aria-label="<?php echo esc_attr__( 'Tool preview', 'toolntip-core' ); ?>">

        <?php if ( ! empty( $preview_image ) ) : ?>

            <div class="tnt-tool-hero__preview-frame">
                <img
                    src="<?php echo esc_url( $preview_image ); ?>"
                    alt="<?php echo esc_attr( $preview_alt ); ?>"
                    loading="eager"
                >
            </div>

        <?php else : ?>

            <div class="tnt-tool-hero__preview-fallback">

                <div class="tnt-tool-hero__preview-logo">
                    <?php tnt_render( 'logo', $tool ); ?>
                </div>

                <span><?php echo esc_html( $tool['title'] ); ?></span>

            </div>

        <?php endif; ?>

    </div>

    <?php tnt_render( 'hero-promo', $tool ); ?>

</div>
