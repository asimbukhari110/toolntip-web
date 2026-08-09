<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['faqs'] ) ) {
    return;
}
?>

<section class="tnt-tool-faq">

    <h3>Frequently Asked Questions</h3>

    <?php foreach ( $tool['faqs'] as $faq ) : ?>

        <details class="tnt-faq-item">

            <summary>

                <?php echo esc_html( $faq['question'] ); ?>

            </summary>

            <div class="tnt-faq-answer">

                <?php echo wp_kses_post( wpautop( $faq['answer'] ) ); ?>

            </div>

        </details>

    <?php endforeach; ?>

</section>