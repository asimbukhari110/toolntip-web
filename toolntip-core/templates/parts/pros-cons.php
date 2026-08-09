<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (
    empty( $tool['pros'] ) &&
    empty( $tool['cons'] )
) {
    return;
}
?>

<section class="tnt-section tnt-tool-pros-cons">

    <?php if ( ! empty( $tool['pros'] ) ) : ?>

        <div class="tnt-pros">

            <h2 class="tnt-section-title">
                Pros
            </h2>

            <ul class="tnt-pros-list">

                <?php foreach ( $tool['pros'] as $pro ) : ?>

                    <li class="tnt-pro-item">

                        <?php echo esc_html( $pro ); ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

    <?php if ( ! empty( $tool['cons'] ) ) : ?>

        <div class="tnt-cons">

            <h2 class="tnt-section-title">
                Cons
            </h2>

            <ul class="tnt-cons-list">

                <?php foreach ( $tool['cons'] as $con ) : ?>

                    <li class="tnt-con-item">

                        <?php echo esc_html( $con ); ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

</section>