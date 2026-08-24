<?php
/**
 * Single Resource Template.
 *
 * Canonical ToolNTip-owned shell for single Resource requests.
 *
 * WEB-007.4 / 4.6-B + 4.6-C + 4.6-D
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $resource = function_exists( 'tnt_get_resource_detail_data' )
        ? tnt_get_resource_detail_data( get_the_ID() )
        : null;

    if ( ! $resource ) {
        continue;
    }

    $published = (string) ( $resource['date']['published_machine'] ?? '' );
    $modified  = (string) ( $resource['date']['modified_machine'] ?? '' );
    $show_modified = $published && $modified && $published !== $modified;
    ?>
    <div class="tnt-resource-detail">
        <article <?php post_class( 'tnt-resource-detail__article' ); ?>>
            <div class="tnt-resource-detail__inner">
                <header class="tnt-resource-detail__identity">
                    <div class="tnt-resource-detail__eyebrow">
                        <?php if ( ! empty( $resource['type'][0]['name'] ) ) : ?>
                            <span class="tnt-resource-detail__type">
                                <?php echo esc_html( $resource['type'][0]['name'] ); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ( ! empty( $resource['featured'] ) ) : ?>
                            <span class="tnt-resource-detail__featured">
                                <?php esc_html_e( '★ Featured', 'toolntip-core' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="tnt-resource-detail__title">
                        <?php echo esc_html( $resource['title'] ); ?>
                    </h1>

                    <div class="tnt-resource-detail__meta" aria-label="<?php esc_attr_e( 'Resource information', 'toolntip-core' ); ?>">
                        <?php if ( ! empty( $resource['date']['published_display'] ) ) : ?>
                            <span class="tnt-resource-detail__meta-item">
                                <span class="tnt-resource-detail__meta-label"><?php esc_html_e( 'Published', 'toolntip-core' ); ?></span>
                                <time datetime="<?php echo esc_attr( $resource['date']['published_machine'] ); ?>">
                                    <?php echo esc_html( $resource['date']['published_display'] ); ?>
                                </time>
                            </span>
                        <?php endif; ?>

                        <?php if ( $show_modified && ! empty( $resource['date']['modified_display'] ) ) : ?>
                            <span class="tnt-resource-detail__meta-item">
                                <span class="tnt-resource-detail__meta-label"><?php esc_html_e( 'Updated', 'toolntip-core' ); ?></span>
                                <time datetime="<?php echo esc_attr( $resource['date']['modified_machine'] ); ?>">
                                    <?php echo esc_html( $resource['date']['modified_display'] ); ?>
                                </time>
                            </span>
                        <?php endif; ?>

                        <?php if ( ! empty( $resource['author']['name'] ) ) : ?>
                            <span class="tnt-resource-detail__meta-item">
                                <span class="tnt-resource-detail__meta-label"><?php esc_html_e( 'By', 'toolntip-core' ); ?></span>
                                <?php if ( ! empty( $resource['author']['url'] ) ) : ?>
                                    <a class="tnt-resource-detail__author" href="<?php echo esc_url( $resource['author']['url'] ); ?>">
                                        <?php echo esc_html( $resource['author']['name'] ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="tnt-resource-detail__author"><?php echo esc_html( $resource['author']['name'] ); ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ( ! empty( $resource['media']['id'] ) ) : ?>
                    <figure class="tnt-resource-detail__featured-media">
                        <?php
                        echo wp_get_attachment_image(
                            (int) $resource['media']['id'],
                            'full',
                            false,
                            array(
                                'class'    => 'tnt-resource-detail__featured-image',
                                'loading'  => 'eager',
                                'decoding' => 'async',
                            )
                        );
                        ?>
                    </figure>
                <?php endif; ?>

                <?php if ( ! empty( $resource['topics'] ) || ! empty( $resource['tags'] ) ) : ?>
                    <nav class="tnt-resource-detail__taxonomy" aria-label="<?php esc_attr_e( 'Resource topics and tags', 'toolntip-core' ); ?>">
                        <?php if ( ! empty( $resource['topics'] ) ) : ?>
                            <div class="tnt-resource-detail__taxonomy-group">
                                <span class="tnt-resource-detail__taxonomy-label"><?php esc_html_e( 'Topics', 'toolntip-core' ); ?></span>
                                <div class="tnt-resource-detail__chips">
                                    <?php foreach ( $resource['topics'] as $topic ) : ?>
                                        <a class="tnt-resource-detail__chip" href="<?php echo esc_url( $topic['url'] ); ?>">
                                            <?php echo esc_html( $topic['name'] ); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $resource['tags'] ) ) : ?>
                            <div class="tnt-resource-detail__taxonomy-group">
                                <span class="tnt-resource-detail__taxonomy-label"><?php esc_html_e( 'Tags', 'toolntip-core' ); ?></span>
                                <div class="tnt-resource-detail__chips">
                                    <?php foreach ( $resource['tags'] as $tag ) : ?>
                                        <a class="tnt-resource-detail__chip tnt-resource-detail__chip--tag" href="<?php echo esc_url( $tag['url'] ); ?>">
                                            <?php echo esc_html( $tag['name'] ); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

                <?php
                $resource_after_hero_monetization = function_exists( 'tnt_render_monetization_placement' )
                    ? tnt_render_monetization_placement( 'resource-after-hero', array(), array( 'variant' => 'standalone' ) )
                    : '';

                if ( '' !== $resource_after_hero_monetization ) :
                    ?>
                    <div class="tnt-resource-detail__monetization tnt-resource-detail__monetization--after-hero">
                        <?php echo $resource_after_hero_monetization; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <?php
                endif;

                $related_tools = function_exists( 'tnt_get_resource_detail_related_tools' )
                    ? tnt_get_resource_detail_related_tools( $resource['id'], 3 )
                    : array();

                $related_resources = function_exists( 'tnt_get_resource_detail_related_resources' )
                    ? tnt_get_resource_detail_related_resources( $resource['id'], 3 )
                    : array();
                ?>

                <div class="tnt-resource-detail__reading-layout<?php echo ( empty( $related_tools ) && empty( $related_resources ) ) ? ' tnt-resource-detail__reading-layout--single' : ''; ?>">
                    <div class="tnt-resource-detail__reading-main">
                        <div class="tnt-resource-detail__content">
                            <?php
                            /*
                             * Canonical editorial-body boundary. Do not replace with
                             * direct post_content output: the_content() preserves the
                             * WordPress rendering pipeline required by Gutenberg,
                             * shortcodes, embeds and optional compatible builders such
                             * as Elementor. ToolNTip continues to own the outer shell.
                             */
                            the_content();
                            ?>
                        </div>
                    </div>

                    <?php if ( ! empty( $related_tools ) || ! empty( $related_resources ) ) : ?>
                        <aside class="tnt-resource-context" aria-label="<?php esc_attr_e( 'Related content', 'toolntip-core' ); ?>">
                            <?php if ( ! empty( $related_tools ) ) : ?>
                                <section class="tnt-context-recommendations" aria-labelledby="tnt-resource-related-tools-title">
                                    <header class="tnt-context-recommendations__header">
                                        <span class="tnt-context-recommendations__icon" aria-hidden="true">&#8984;</span>
                                        <h2 id="tnt-resource-related-tools-title" class="tnt-context-recommendations__title">
                                            <?php esc_html_e( 'Related Tools', 'toolntip-core' ); ?>
                                        </h2>
                                    </header>

                                    <div class="tnt-context-recommendations__list">
                                        <?php foreach ( $related_tools as $related_tool ) : ?>
                                            <?php
                                            $related_url = get_permalink( $related_tool['post_id'] );
                                            $related_rating = ! empty( $related_tool['rating']['value'] ) ? (float) $related_tool['rating']['value'] : 0;
                                            $related_category = ! empty( $related_tool['categories'][0]['name'] ) ? $related_tool['categories'][0]['name'] : '';
                                            ?>
                                            <a class="tnt-context-item" href="<?php echo esc_url( $related_url ); ?>">
                                                <span class="tnt-context-item__media tnt-context-item__media--tool">
                                                    <?php tnt_render( 'logo', $related_tool ); ?>
                                                </span>
                                                <span class="tnt-context-item__content">
                                                    <span class="tnt-context-item__name"><?php echo esc_html( $related_tool['title'] ); ?></span>
                                                    <?php if ( $related_category ) : ?>
                                                        <span class="tnt-context-item__meta"><?php echo esc_html( $related_category ); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if ( $related_rating > 0 ) : ?>
                                                    <span class="tnt-context-item__rating" aria-label="<?php echo esc_attr( sprintf( 'Editor rating %.1f out of 5', $related_rating ) ); ?>">
                                                        <span aria-hidden="true">&#9733;</span><?php echo esc_html( number_format_i18n( $related_rating, 1 ) ); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>

                                    <a class="tnt-context-recommendations__more" href="<?php echo esc_url( home_url( '/tools/' ) ); ?>">
                                        <span><?php esc_html_e( 'View More Tools', 'toolntip-core' ); ?></span><span aria-hidden="true">&#8594;</span>
                                    </a>
                                </section>
                            <?php endif; ?>

                            <?php if ( ! empty( $related_resources ) ) : ?>
                                <section class="tnt-context-recommendations" aria-labelledby="tnt-resource-related-resources-title">
                                    <header class="tnt-context-recommendations__header">
                                        <span class="tnt-context-recommendations__icon" aria-hidden="true">&#9776;</span>
                                        <h2 id="tnt-resource-related-resources-title" class="tnt-context-recommendations__title">
                                            <?php esc_html_e( 'Related Resources', 'toolntip-core' ); ?>
                                        </h2>
                                    </header>

                                    <div class="tnt-context-recommendations__list">
                                        <?php foreach ( $related_resources as $related_resource ) : ?>
                                            <?php
                                            $resource_type = ! empty( $related_resource['type']['name'] ) ? $related_resource['type']['name'] : '';
                                            $resource_icon = ! empty( $related_resource['icon']['url'] ) ? $related_resource['icon']['url'] : '';
                                            $resource_icon_alt = ! empty( $related_resource['icon']['alt'] ) ? $related_resource['icon']['alt'] : $related_resource['title'];
                                            ?>
                                            <a class="tnt-context-item" href="<?php echo esc_url( $related_resource['permalink'] ); ?>">
                                                <span class="tnt-context-item__media tnt-context-item__media--resource">
                                                    <?php if ( $resource_icon ) : ?>
                                                        <img src="<?php echo esc_url( $resource_icon ); ?>" alt="<?php echo esc_attr( $resource_icon_alt ); ?>" loading="lazy" decoding="async" />
                                                    <?php endif; ?>
                                                </span>
                                                <span class="tnt-context-item__content">
                                                    <span class="tnt-context-item__name"><?php echo esc_html( $related_resource['title'] ); ?></span>
                                                    <?php if ( $resource_type ) : ?>
                                                        <span class="tnt-context-item__meta"><?php echo esc_html( $resource_type ); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>

                                    <a class="tnt-context-recommendations__more" href="<?php echo esc_url( home_url( '/resources/' ) ); ?>">
                                        <span><?php esc_html_e( 'View More Resources', 'toolntip-core' ); ?></span><span aria-hidden="true">&#8594;</span>
                                    </a>
                                </section>
                            <?php endif; ?>

                            <?php
                            $resource_sidebar_monetization = function_exists( 'tnt_render_monetization_placement' )
                                ? tnt_render_monetization_placement( 'resource-sidebar', array(), array( 'variant' => 'standalone' ) )
                                : '';
                            if ( '' !== $resource_sidebar_monetization ) :
                                ?>
                                <div class="tnt-resource-detail__monetization tnt-resource-detail__monetization--sidebar">
                                    <?php echo $resource_sidebar_monetization; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>
                            <?php endif; ?>
                        </aside>
                    <?php endif; ?>
                </div>

                <?php
                $resource_before_comments_monetization = function_exists( 'tnt_render_monetization_placement' )
                    ? tnt_render_monetization_placement( 'resource-before-comments', array(), array( 'variant' => 'standalone' ) )
                    : '';
                if ( '' !== $resource_before_comments_monetization ) :
                    ?>
                    <div class="tnt-resource-detail__monetization tnt-resource-detail__monetization--before-comments">
                        <?php echo $resource_before_comments_monetization; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <?php
                endif;

                // Native WordPress comments with ToolNTip Resource presentation.
                if ( comments_open() || get_comments_number() ) {
                    comments_template();
                }
                ?>
            </div>
        </article>
    </div>
    <?php
endwhile;

get_footer();
