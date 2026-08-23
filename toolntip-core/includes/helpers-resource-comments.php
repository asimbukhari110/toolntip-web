<?php
/**
 * Resource Comments integration.
 *
 * WEB-007.4 / 4.6-F4
 *
 * Reuses the native WordPress comment lifecycle while enforcing ToolNTip's
 * authenticated-only participation rule for Resource comments.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Route Resource comment rendering to the ToolNTip-owned presentation template.
 *
 * @param string $template Theme-resolved comments template path.
 * @return string
 */
function tnt_resource_comments_template( $template ) {
    if ( is_singular( 'resource' ) ) {
        $plugin_template = TNT_CORE_PATH . 'templates/comments-resource.php';

        if ( is_readable( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
}
add_filter( 'comments_template', 'tnt_resource_comments_template' );

/**
 * Enforce authenticated-only Resource comment submission independently of the
 * site's global Discussion setting.
 *
 * @param array<string,mixed> $commentdata Incoming comment data.
 * @return array<string,mixed>
 */
function tnt_require_login_for_resource_comments( $commentdata ) {
    $post_id = isset( $commentdata['comment_post_ID'] ) ? absint( $commentdata['comment_post_ID'] ) : 0;

    if ( $post_id && 'resource' === get_post_type( $post_id ) && ! is_user_logged_in() ) {
        wp_die(
            esc_html__( 'You must be signed in to comment on this Resource.', 'toolntip-core' ),
            esc_html__( 'Sign in required', 'toolntip-core' ),
            array( 'response' => 403 )
        );
    }

    return $commentdata;
}
add_filter( 'preprocess_comment', 'tnt_require_login_for_resource_comments' );

/**
 * Render one Resource comment using native WordPress comment data and reply
 * behavior without Tool review/rating semantics.
 *
 * @param WP_Comment $comment Comment object.
 * @param array      $args    wp_list_comments() arguments.
 * @param int        $depth   Current comment depth.
 * @return void
 */
function tnt_render_resource_comment( $comment, $args, $depth ) {
    ?>
    <li <?php comment_class( 'tnt-resource-comment' ); ?> id="comment-<?php comment_ID(); ?>">
        <article class="tnt-resource-comment__body">
            <div class="tnt-resource-comment__avatar">
                <?php echo get_avatar( $comment, 44, '', '', array( 'loading' => 'lazy' ) ); ?>
            </div>
            <div class="tnt-resource-comment__main">
                <header class="tnt-resource-comment__header">
                    <span class="tnt-resource-comment__author"><?php echo wp_kses_post( get_comment_author_link( $comment ) ); ?></span>
                    <a class="tnt-resource-comment__date" href="<?php echo esc_url( get_comment_link( $comment ) ); ?>">
                        <time datetime="<?php comment_time( DATE_W3C ); ?>">
                            <?php echo esc_html( get_comment_date( get_option( 'date_format' ), $comment ) ); ?>
                        </time>
                    </a>
                </header>

                <?php if ( '0' === $comment->comment_approved ) : ?>
                    <p class="tnt-resource-comment__moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'toolntip-core' ); ?></p>
                <?php endif; ?>

                <div class="tnt-resource-comment__content">
                    <?php comment_text( $comment ); ?>
                </div>

                <?php
                comment_reply_link(
                    array_merge(
                        $args,
                        array(
                            'add_below'  => 'comment',
                            'depth'      => $depth,
                            'max_depth'  => $args['max_depth'],
                            'reply_text' => esc_html__( 'Reply', 'toolntip-core' ),
                            'before'     => '<div class="tnt-resource-comment__reply">',
                            'after'      => '</div>',
                        )
                    ),
                    $comment
                );
                ?>
            </div>
        </article>
    <?php
}
