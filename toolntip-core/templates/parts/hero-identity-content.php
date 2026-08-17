<?php
/**
 * Tool Hero Identity Content Component.
 *
 * Canonical informational content shared by the native Hero and granular Hero shortcode.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$feature_highlights = ! empty( $tool['features'] )
    ? array_slice( $tool['features'], 0, 3 )
    : array();

$hero            = ! empty( $tool['hero'] ) && is_array( $tool['hero'] ) ? $tool['hero'] : array();
$include_actions = ! empty( $tool['_tnt_include_hero_actions'] );
$meta_tool       = $tool;
$meta_tool['_tnt_hide_tagline'] = true;
?>

<div class="tnt-tool-hero__identity-header">

    <div class="tnt-tool-hero__brand-row<?php echo ! empty( $tool['featured'] ) ? ' tnt-tool-hero__brand-row--featured' : ''; ?>">

        <div class="tnt-tool-hero__logo">
            <?php tnt_render( 'logo', $tool ); ?>
        </div>

        <div class="tnt-tool-hero__heading">

            <?php tnt_render( 'title', $tool ); ?>

            <?php if ( ! empty( $hero['tagline'] ) ) : ?>
                <p class="tnt-tagline">
                    <?php echo esc_html( $hero['tagline'] ); ?>
                </p>
            <?php endif; ?>

        </div>

    </div>

    <?php if ( ! empty( $tool['featured'] ) ) : ?>
        <div class="tnt-featured-ribbon" role="status" aria-label="<?php echo esc_attr__( 'Featured Tool', 'toolntip-core' ); ?>">
            <span class="tnt-featured-ribbon__star" aria-hidden="true">&#9733;</span>
            <span><?php echo esc_html__( 'Featured', 'toolntip-core' ); ?></span>
        </div>
    <?php endif; ?>

</div>

<div class="tnt-tool-hero__identity-details">

    <?php tnt_render( 'hero-meta', $meta_tool ); ?>

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

    <?php if ( $include_actions ) : ?>
        <?php tnt_render( 'buttons', $tool ); ?>
    <?php endif; ?>

</div>
