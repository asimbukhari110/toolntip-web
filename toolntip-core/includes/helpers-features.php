<?php
/**
 * Features Helper
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_tool_features( $tool ) {

    $features = array();

    if ( have_rows( 'features', $tool->ID ) ) {

        while ( have_rows( 'features', $tool->ID ) ) {

            the_row();

            $feature = trim( get_sub_field( 'feature' ) );

            if ( ! empty( $feature ) ) {
                $features[] = $feature;
            }

        }

    }

    return $features;

}