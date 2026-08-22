<?php
/**
 * Resource collection shortcode orchestration.
 *
 * WEB-007.4 / 4.3D.3
 *
 * Query behavior is delegated to the canonical Resource query helper.
 * Resource Card/collection presentation is owned by WEB-007.4 / 4.4.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a Resource collection from a canonical Resource query.
 *
 * Query construction remains owned by WEB-007.4 / 4.3. This renderer owns
 * only collection structure, Resource Card delegation, and the simple empty
 * state frozen in WEB-007.4 / 4.4B.
 *
 * @param WP_Query $query Resource query.
 * @return string
 */
function tnt_render_resource_collection( $query, $args = array() ) {
    if ( ! ( $query instanceof WP_Query ) || ! $query->have_posts() ) {
        return '<div class="tnt-resource-collection"><div class="tnt-empty"><p>' .
            esc_html__( 'No resources found.', 'toolntip-core' ) .
            '</p></div></div>';
    }

    $args = wp_parse_args(
        (array) $args,
        array(
            'monetization' => false,
            'columns'      => 3,
        )
    );

    $resources = array();
    foreach ( $query->posts as $resource ) {
        if ( ! ( $resource instanceof WP_Post ) || 'resource' !== $resource->post_type ) {
            continue;
        }

        $card_data = tnt_get_resource_card_data( $resource );
        if ( empty( $card_data ) ) {
            continue;
        }

        $resources[] = array(
            'id'   => absint( $resource->ID ),
            'card' => $card_data,
        );
    }

    if ( empty( $resources ) ) {
        return '<div class="tnt-resource-collection"><div class="tnt-empty"><p>' .
            esc_html__( 'No resources found.', 'toolntip-core' ) .
            '</p></div></div>';
    }

    $before_grid_ad = '';
    $in_grid_ad     = '';
    $in_grid_slots  = array();

    if ( ! empty( $args['monetization'] ) ) {
        $before_grid_ad = function_exists( 'tnt_get_tool_directory_before_grid_ad_markup' )
            ? tnt_get_tool_directory_before_grid_ad_markup()
            : '';

        $resource_ids = array_column( $resources, 'id' );
        $seed = 'resource-hub|page:' . max( 1, tnt_resource_hub_current_page() )
            . '|query:' . (string) wp_json_encode( wp_unslash( $_GET ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            . '|resources:' . implode( ',', $resource_ids );

        $in_grid_slots = function_exists( 'tnt_get_tool_directory_ad_slots' )
            ? tnt_get_tool_directory_ad_slots(
                count( $resources ),
                array(
                    'tools_loaded' => 0,
                    'ads_rendered' => 0,
                    'columns'      => max( 1, absint( $args['columns'] ) ),
                    'tool_ids'     => $resource_ids,
                    'seed'         => $seed,
                )
            )
            : array();

        $in_grid_ad = ! empty( $in_grid_slots ) && function_exists( 'tnt_get_tool_directory_in_grid_ad_markup' )
            ? tnt_get_tool_directory_in_grid_ad_markup()
            : '';
    }

    ob_start();
    ?>
    <div class="tnt-resource-collection">
        <?php if ( '' !== $before_grid_ad ) : ?>
            <div class="tnt-resource-collection__before-grid-ad">
                <?php echo $before_grid_ad; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>

        <div class="tnt-resource-grid">
            <?php foreach ( $resources as $resource_index => $resource ) : ?>
                <?php tnt_render( 'resource-card', $resource['card'] ); ?>

                <?php
                $resource_position = $resource_index + 1;
                if ( '' !== $in_grid_ad && in_array( $resource_position, $in_grid_slots, true ) ) :
                    ?>
                    <div class="tnt-resource-grid__ad-card" data-tnt-resource-ad-after="<?php echo esc_attr( $resource_position ); ?>">
                        <?php echo $in_grid_ad; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * [tnt_resources] shortcode.
 *
 * Frozen public v1 attributes:
 * - limit
 * - type
 * - topic
 * - tag
 * - search
 * - featured
 * - orderby
 * - order
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function tnt_resources_shortcode( $atts = array() ) {
	if ( wp_style_is( 'tnt-resource-card', 'registered' ) ) {
		wp_enqueue_style( 'tnt-resource-card' );
	}

	$atts = shortcode_atts(
		array(
			'limit'   => 12,
			'type'    => '',
			'topic'   => '',
			'tag'     => '',
			'search'  => '',
			'featured' => '',
			'orderby' => 'date',
			'order'   => 'DESC',
		),
		(array) $atts,
		'tnt_resources'
	);

	$query_args = array(
		'limit'   => $atts['limit'],
		'type'    => $atts['type'],
		'topic'   => $atts['topic'],
		'tag'     => $atts['tag'],
		'search'  => sanitize_text_field( (string) $atts['search'] ),
		'featured' => $atts['featured'],
		'orderby' => $atts['orderby'],
		'order'   => $atts['order'],
		'status'  => 'publish',
	);

	$query = tnt_get_resources( $query_args );

	return tnt_render_resource_collection( $query );
}
add_shortcode( 'tnt_resources', 'tnt_resources_shortcode' );
