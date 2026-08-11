<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['faqs'] ) ) {
    return;
}
?>

<div id="faq" class="tnt-tool-detail-anchor">

    <section class="tnt-tool-faq">

        <header class="tnt-content-panel__header">

            <span
                class="tnt-content-panel__icon"
                aria-hidden="true"
            >
                ?
            </span>

            <h2 class="tnt-section-title">
                <?php echo esc_html__( 'Frequently Asked Questions', 'toolntip-core' ); ?>
            </h2>

        </header>

        <div class="tnt-tool-faq__list">

            <?php foreach ( $tool['faqs'] as $faq_item ) : ?>

                <details class="tnt-faq-item">

                    <summary>
                        <span>
                            <?php echo esc_html( $faq_item['question'] ); ?>
                        </span>
                    </summary>

                    <div class="tnt-faq-answer">
                        <?php echo wp_kses_post( wpautop( $faq_item['answer'] ) ); ?>
                    </div>

                </details>

            <?php endforeach; ?>

        </div>

    </section>

</div>
