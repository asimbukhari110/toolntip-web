<?php
/**
 * Resource Comments template.
 *
 * WEB-007.4 / 4.6-F4
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( post_password_required() ) {
    return;
}

$comment_count = get_comments_number();
?>
<section id="comments" class="tnt-resource-comments" aria-labelledby="tnt-resource-comments-title">
    <header class="tnt-resource-comments__header">
        <h2 id="tnt-resource-comments-title" class="tnt-resource-comments__title">
            <?php esc_html_e( 'Comments', 'toolntip-core' ); ?>
        </h2>
        <?php if ( $comment_count > 0 ) : ?>
            <span class="tnt-resource-comments__count">
                <?php
                printf(
                    esc_html( _n( '%s comment', '%s comments', $comment_count, 'toolntip-core' ) ),
                    esc_html( number_format_i18n( $comment_count ) )
                );
                ?>
            </span>
        <?php endif; ?>
    </header>

    <?php if ( have_comments() ) : ?>
        <ol class="tnt-resource-comments__list">
            <?php
            wp_list_comments(
                array(
                    'style'       => 'ol',
                    'short_ping'  => true,
                    'avatar_size' => 44,
                    'callback'    => 'tnt_render_resource_comment',
                )
            );
            ?>
        </ol>

        <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
            <nav class="tnt-resource-comments__pagination" aria-label="<?php esc_attr_e( 'Comments navigation', 'toolntip-core' ); ?>">
                <?php paginate_comments_links(); ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( comments_open() ) : ?>
        <div class="tnt-resource-comments__discussion">
            <?php if ( is_user_logged_in() ) : ?>
                <?php
                comment_form(
                    array(
                        'title_reply'          => esc_html__( 'Join the discussion', 'toolntip-core' ),
                        'title_reply_before'   => '<h3 class="tnt-resource-comments__form-title">',
                        'title_reply_after'    => '</h3>',
                        'label_submit'         => esc_html__( 'Post Comment', 'toolntip-core' ),
                        'class_submit'         => 'tnt-btn tnt-btn-primary',
                        'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Comment', 'toolntip-core' ) . '</label><textarea id="comment" name="comment" cols="45" rows="7" maxlength="65525" required></textarea></p>',
                        'comment_notes_before' => '',
                        'comment_notes_after'  => '',
                        'logged_in_as'         => '',
                    )
                );
                ?>
            <?php else : ?>
                <div class="tnt-resource-comments__auth">
                    <h3 class="tnt-resource-comments__form-title"><?php esc_html_e( 'Join the discussion', 'toolntip-core' ); ?></h3>
                    <p><?php esc_html_e( 'Sign in or create an account to participate.', 'toolntip-core' ); ?></p>
                    <div class="tnt-resource-comments__auth-actions">
                        <a class="tnt-btn tnt-btn-primary" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
                            <?php esc_html_e( 'Sign In', 'toolntip-core' ); ?>
                        </a>
                        <a class="tnt-btn tnt-btn-secondary" href="<?php echo esc_url( wp_registration_url() ); ?>">
                            <?php esc_html_e( 'Create Account', 'toolntip-core' ); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ( $comment_count < 1 ) : ?>
        <p class="tnt-resource-comments__closed"><?php esc_html_e( 'Comments are closed.', 'toolntip-core' ); ?></p>
    <?php endif; ?>
</section>
