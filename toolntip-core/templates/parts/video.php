<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['video'] ) ) {
    return;
}
?>

<section class="tnt-tool-video">

    <h3>Demo Video</h3>

    <?php
    if ( ! empty( $tool['video']['embed'] ) ) {

        echo $tool['video']['embed'];

    } else {

        ?>

        <a
            href="<?php echo esc_url( $tool['video']['url'] ); ?>"
            target="_blank"
            rel="noopener noreferrer">

            Watch Demo Video

        </a>

        <?php

    }
    ?>

</section>