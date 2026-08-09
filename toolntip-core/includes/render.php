<?php
/**
 * Component Renderer
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render a template part.
 *
 * @param string $component Component name.
 * @param array  $tool      Tool data.
 */
function tnt_render( $component, $tool = array() ) {

    $file = TNT_CORE_PATH . 'templates/parts/' .
        sanitize_file_name( $component ) .
        '.php';

    if ( file_exists( $file ) ) {
        include $file;
    }

}