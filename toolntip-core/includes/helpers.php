<?php
/**
 * Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get a Tool by ID or Slug.
 *
 * @param int|string $value Tool ID or slug.
 * @return WP_Post|null
 */
function tnt_get_tool( $value ) {

    /*
     * Already a WP_Post.
     */
    if ( $value instanceof WP_Post ) {

        if ( $value->post_type === 'tool' ) {
            return $value;
        }

        return null;
    }

    /*
     * Search by numeric ID.
     */
    if ( is_numeric( $value ) ) {

        $tool = get_post( (int) $value );

        if ( $tool && $tool->post_type === 'tool' ) {
            return $tool;
        }

        return null;
    }

    /*
     * Search by slug.
     */
    $tool = get_page_by_path(
        $value,
        OBJECT,
        'tool'
    );

    if ( $tool ) {
        return $tool;
    }

    return null;
}

/**
 * Get normalized Tool data.
 *
 * @param WP_Post|int|string $tool Tool object, ID or slug.
 * @return array|null
 */
function tnt_get_tool_data( $tool ) {

    // Accept ID or slug.
    if ( ! $tool instanceof WP_Post ) {
        $tool = tnt_get_tool( $tool );
    }

    if ( ! $tool ) {
        return null;
    }

    
	
$last_verified = get_field( 'last_verified', $tool->ID );

$platforms = get_field( 'platform', $tool->ID );

	if ( empty( $platforms ) ) {
		$platforms = array();
	} elseif ( ! is_array( $platforms ) ) {
		$platforms = array( $platforms );
	}

	$platforms = array_values(
		array_filter(
			array_map(
				static function ( $platform ) {
					return trim( (string) $platform );
				},
				$platforms
			)
		)
	);

return array(

    'id' => $tool->ID,
	
	'post'    => $tool,

    'post_id' => $tool->ID,

    'title' => get_the_title( $tool ),

    'slug' => $tool->post_name,

    'excerpt' => get_the_excerpt( $tool ),

    'about' => get_field( 'about_this_tool', $tool->ID ),

    'tool_type' => get_field( 'tool_type', $tool->ID ),

    'official_website' => get_field( 'official_website', $tool->ID ),

    'affiliate_url' => get_field( 'affiliate_url', $tool->ID ),

    'pricing' => get_field( 'pricing', $tool->ID ),

    'platform' => $platforms,

    'badges' => tnt_get_tool_badges( $tool ),

    'developer' => get_field( 'developer', $tool->ID ),

    'featured' => (bool) get_field( 'featured_tool', $tool->ID ),
	
	'features' => tnt_get_tool_features( $tool ),
	
	'pros' => tnt_get_tool_pros( $tool ),

	'cons' => tnt_get_tool_cons( $tool ),
	
	'screenshots' => tnt_get_tool_screenshots( $tool ),
	
	'video' => tnt_get_tool_video( $tool ),
	
	'faqs' => tnt_get_tool_faqs( $tool ),
	
	'hero' => tnt_get_tool_hero( $tool ),
	
	'rating' => tnt_get_tool_rating( $tool ),
	
	
	'categories' => tnt_get_tool_categories( $tool ),

	'tags' => tnt_get_tool_tags( $tool ),

    'last_verified' => $last_verified
        ? date( 'F j, Y', strtotime( $last_verified ) )
        : '',

		'logo' => tnt_get_tool_logo( $tool ),

		'use_tool' => tnt_get_use_tool_button( $tool ),

		'featured_image' => get_the_post_thumbnail_url(
        $tool,
        'large'
    ),

);

}