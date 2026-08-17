<?php
/**
 * ToolNTip Labs shortcode.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the ToolNTip Labs collection.
 *
 * Supported attributes deliberately omit `type`: Labs always means Internal
 * Tools and that scope is enforced by the Labs semantic helper.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tnt_labs_shortcode( $atts ) {

    $atts = shortcode_atts(
        array(
            'category' => '',
            'featured' => '',
            'limit'    => 8,
            'columns'  => 3,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ),
        $atts,
        'tnt_labs'
    );

    $columns = tnt_tool_query_normalize_columns( $atts['columns'], array( 2, 3, 4 ), 3 );
    $query   = new WP_Query( tnt_build_labs_query_args( $atts ) );

    tnt_enqueue_tool_collection_assets();
    wp_enqueue_style(
        'tnt-labs',
        TNT_CORE_URL . 'assets/css/labs.css',
        array( 'tnt-tool-collections' ),
        TNT_CORE_VERSION
    );

    ob_start();
    ?>
    <section class="tnt-labs" data-tnt-component="labs-collection">
        <?php if ( $query->have_posts() ) : ?>
            <?php echo tnt_render_tool_shortcode_grid( $query, $columns, 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php else : ?>
            <div class="tnt-labs__empty tnt-empty">
                <p class="tnt-labs__empty-title"><?php echo esc_html__( 'No ToolNTip Labs applications are available yet.', 'toolntip-core' ); ?></p>
                <p><?php echo esc_html__( 'Explore the Tool directory for other useful tools.', 'toolntip-core' ); ?></p>
                <p>
                    <a class="tnt-action" href="<?php echo esc_url( tnt_get_labs_tools_url() ); ?>">
                        <?php echo esc_html__( 'Explore Tools', 'toolntip-core' ); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </section>
    <?php

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode( 'tnt_labs', 'tnt_labs_shortcode' );
