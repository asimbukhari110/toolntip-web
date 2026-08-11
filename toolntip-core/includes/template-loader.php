<?php
/**
 * Tool Template Loader.
 *
 * Routes single Tool requests through ToolNTip Core.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load the canonical single Tool template.
 *
 * @param string $template Current WordPress template.
 * @return string
 */
function tnt_tool_template_include( $template ) {

    if ( ! is_singular( 'tool' ) ) {
        return $template;
    }

    $tool_template = TNT_CORE_PATH . 'templates/single-tool.php';

    if ( file_exists( $tool_template ) ) {
        return $tool_template;
    }

    return $template;
}

add_filter( 'template_include', 'tnt_tool_template_include', 99 );