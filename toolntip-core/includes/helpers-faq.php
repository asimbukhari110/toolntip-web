<?php
/**
 * FAQ Helper Functions.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_get_tool_faqs( $tool ) {
    $faqs = array();
    foreach ( tnt_get_tool_faq_rows( $tool ) as $faq ) {
        $question = trim( (string) $faq['question'] );
        $answer   = (string) $faq['answer'];
        if ( '' === $question || '' === trim( wp_strip_all_tags( $answer ) ) ) {
            continue;
        }
        $faqs[] = array(
            'question' => $question,
            'answer'   => wp_kses_post( $answer ),
        );
    }
    return $faqs;
}
