<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rating = $tool['rating'];

if ( $rating['value'] <= 0 ) {
    return;
}
?>

<div class="tnt-rating">

    <div class="tnt-stars">

        <div
            class="tnt-stars-fill"
            style="width: <?php echo esc_attr( $rating['percentage'] ); ?>%;">

            ★★★★★

        </div>

        <div class="tnt-stars-empty">

            ★★★★★

        </div>

    </div>

    <span class="tnt-rating-number">

        <?php echo number_format( $rating['value'], 1 ); ?>

    </span>

    <span class="tnt-review-count">

        (<?php echo number_format_i18n( $rating['reviews'] ); ?> Reviews)

    </span>

</div>