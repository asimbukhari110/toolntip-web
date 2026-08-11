<?php
/**
 * Tool Detail Hero Promotional Placement.
 *
 * Presentation-only consumer of the normalized promotional placement contract.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$promo = function_exists( 'tnt_get_promo_placement' )
    ? tnt_get_promo_placement( 'tool-detail-hero', $tool )
    : array();

if ( empty( $promo ) ) {
    return;
}

$promo_type = ! empty( $promo['type'] )
    ? sanitize_html_class( $promo['type'] )
    : 'house';
?>

<aside
    class="tnt-tool-hero__promo tnt-tool-hero__promo--<?php echo esc_attr( $promo_type ); ?>"
    aria-label="<?php echo esc_attr__( 'Promotional content', 'toolntip-core' ); ?>"
>

    <?php if ( ! empty( $promo['label'] ) ) : ?>

        <span class="tnt-tool-hero__promo-label">
            <?php echo esc_html( $promo['label'] ); ?>
        </span>

    <?php endif; ?>

    <div class="tnt-tool-hero__promo-body">

        <div class="tnt-tool-hero__promo-mark" aria-hidden="true">
            TNT
        </div>

        <?php if ( ! empty( $promo['eyebrow'] ) ) : ?>

            <span class="tnt-tool-hero__promo-eyebrow">
                <?php echo esc_html( $promo['eyebrow'] ); ?>
            </span>

        <?php endif; ?>

        <?php if ( ! empty( $promo['title'] ) ) : ?>

            <strong class="tnt-tool-hero__promo-title">
                <?php echo esc_html( $promo['title'] ); ?>
            </strong>

        <?php endif; ?>

        <?php if ( ! empty( $promo['description'] ) ) : ?>

            <p class="tnt-tool-hero__promo-description">
                <?php echo esc_html( $promo['description'] ); ?>
            </p>

        <?php endif; ?>

    </div>

    <?php if ( ! empty( $promo['url'] ) && ! empty( $promo['cta_label'] ) ) : ?>

        <a
            class="tnt-tool-hero__promo-cta"
            href="<?php echo esc_url( $promo['url'] ); ?>"
            <?php echo ! empty( $promo['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
        >
            <span><?php echo esc_html( $promo['cta_label'] ); ?></span>
            <span aria-hidden="true">&#8594;</span>
        </a>

    <?php endif; ?>

</aside>
