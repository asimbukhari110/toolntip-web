<?php
/**
 * FAQ Helper Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Tool FAQs.
 *
 * @param WP_Post $tool
 * @return array
 */
function tnt_get_tool_faqs( $tool ) {

    $faqs = array();

    if ( have_rows( 'faqs', $tool->ID ) ) {

        while ( have_rows( 'faqs', $tool->ID ) ) {

            the_row();

            $question = trim( get_sub_field( 'question' ) );
            $answer   = get_sub_field( 'answer' );

            if ( empty( $question ) || empty( $answer ) ) {
                continue;
            }

            $faqs[] = array(

                'question' => $question,

                'answer' => wp_kses_post( $answer ),

            );

        }

    }

    return $faqs;

}