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
 * @param array $tool
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

        'url' => home_url( '/tool/' . $tool['slug'] . '/' ),

    );

    if ( ! empty( $tool['official_website'] ) ) {

        $schema['sameAs'] = $tool['official_website'];

    }

    return $schema;

}