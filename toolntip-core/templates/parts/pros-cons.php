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

<div id="pros-cons" class="tnt-tool-detail-anchor">

    <section class="tnt-tool-pros-cons">

        <header class="tnt-content-panel__header">

            <span
                class="tnt-content-panel__icon"
                aria-hidden="true"
            >
                &#9878;
            </span>

            <h2 class="tnt-section-title">
                <?php echo esc_html__( 'Pros & Cons', 'toolntip-core' ); ?>
            </h2>

        </header>

        <div class="tnt-tool-pros-cons__grid">

            <?php if ( ! empty( $tool['pros'] ) ) : ?>

                <div class="tnt-pros">

                    <h3 class="tnt-tool-pros-cons__title">
                        <?php echo esc_html__( 'Pros', 'toolntip-core' ); ?>
                    </h3>

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

                    <h3 class="tnt-tool-pros-cons__title">
                        <?php echo esc_html__( 'Cons', 'toolntip-core' ); ?>
                    </h3>

                    <ul class="tnt-cons-list">

                        <?php foreach ( $tool['cons'] as $con ) : ?>

                            <li class="tnt-con-item">
                                <?php echo esc_html( $con ); ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

        </div>

    </section>

</div>
