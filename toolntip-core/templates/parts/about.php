<?php
/**
 * Tool About Component.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['about'] ) ) {
    return;
}
?>

<section class="tnt-section tnt-tool-about">

    <header class="tnt-overview-panel__heading">
        <span class="tnt-overview-panel__icon tnt-overview-panel__icon--info" aria-hidden="true">
            i
        </span>

        <h2 class="tnt-section-title">
            <?php echo esc_html__( 'About This Tool', 'toolntip-core' ); ?>
        </h2>
    </header>

    <div class="tnt-about-content">
        <?php echo wp_kses_post( wpautop( $tool['about'] ) ); ?>
    </div>

</section>
