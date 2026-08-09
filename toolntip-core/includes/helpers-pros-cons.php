<?php
/**
 * Pros & Cons Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Pros
 */
function tnt_get_tool_pros( $tool ) {

    $pros = array();

    if ( have_rows( 'pros', $tool->ID ) ) {

        while ( have_rows( 'pros', $tool->ID ) ) {

            the_row();

            $pro = trim( get_sub_field( 'pro' ) );

            if ( ! empty( $pro ) ) {
                $pros[] = $pro;
            }

        }

    }

    return $pros;

}

/**
 * Get Cons
 */
function tnt_get_tool_cons( $tool ) {

    $cons = array();

    if ( have_rows( 'cons', $tool->ID ) ) {

        while ( have_rows( 'cons', $tool->ID ) ) {

            the_row();

            $con = trim( get_sub_field( 'con' ) );

            if ( ! empty( $con ) ) {
                $cons[] = $con;
            }

        }

    }

    return $cons;

}