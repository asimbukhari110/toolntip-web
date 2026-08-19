<?php
/**
 * Resource collection shortcode orchestration.
 *
 * WEB-007.4 / 4.3D.3
 *
 * Query behavior is delegated to the canonical Resource query helper.
 * Final Resource Card / collection presentation belongs to WEB-007.4 / 4.4.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a deliberately minimal Resource collection.
 *
 * This is an interim semantic renderer for 4.3 query/shortcode validation.
 * WEB-007.4 / 4.4 will replace presentation ownership with the canonical
 * Resource Card and collection/grid components.
 *
 * @param WP_Query $query Resource query.
 * @return string
 */
function tnt_render_resource_shortcode_validation_collection( $query ) {
	if ( ! ( $query instanceof WP_Query ) || ! $query->have_posts() ) {
		return '<div class="tnt-resources tnt-resources--empty"><p>' .
			esc_html__( 'No resources found.', 'toolntip-core' ) .
		'</p></div>';
	}

	$output = '<div class="tnt-resources tnt-resources--validation">';
	$output .= '<ul class="tnt-resources__list">';

	foreach ( $query->posts as $resource ) {
		if ( ! ( $resource instanceof WP_Post ) ) {
			continue;
		}

		$output .= '<li class="tnt-resources__item">';
		$output .= '<a class="tnt-resources__link" href="' . esc_url( get_permalink( $resource ) ) . '">';
		$output .= esc_html( get_the_title( $resource ) );
		$output .= '</a>';
		$output .= '</li>';
	}

	$output .= '</ul>';
	$output .= '</div>';

	return $output;
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

	return tnt_render_resource_shortcode_validation_collection( $query );
}
add_shortcode( 'tnt_resources', 'tnt_resources_shortcode' );
