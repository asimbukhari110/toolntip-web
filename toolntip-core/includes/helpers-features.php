<?php
/**
 * Features Helper.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_get_tool_features( $tool ) {
    return array_values(
        array_map(
            static function ( $value ) { return trim( (string) $value ); },
            tnt_get_tool_repeater_values( $tool, 'features', 'feature' )
        )
    );
}
