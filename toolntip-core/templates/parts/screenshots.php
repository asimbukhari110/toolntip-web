<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['screenshots'] ) ) {
    return;
}
?>

<section class="tnt-tool-screenshots">

    <h2 class="tnt-section-title">Screenshots</h2>

    <div class="tnt-screenshots-grid">

        <?php foreach ( $tool['screenshots'] as $image ) : ?>

            <figure class="tnt-screenshot-item">

                <img
                    src="<?php echo esc_url( $image['thumb'] ); ?>"
                    alt="<?php echo esc_attr( $image['alt'] ); ?>"
                    loading="lazy">

            </figure>

        <?php endforeach; ?>

    </div>

</section>