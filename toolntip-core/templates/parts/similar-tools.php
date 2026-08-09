<?php
/**
 * Similar Tools
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Get related tools.
 */
$related_tools = tnt_get_related_tools( $tool );

/*
 * Nothing to show.
 */
if ( empty( $related_tools ) ) {
    return;
}
?>

<section class="tnt-similar-tools">

    <h2 class="tnt-section-title">
        <?php echo esc_html__( 'Similar Tools', 'toolntip-core' ); ?>
    </h2>

    <div class="tnt-tool-grid">

        <?php foreach ( $related_tools as $related_tool ) : ?>

            <?php
            tnt_render(
                'tool-card',
                $related_tool
            );
            ?>

        <?php endforeach; ?>

    </div>

</section>