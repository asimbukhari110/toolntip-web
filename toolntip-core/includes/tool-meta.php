<?php
/**
 * Tool metadata accessors owned by ToolNTip Core.
 *
 * Legacy ACF-authored values are stored in ordinary WordPress post meta. Core
 * reads those values directly and intentionally leaves ACF field-reference
 * rows (_field_name) untouched for rollback compatibility.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve a Tool ID.
 *
 * @param WP_Post|int|string $tool Tool object, ID or slug.
 * @return int
 */
function tnt_tool_meta_post_id( $tool ) {
    if ( $tool instanceof WP_Post ) {
        return 'tool' === $tool->post_type ? (int) $tool->ID : 0;
    }

    if ( is_numeric( $tool ) ) {
        return 'tool' === get_post_type( (int) $tool ) ? (int) $tool : 0;
    }

    if ( function_exists( 'tnt_get_tool' ) ) {
        $resolved = tnt_get_tool( $tool );
        return $resolved instanceof WP_Post ? (int) $resolved->ID : 0;
    }

    return 0;
}

/**
 * Return one Tool meta value.
 *
 * @param WP_Post|int|string $tool    Tool object, ID or slug.
 * @param string             $key     Meta key.
 * @param mixed              $default Default value.
 * @return mixed
 */
function tnt_get_tool_meta( $tool, $key, $default = '' ) {
    $post_id = tnt_tool_meta_post_id( $tool );
    $key     = sanitize_key( $key );

    if ( $post_id <= 0 || '' === $key || ! metadata_exists( 'post', $post_id, $key ) ) {
        return $default;
    }

    return get_post_meta( $post_id, $key, true );
}

/**
 * Return a normalized boolean Tool meta value.
 *
 * @param WP_Post|int|string $tool Tool.
 * @param string             $key  Meta key.
 * @return bool
 */
function tnt_get_tool_meta_bool( $tool, $key ) {
    $value = tnt_get_tool_meta( $tool, $key, '' );

    return in_array( $value, array( 1, '1', true, 'true', 'yes', 'on' ), true );
}

/**
 * Return a normalized array Tool meta value.
 *
 * @param WP_Post|int|string $tool Tool.
 * @param string             $key  Meta key.
 * @return array
 */
function tnt_get_tool_meta_array( $tool, $key ) {
    $value = tnt_get_tool_meta( $tool, $key, array() );

    if ( is_array( $value ) ) {
        return array_values( $value );
    }

    if ( '' === trim( (string) $value ) ) {
        return array();
    }

    return array( $value );
}

/**
 * Return values from the legacy ACF-style repeater storage shape.
 *
 * Example: features=3 and features_0_feature ... features_2_feature.
 *
 * @param WP_Post|int|string $tool      Tool.
 * @param string             $group_key Repeater count key.
 * @param string             $item_key  Child suffix.
 * @return array
 */
function tnt_get_tool_repeater_values( $tool, $group_key, $item_key ) {
    $post_id = tnt_tool_meta_post_id( $tool );
    if ( $post_id <= 0 ) {
        return array();
    }

    $group_key = sanitize_key( $group_key );
    $item_key  = sanitize_key( $item_key );
    $count     = max( 0, (int) get_post_meta( $post_id, $group_key, true ) );
    $values    = array();

    for ( $index = 0; $index < $count; $index++ ) {
        $value = get_post_meta( $post_id, $group_key . '_' . $index . '_' . $item_key, true );
        if ( '' === trim( wp_strip_all_tags( (string) $value ) ) ) {
            continue;
        }
        $values[] = $value;
    }

    return $values;
}

/**
 * Return FAQs from the legacy ACF-style repeater storage shape.
 *
 * @param WP_Post|int|string $tool Tool.
 * @return array<int,array{question:string,answer:string}>
 */
function tnt_get_tool_faq_rows( $tool ) {
    $post_id = tnt_tool_meta_post_id( $tool );
    if ( $post_id <= 0 ) {
        return array();
    }

    $count = max( 0, (int) get_post_meta( $post_id, 'faqs', true ) );
    $rows  = array();

    for ( $index = 0; $index < $count; $index++ ) {
        $question = trim( (string) get_post_meta( $post_id, 'faqs_' . $index . '_question', true ) );
        $answer   = (string) get_post_meta( $post_id, 'faqs_' . $index . '_answer', true );

        if ( '' === $question || '' === trim( wp_strip_all_tags( $answer ) ) ) {
            continue;
        }

        $rows[] = array(
            'question' => $question,
            'answer'   => $answer,
        );
    }

    return $rows;
}

/**
 * Normalize an attachment into the image array historically returned by ACF.
 *
 * @param int $attachment_id Attachment ID.
 * @return array
 */
function tnt_get_tool_attachment_data( $attachment_id ) {
    $attachment_id = absint( $attachment_id );
    if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
        return array();
    }

    $full   = wp_get_attachment_image_src( $attachment_id, 'full' );
    $medium = wp_get_attachment_image_src( $attachment_id, 'medium' );
    $large  = wp_get_attachment_image_src( $attachment_id, 'large' );

    if ( ! is_array( $full ) ) {
        return array();
    }

    return array(
        'ID'     => $attachment_id,
        'id'     => $attachment_id,
        'url'    => $full[0],
        'alt'    => trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
        'width'  => (int) $full[1],
        'height' => (int) $full[2],
        'sizes'  => array(
            'medium' => is_array( $medium ) ? $medium[0] : $full[0],
            'large'  => is_array( $large ) ? $large[0] : $full[0],
        ),
    );
}

/**
 * Return Tool screenshots as attachment-data arrays.
 *
 * @param WP_Post|int|string $tool Tool.
 * @return array
 */
function tnt_get_tool_screenshot_attachment_data( $tool ) {
    $ids    = tnt_get_tool_meta_array( $tool, 'screenshots' );
    $images = array();

    foreach ( $ids as $id ) {
        $image = tnt_get_tool_attachment_data( $id );
        if ( ! empty( $image ) ) {
            $images[] = $image;
        }
    }

    return $images;
}
