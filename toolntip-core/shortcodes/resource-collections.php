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
function tnt_render_resource_collection( $query ) {
	if ( ! ( $query instanceof WP_Query ) || ! $query->have_posts() ) {
		return '<div class="tnt-resource-collection"><div class="tnt-empty"><p>' .
			esc_html__( 'No resources found.', 'toolntip-core' ) .
			'</p></div></div>';
	}

	ob_start();
	?>
	<div class="tnt-resource-collection">
		<div class="tnt-resource-grid">
			<?php foreach ( $query->posts as $resource ) : ?>
				<?php
				if ( ! ( $resource instanceof WP_Post ) || 'resource' !== $resource->post_type ) {
					continue;
				}

				$card_data = tnt_get_resource_card_data( $resource );

				if ( empty( $card_data ) ) {
					continue;
				}

				tnt_render( 'resource-card', $card_data );
				?>
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
		'orderby' => $atts['orderby'],
		'order'   => $atts['order'],
		'status'  => 'publish',
	);

	$query = tnt_get_resources( $query_args );

	return tnt_render_resource_collection( $query );
}
add_shortcode( 'tnt_resources', 'tnt_resources_shortcode' );
