<?php
/**
 * Tool Key Features Component.
 *
 * Uses the existing normalized feature-string contract. Presentation remains
 * independent of the feature data source so richer feature metadata can be
 * introduced later without changing Tool Detail orchestration.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['features'] ) ) {
    return;
}
?>

<div id="features" class="tnt-tool-detail-anchor">

    <section class="tnt-tool-features">

        <header class="tnt-overview-panel__heading">
            <span class="tnt-overview-panel__icon tnt-overview-panel__icon--features" aria-hidden="true">
                &#9734;
            </span>

            <h2 class="tnt-section-title">
                <?php echo esc_html__( 'Key Features', 'toolntip-core' ); ?>
            </h2>
        </header>

        <ul class="tnt-feature-grid">

            <?php foreach ( $tool['features'] as $index => $feature ) : ?>

                <li class="tnt-feature-card">

                    <span
                        class="tnt-feature-card__icon"
                        aria-hidden="true"
                    >
                        <span class="tnt-feature-card__glyph"></span>
                    </span>

                    <span class="tnt-feature-card__title">
                        <?php echo esc_html( $feature ); ?>
                    </span>

                </li>

            <?php endforeach; ?>

        </ul>

    </section>

</div>
