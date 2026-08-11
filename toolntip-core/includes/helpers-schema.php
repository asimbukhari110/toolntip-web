<?php
/**
 * Schema Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generate SoftwareApplication Schema.
 *
 * Community review data is used for aggregateRating.
 * The ToolNTip editor rating remains separate and is not
 * presented as a public aggregate rating.
 *
 * @param array $tool Normalized Tool data.
 * @return array
 */
function tnt_get_tool_schema( $tool ) {

    $schema = array(

        '@context' => 'https://schema.org',

        '@type' => 'SoftwareApplication',

        'name' => $tool['title'],

        'description' => $tool['excerpt'],

        'applicationCategory' => $tool['tool_type'],

        'operatingSystem' => 'Web',

        'url' => home_url(
            '/tool/' . $tool['slug'] . '/'
        ),

    );

    /*
     * Official website.
     */
    if ( ! empty( $tool['official_website'] ) ) {

        $schema['sameAs'] = $tool['official_website'];

    }

    /*
     * Community aggregate rating.
     *
     * Only approved community reviews participate in this
     * rating. The editor rating is intentionally excluded.
     */
    $community = ! empty( $tool['rating']['community'] )
        ? $tool['rating']['community']
        : array();

    if (
        ! empty( $community['count'] ) &&
        ! empty( $community['value'] )
    ) {

        $schema['aggregateRating'] = array(

            '@type' => 'AggregateRating',

            'ratingValue' => (float) $community['value'],

            'reviewCount' => (int) $community['count'],

            'bestRating' => 5,

            'worstRating' => 1,

        );

    }

    return $schema;
}