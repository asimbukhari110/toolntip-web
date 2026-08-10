<?php
/**
 * Tool Community Reviews.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = ! empty( $tool['post_id'] ) ? (int) $tool['post_id'] : 0;

if ( $post_id <= 0 ) {
    return;
}

$community = ! empty( $tool['rating']['community'] )
    ? $tool['rating']['community']
    : tnt_get_tool_community_rating( $post_id );

$reviews = get_comments(
    array(
        'post_id'  => $post_id,
        'status'   => 'approve',
        'type'     => 'comment',
        'meta_key' => 'tnt_rating',
        'orderby'  => 'comment_date_gmt',
        'order'    => 'DESC',
        'number'   => 20,
    )
);
?>

<div id="reviews" class="tnt-tool-detail-anchor">

    <section class="tnt-tool-reviews">

        <div class="tnt-tool-reviews__header">

            <div>
                <h2 class="tnt-section-title">
                    <?php echo esc_html__( 'Community Reviews', 'toolntip-core' ); ?>
                </h2>

                <p class="tnt-tool-reviews__intro">
                    <?php echo esc_html__( 'Ratings and reviews from ToolNTip users.', 'toolntip-core' ); ?>
                </p>
            </div>

            <div class="tnt-tool-reviews__summary">

                <?php if ( ! empty( $community['count'] ) ) : ?>

                    <strong class="tnt-tool-reviews__score">
                        <?php echo esc_html( number_format_i18n( $community['value'], 1 ) ); ?>
                    </strong>

                    <div class="tnt-tool-reviews__stars" aria-hidden="true">
                        ★★★★★
                    </div>

                    <span class="tnt-tool-reviews__count">
                        <?php
                        printf(
                            esc_html( _n( '%s approved review', '%s approved reviews', $community['count'], 'toolntip-core' ) ),
                            esc_html( number_format_i18n( $community['count'] ) )
                        );
                        ?>
                    </span>

                <?php else : ?>

                    <strong class="tnt-tool-reviews__empty">
                        <?php echo esc_html__( 'No community ratings yet', 'toolntip-core' ); ?>
                    </strong>

                    <span class="tnt-tool-reviews__count">
                        <?php echo esc_html__( 'Be the first to review this tool.', 'toolntip-core' ); ?>
                    </span>

                <?php endif; ?>

            </div>

        </div>

        <?php if ( ! empty( $reviews ) ) : ?>

            <div class="tnt-tool-reviews__list">

                <?php foreach ( $reviews as $review ) : ?>

                    <?php
                    $review_rating = (int) get_comment_meta( $review->comment_ID, 'tnt_rating', true );
                    ?>

                    <article class="tnt-tool-review">

                        <header class="tnt-tool-review__header">

                            <div>
                                <strong class="tnt-tool-review__author">
                                    <?php echo esc_html( get_comment_author( $review ) ); ?>
                                </strong>

                                <time
                                    class="tnt-tool-review__date"
                                    datetime="<?php echo esc_attr( get_comment_date( DATE_W3C, $review ) ); ?>"
                                >
                                    <?php echo esc_html( get_comment_date( '', $review ) ); ?>
                                </time>
                            </div>

                            <span
                                class="tnt-tool-review__rating"
                                aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $review_rating, 'toolntip-core' ), $review_rating ) ); ?>"
                            >
                                <?php echo esc_html( str_repeat( '★', $review_rating ) ); ?>
                            </span>

                        </header>

                        <div class="tnt-tool-review__content">
                            <?php echo wp_kses_post( wpautop( $review->comment_content ) ); ?>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <div class="tnt-tool-reviews__form">

            <?php if ( comments_open( $post_id ) ) : ?>

                <?php
                ob_start();
                ?>

                <fieldset class="tnt-review-rating-field">
                    <legend>
                        <?php echo esc_html__( 'Your Rating', 'toolntip-core' ); ?>
                    </legend>

                    <div class="tnt-review-rating-options">
                        <?php for ( $star = 5; $star >= 1; $star-- ) : ?>
                            <label>
                                <input
                                    type="radio"
                                    name="tnt_rating"
                                    value="<?php echo esc_attr( $star ); ?>"
                                    required
                                >
                                <span>
                                    <?php
                                    printf(
                                        esc_html( _n( '%d star', '%d stars', $star, 'toolntip-core' ) ),
                                        esc_html( $star )
                                    );
                                    ?>
                                </span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <p class="comment-form-comment">
                    <label for="comment">
                        <?php echo esc_html__( 'Your Review', 'toolntip-core' ); ?>
                    </label>
					<?php
					wp_editor(
						'',
						'tnt_review_content',
						array(
							'textarea_name' => 'comment',
							'textarea_rows' => 8,
							'media_buttons' => false,
							'teeny'         => true,
							'quicktags'     => true,
							'tinymce'       => array(
								'toolbar1' => 'bold italic bullist numlist blockquote link unlink',
								'toolbar2' => '',
							),
						)
					);
					?>
                </p>

                <?php
                $comment_field = ob_get_clean();

                comment_form(
                    array(
                        'title_reply'          => esc_html__( 'Write a Review', 'toolntip-core' ),
                        'title_reply_before'   => '<h3 class="tnt-tool-reviews__form-title">',
                        'title_reply_after'    => '</h3>',
                        'label_submit'         => esc_html__( 'Submit Review', 'toolntip-core' ),
                        'class_submit'         => 'tnt-btn tnt-btn-primary',
                        'comment_field'        => $comment_field,
                        'comment_notes_after'  => '',
                        'logged_in_as'         => '',
                    ),
                    $post_id
                );
                ?>

            <?php else : ?>

                <p class="tnt-tool-reviews__closed">
                    <?php echo esc_html__( 'Reviews are currently closed for this tool.', 'toolntip-core' ); ?>
                </p>

            <?php endif; ?>

        </div>

    </section>

</div>
