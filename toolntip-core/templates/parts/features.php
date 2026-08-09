<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['features'] ) ) {
    return;
}
?>

<section class="tnt-tool-features">

    <h3>Features</h3>

    <ul>

        <?php foreach ( $tool['features'] as $feature ) : ?>

            <li>

                <?php echo esc_html( $feature ); ?>

            </li>

        <?php endforeach; ?>

    </ul>

</section>