<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rating = $tool['rating'];

$editor = ! empty( $rating['editor'] )
    ? $rating['editor']
    : $rating;

if (
    empty( $editor['value'] ) ||
    $editor['value'] <= 0
) {
    return;
}
?>

<div class="tnt-rating tnt-rating--editor">

    <div class="tnt-stars" aria-hidden="true">

        <div
            class="tnt-stars-fill"
            style="width: <?php echo esc_attr( $editor['percentage'] ); ?>%;"
        >
            ★★★★★
        </div>

        <div class="tnt-stars-empty">
            ★★★★★
        </div>

    </div>

    <span class="tnt-rating-number">
        <?php
        echo esc_html(
            number_format_i18n(
                $editor['value'],
                1
            )
        );
        ?>
    </span>

    <span class="tnt-review-count">
        <?php
        echo esc_html__(
            'Editor Rating',
            'toolntip-core'
        );
        ?>
    </span>

</div>