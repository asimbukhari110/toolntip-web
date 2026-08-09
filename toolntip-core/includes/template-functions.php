<?php
/**
 * Template Functions
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render a plugin template.
 *
 * @param string $template Template filename (without .php).
 * @param array  $args     Variables passed to the template.
 *
 * @return string
 */
function tnt_render_template( $template, $args = array() ) {

    $template_file = TNT_CORE_PATH . 'templates/' . $template . '.php';

    if ( ! file_exists( $template_file ) ) {
        return '';
    }

    ob_start();

    extract( $args, EXTR_SKIP );

    include $template_file;

    return ob_get_clean();

}