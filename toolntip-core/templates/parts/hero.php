<?php
/**
 * Tool Hero Component.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$feature_highlights = ! empty( $tool['features'] )
    ? array_slice( $tool['features'], 0, 3 )
    : array();

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

    <div class="tnt-tool-hero__identity">

        <div class="tnt-tool-hero__brand-row">

            <div class="tnt-tool-hero__logo">
                <?php tnt_render( 'logo', $tool ); ?>
            </div>

            <div class="tnt-tool-hero__heading">

                <?php tnt_render( 'title', $tool ); ?>

                <?php tnt_render( 'hero-meta', $tool ); ?>

            </div>

        </div>

        <div class="tnt-tool-hero__badges">
            <?php tnt_render( 'badges', $tool ); ?>
        </div>

        <?php if ( ! empty( $feature_highlights ) ) : ?>

            <ul class="tnt-tool-hero__highlights" aria-label="<?php echo esc_attr__( 'Key features', 'toolntip-core' ); ?>">

                <?php foreach ( $feature_highlights as $feature ) : ?>

                    <li>
                        <?php echo esc_html( $feature ); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

        <?php if ( ! empty( $tool['excerpt'] ) ) : ?>

            <p class="tnt-tool-hero__summary">
                <?php echo esc_html( $tool['excerpt'] ); ?>
            </p>

        <?php endif; ?>

        <?php tnt_render( 'buttons', $tool ); ?>

    </div>

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

</div>
