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

    <?php include TNT_CORE_PATH . 'templates/parts/rating.php'; ?>

    <?php if ( $hero['verified'] ) : ?>

        <p class="tnt-verified">

            ✔ Verified by Toolntip

        </p>

    <?php endif; ?>

</div>