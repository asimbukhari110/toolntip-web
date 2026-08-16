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
 * Split Tool About content into introductory and extended sections.
 *
 * The existing about_this_tool field remains the single source of truth.
 * Content before the first H2/H3 stays in the overview column; content from
 * that heading onward is rendered as full-width extended editorial content.
 * If a safe split point is not available, the complete content remains in the
 * introductory region.
 *
 * @param string $content Raw About This Tool field content.
 * @return array{intro:string,extended:string}
 */
function tnt_split_tool_about_content( $content ) {

    $content = trim( (string) $content );

    if ( $content === '' ) {
        return array(
            'intro'    => '',
            'extended' => '',
        );
    }

    // Preserve the existing About rendering contract before splitting.
    $html = wpautop( $content );

    /*
     * The Tool page title is the document H1 and "About This Tool" is the
     * component H2. Normalize author-entered H1/H2 subsection headings to H3
     * so About content always maintains a valid, predictable heading hierarchy.
     * Existing H3 headings are preserved.
     */
    $html = preg_replace( '/<h[12](\b[^>]*)>/i', '<h3$1>', $html );
    $html = preg_replace( '/<\/h[12]>/i', '</h3>', $html );

    if ( ! preg_match( '/<h3\b[^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE ) ) {
        return array(
            'intro'    => $html,
            'extended' => '',
        );
    }

    $offset   = (int) $match[0][1];
    $intro    = trim( substr( $html, 0, $offset ) );
    $extended = trim( substr( $html, $offset ) );

    // Avoid creating an empty overview column when content begins with a heading.
    if ( $intro === '' || $extended === '' ) {
        return array(
            'intro'    => $html,
            'extended' => '',
        );
    }

    return array(
        'intro'    => $intro,
        'extended' => $extended,
    );
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

$actions = tnt_get_tool_actions( $tool );

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

    'use_tool_url' => get_field( 'use_tool_url', $tool->ID ),

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

		'actions' => $actions,

		'use_tool' => ! empty( $actions['use_tool'] ) ? $actions['use_tool'] : array(),

		'featured_image' => get_the_post_thumbnail_url(
        $tool,
        'large'
    ),

);

}