<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$hero = $tool['hero'];

if ( empty( $hero ) ) {
    return;
}
?>

<div class="tnt-hero-meta">

    <?php if ( ! empty( $hero['tagline'] ) ) : ?>

        <p class="tnt-tagline">
            <?php echo esc_html( $hero['tagline'] ); ?>
        </p>

    <?php endif; ?>

    <div class="tnt-hero-meta__signals">

        <?php if ( ! empty( $hero['verified'] ) ) : ?>

            <span class="tnt-verified">
                <span class="tnt-verified__icon" aria-hidden="true">&#10003;</span>
                <?php echo esc_html__( 'Verified by ToolNTip', 'toolntip-core' ); ?>
            </span>

        <?php endif; ?>

        <?php tnt_render( 'rating', $tool ); ?>

    </div>

</div>
