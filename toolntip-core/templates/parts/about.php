<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['about'] ) ) {
    return;
}
?>

<section class="tnt-section tnt-tool-about">

    <h2 class="tnt-section-title">
        About This Tool
    </h2>

    <div class="tnt-about-content">

        <?php echo wp_kses_post( wpautop( $tool['about'] ) ); ?>

    </div>

</section>