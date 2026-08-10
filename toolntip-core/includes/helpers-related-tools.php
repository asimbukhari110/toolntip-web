<?php
/**
 * Related Tools Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get related tools.
 *
 * @param WP_Post|int $tool  Tool object or ID.
 * @param int         $limit Maximum number of tools.
 *
 * @return array
 */
function tnt_get_related_tools( $tool, $limit = 6 ) {

    /*
     * Accept Tool Array, WP_Post or ID.
     */
    if ( is_array( $tool ) ) {
        $tool = $tool['post'];
    }

    if ( ! $tool instanceof WP_Post ) {
        $tool = tnt_get_tool( $tool );
    }

    if ( ! $tool ) {
        return array();
    }

    $candidates = tnt_get_candidate_tools( $tool );

    $results = array();

    foreach ( $candidates as $candidate ) {

        $score = tnt_calculate_related_score(
            $tool,
            $candidate
        );

        if ( $score <= 0 ) {
            continue;
        }

        /*
         * Build the complete Toolntip data array.
         */
        $related_tool = tnt_get_tool_data( $candidate );

        if ( ! $related_tool ) {
            continue;
        }

        /*
         * Save score.
         */
        $related_tool['related_score'] = $score;

        $results[] = $related_tool;

    }

    usort(
        $results,
        function ( $a, $b ) {
            return $b['related_score'] <=> $a['related_score'];
        }
    );

    return array_slice(
        $results,
        0,
        $limit
    );

}



/**
 * Get candidate tools.
 *
 * @param WP_Post $tool Current tool.
 *
 * @return WP_Post[]
 */
function tnt_get_candidate_tools( WP_Post $tool ) {

    $query = new WP_Query(
        array(
            'post_type'      => 'tool',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__not_in'   => array( $tool->ID ),
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );

    return $query->posts;

}

/**
 * Calculate the similarity score between two tools.
 *
 * @param WP_Post $tool
 * @param WP_Post $candidate
 *
 * @return int
 */
function tnt_calculate_related_score( WP_Post $tool, WP_Post $candidate ) {

    $score = 0;

    /*
     * Category Match
     * -------------------------------------------------
     */

    $tool_categories = wp_get_post_terms(
        $tool->ID,
        'tool_category',
        array(
            'fields' => 'ids',
        )
    );

    $candidate_categories = wp_get_post_terms(
        $candidate->ID,
        'tool_category',
        array(
            'fields' => 'ids',
        )
    );

    if ( array_intersect( $tool_categories, $candidate_categories ) ) {
        $score += 100;
    }

    /*
     * Tag Match
     * -------------------------------------------------
     */

    $tool_tags = wp_get_post_terms(
        $tool->ID,
        'tool_tag',
        array(
            'fields' => 'ids',
        )
    );

    $candidate_tags = wp_get_post_terms(
        $candidate->ID,
        'tool_tag',
        array(
            'fields' => 'ids',
        )
    );

    $matching_tags = array_intersect(
        $tool_tags,
        $candidate_tags
    );

    $score += min(
        count( $matching_tags ) * 20,
        60
    );

    /*
     * Tool Type
     * -------------------------------------------------
     */

    if (
        get_field( 'tool_type', $tool->ID ) ===
        get_field( 'tool_type', $candidate->ID )
    ) {
        $score += 15;
    }

    /*
	 * Platform
	 * -------------------------------------------------
	 *
	 * Platform is a multi-value field. Related tools receive
	 * the platform score when at least one normalized platform
	 * value overlaps.
	 */

	$tool_platforms = get_field( 'platform', $tool->ID );
	$candidate_platforms = get_field( 'platform', $candidate->ID );

	$tool_platforms = is_array( $tool_platforms )
		? $tool_platforms
		: array_filter( array( $tool_platforms ) );

	$candidate_platforms = is_array( $candidate_platforms )
		? $candidate_platforms
		: array_filter( array( $candidate_platforms ) );

	if ( array_intersect( $tool_platforms, $candidate_platforms ) ) {
		$score += 10;
	}

    /*
     * Pricing
     * -------------------------------------------------
     */

    if (
        get_field( 'pricing', $tool->ID ) ===
        get_field( 'pricing', $candidate->ID )
    ) {
        $score += 10;
    }

    /*
     * Featured
     * -------------------------------------------------
     */

    if ( get_field( 'featured_tool', $candidate->ID ) ) {
        $score += 5;
    }

    return $score;
}