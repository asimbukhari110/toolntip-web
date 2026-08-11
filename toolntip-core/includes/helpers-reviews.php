<?php
/**
 * Tool Community Reviews Helper.
 *
 * Uses native WordPress comments as Tool reviews and stores the user rating
 * as comment meta. Aggregate values are cached in Tool post meta for fast
 * reads by cards and directory loops.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enable native WordPress comment support for the Tool CPT.
 *
 * The Tool CPT is registered outside this plugin, so support is attached
 * after normal post type registration has completed.
 */
function tnt_enable_tool_review_support() {
    if ( post_type_exists( 'tool' ) ) {
        add_post_type_support( 'tool', 'comments' );
    }
}
add_action( 'init', 'tnt_enable_tool_review_support', 99 );

/**
 * Normalize a Tool object/array/ID to a Tool post ID.
 *
 * @param WP_Post|array|int $tool Tool value.
 * @return int
 */
function tnt_get_tool_review_post_id( $tool ) {
    if ( is_array( $tool ) && ! empty( $tool['post_id'] ) ) {
        return (int) $tool['post_id'];
    }

    if ( $tool instanceof WP_Post ) {
        return (int) $tool->ID;
    }

    return (int) $tool;
}

/**
 * Calculate approved community rating data for a Tool.
 *
 * Only approved comments with a valid tnt_rating value from 1 to 5
 * participate in the average and count.
 *
 * @param int $post_id Tool post ID.
 * @return array
 */
function tnt_calculate_tool_community_rating( $post_id ) {
    $post_id = (int) $post_id;

    $result = array(
        'value'      => 0.0,
        'count'      => 0,
        'max'        => 5,
        'percentage' => 0.0,
    );

    if ( $post_id <= 0 || get_post_type( $post_id ) !== 'tool' ) {
        return $result;
    }

    $comment_ids = get_comments(
        array(
            'post_id' => $post_id,
            'status'  => 'approve',
            'type'    => 'comment',
            'fields'  => 'ids',
            'number'  => 0,
        )
    );

    $total = 0;
    $count = 0;

    foreach ( $comment_ids as $comment_id ) {
        $rating = (int) get_comment_meta( $comment_id, 'tnt_rating', true );

        if ( $rating < 1 || $rating > 5 ) {
            continue;
        }

        $total += $rating;
        $count++;
    }

    if ( $count > 0 ) {
        $average = $total / $count;

        $result['value']      = round( $average, 2 );
        $result['count']      = $count;
        $result['percentage'] = ( $average / 5 ) * 100;
    }

    return $result;
}

/**
 * Recalculate and cache community rating aggregates for a Tool.
 *
 * @param int $post_id Tool post ID.
 * @return array
 */
function tnt_refresh_tool_community_rating( $post_id ) {
    $post_id = (int) $post_id;

    $rating = tnt_calculate_tool_community_rating( $post_id );

    if ( $post_id <= 0 || get_post_type( $post_id ) !== 'tool' ) {
        return $rating;
    }

    update_post_meta( $post_id, '_tnt_user_rating_average', $rating['value'] );
    update_post_meta( $post_id, '_tnt_user_rating_count', $rating['count'] );

    return $rating;
}

/**
 * Get cached community rating data for a Tool.
 *
 * Missing cache values are initialized from approved review comments.
 *
 * @param WP_Post|array|int $tool Tool value.
 * @return array
 */
function tnt_get_tool_community_rating( $tool ) {
    $post_id = tnt_get_tool_review_post_id( $tool );

    $empty = array(
        'value'      => 0.0,
        'count'      => 0,
        'max'        => 5,
        'percentage' => 0.0,
    );

    if ( $post_id <= 0 || get_post_type( $post_id ) !== 'tool' ) {
        return $empty;
    }

    if (
        ! metadata_exists( 'post', $post_id, '_tnt_user_rating_average' ) ||
        ! metadata_exists( 'post', $post_id, '_tnt_user_rating_count' )
    ) {
        return tnt_refresh_tool_community_rating( $post_id );
    }

    $value = (float) get_post_meta( $post_id, '_tnt_user_rating_average', true );
    $count = (int) get_post_meta( $post_id, '_tnt_user_rating_count', true );

    return array(
        'value'      => $value,
        'count'      => $count,
        'max'        => 5,
        'percentage' => $value > 0 ? ( $value / 5 ) * 100 : 0.0,
    );
}

/**
 * Validate a submitted Tool review before WordPress creates the comment.
 *
 * @param array $commentdata Comment data.
 * @return array
 */
function tnt_validate_tool_review_rating( $commentdata ) {
    $post_id = ! empty( $commentdata['comment_post_ID'] )
        ? (int) $commentdata['comment_post_ID']
        : 0;

    if ( $post_id <= 0 || get_post_type( $post_id ) !== 'tool' ) {
        return $commentdata;
    }

    if ( ! empty( $commentdata['comment_type'] ) ) {
        return $commentdata;
    }

    $rating = isset( $_POST['tnt_rating'] )
        ? (int) wp_unslash( $_POST['tnt_rating'] )
        : 0;

    if ( $rating < 1 || $rating > 5 ) {
        wp_die(
            esc_html__( 'Please select a rating from 1 to 5 stars.', 'toolntip-core' ),
            esc_html__( 'Review Rating Required', 'toolntip-core' ),
            array( 'response' => 400 )
        );
    }

    return $commentdata;
}
add_filter( 'preprocess_comment', 'tnt_validate_tool_review_rating' );

/**
 * Save submitted Tool rating as comment meta and refresh aggregates.
 *
 * @param int        $comment_id       Comment ID.
 * @param int|string $comment_approved Approval status.
 * @param array      $commentdata      Comment data.
 */
function tnt_save_tool_review_rating( $comment_id, $comment_approved, $commentdata ) {
    $post_id = ! empty( $commentdata['comment_post_ID'] )
        ? (int) $commentdata['comment_post_ID']
        : 0;

    if ( $post_id <= 0 || get_post_type( $post_id ) !== 'tool' ) {
        return;
    }

    $rating = isset( $_POST['tnt_rating'] )
        ? (int) wp_unslash( $_POST['tnt_rating'] )
        : 0;

    if ( $rating >= 1 && $rating <= 5 ) {
        update_comment_meta( $comment_id, 'tnt_rating', $rating );
    }

    tnt_refresh_tool_community_rating( $post_id );
}
add_action( 'comment_post', 'tnt_save_tool_review_rating', 10, 3 );

/**
 * Refresh a Tool aggregate after a comment status change.
 *
 * @param string     $new_status New status.
 * @param string     $old_status Old status.
 * @param WP_Comment $comment    Comment object.
 */
function tnt_refresh_tool_rating_on_status_change( $new_status, $old_status, $comment ) {
    if ( $new_status === $old_status || ! $comment instanceof WP_Comment ) {
        return;
    }

    if ( get_post_type( $comment->comment_post_ID ) === 'tool' ) {
        tnt_refresh_tool_community_rating( $comment->comment_post_ID );
    }
}
add_action( 'transition_comment_status', 'tnt_refresh_tool_rating_on_status_change', 10, 3 );

/**
 * Refresh a Tool aggregate when an existing comment is edited.
 *
 * @param int $comment_id Comment ID.
 */
function tnt_refresh_tool_rating_from_comment_id( $comment_id ) {
    $comment = get_comment( $comment_id );

    if ( $comment && get_post_type( $comment->comment_post_ID ) === 'tool' ) {
        tnt_refresh_tool_community_rating( $comment->comment_post_ID );
    }
}
add_action( 'edit_comment', 'tnt_refresh_tool_rating_from_comment_id' );
add_action( 'trashed_comment', 'tnt_refresh_tool_rating_from_comment_id' );
add_action( 'untrashed_comment', 'tnt_refresh_tool_rating_from_comment_id' );

/**
 * Refresh a Tool aggregate when a comment is permanently deleted.
 *
 * @param int        $comment_id Comment ID.
 * @param WP_Comment $comment    Deleted comment object.
 */
function tnt_refresh_tool_rating_on_deleted_comment( $comment_id, $comment ) {
    if ( $comment instanceof WP_Comment && get_post_type( $comment->comment_post_ID ) === 'tool' ) {
        tnt_refresh_tool_community_rating( $comment->comment_post_ID );
    }
}
add_action( 'deleted_comment', 'tnt_refresh_tool_rating_on_deleted_comment', 10, 2 );

/**
 * Add a Rating column to the WordPress Comments administration screen.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function tnt_add_review_rating_comment_column( $columns ) {
    $columns['tnt_rating'] = __( 'Rating', 'toolntip-core' );
    return $columns;
}
add_filter( 'manage_edit-comments_columns', 'tnt_add_review_rating_comment_column' );

/**
 * Render the Rating value in the WordPress Comments administration screen.
 *
 * @param string $column     Column name.
 * @param int    $comment_id Comment ID.
 */
function tnt_render_review_rating_comment_column( $column, $comment_id ) {
    if ( $column !== 'tnt_rating' ) {
        return;
    }

    $comment = get_comment( $comment_id );

    if ( ! $comment || get_post_type( $comment->comment_post_ID ) !== 'tool' ) {
        echo '&mdash;';
        return;
    }

    $rating = (int) get_comment_meta( $comment_id, 'tnt_rating', true );

    if ( $rating < 1 || $rating > 5 ) {
        echo '&mdash;';
        return;
    }

    printf(
        '<span aria-label="%1$s">%2$s</span>',
        esc_attr( sprintf( _n( '%d star', '%d stars', $rating, 'toolntip-core' ), $rating ) ),
        esc_html( str_repeat( '★', $rating ) )
    );
}
add_action( 'manage_comments_custom_column', 'tnt_render_review_rating_comment_column', 10, 2 );
