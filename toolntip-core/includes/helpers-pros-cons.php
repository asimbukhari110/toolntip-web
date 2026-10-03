<?php
/**
 * Pros & Cons Helper.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_get_tool_pros( $tool ) {
    return array_values( array_map( 'strval', tnt_get_tool_repeater_values( $tool, 'pros', 'pro' ) ) );
}

function tnt_get_tool_cons( $tool ) {
    return array_values( array_map( 'strval', tnt_get_tool_repeater_values( $tool, 'cons', 'con' ) ) );
}
