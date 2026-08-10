<?php
/**
 * Single Tool Template.
 *
 * Bridges WordPress single Tool requests to the normalized
 * ToolNTip Core Tool Detail presentation.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :

    the_post();

    $tool = tnt_get_tool_data( get_the_ID() );

    if ( $tool ) {
        include TNT_CORE_PATH . 'templates/tool-detail.php';
    }

endwhile;

get_footer();