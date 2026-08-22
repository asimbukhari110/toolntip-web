<?php
/**
 * Resource Card Component.
 *
 * Compact summary representation of a Resource.
 *
 * WEB-007.4 / 4.4C.2
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * tnt_render() currently exposes component data through the historical
 * $tool variable. Keep that renderer contract unchanged during WEB-007.4.
 */
$resource = is_array( $tool ) ? $tool : array();

if ( empty( $resource['post_id'] ) || empty( $resource['permalink'] ) ) {
    return;
}

$type          = ! empty( $resource['type'] ) && is_array( $resource['type'] )
    ? $resource['type']
    : array();

$topics        = ! empty( $resource['topics'] ) && is_array( $resource['topics'] )
    ? $resource['topics']
    : array();

$tags          = ! empty( $resource['tags'] ) && is_array( $resource['tags'] )
    ? $resource['tags']
    : array();

$primary_topic = ! empty( $topics[0] ) && is_array( $topics[0] )
    ? $topics[0]
    : array();

$image = ! empty( $resource['featured_image'] ) && is_array( $resource['featured_image'] )
    ? $resource['featured_image']
    : array();

$date = ! empty( $resource['date'] ) && is_array( $resource['date'] )
    ? $resource['date']
    : array();
?>

<article class="tnt-resource-card<?php echo ! empty( $resource['featured'] ) ? ' tnt-resource-card--featured' : ''; ?>">

    <?php if ( ! empty( $resource['featured'] ) ) : ?>
        <div class="tnt-resource-card__featured-ribbon" role="status" aria-label="<?php echo esc_attr__( 'Featured Resource', 'toolntip-core' ); ?>">
            <span class="tnt-resource-card__featured-ribbon-star" aria-hidden="true">&#9733;</span>
            <span><?php echo esc_html__( 'Featured', 'toolntip-core' ); ?></span>
        </div>
    <?php endif; ?>

    <a
        class="tnt-resource-card__media"
        href="<?php echo esc_url( $resource['permalink'] ); ?>"
        aria-label="<?php echo esc_attr( $resource['title'] ?? '' ); ?>"
    >
        <?php if ( ! empty( $image['url'] ) ) : ?>
            <img
                class="tnt-resource-card__image"
                src="<?php echo esc_url( $image['url'] ); ?>"
                alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>"
                loading="lazy"
            >
        <?php else : ?>
            <span class="tnt-resource-card__media-fallback" aria-hidden="true"></span>
        <?php endif; ?>
    </a>

    <div class="tnt-resource-card__body">

        <?php if ( ! empty( $type['name'] ) ) : ?>
            <div class="tnt-resource-card__type">
                <?php if ( ! empty( $type['url'] ) ) : ?>
                    <a href="<?php echo esc_url( $type['url'] ); ?>">
                        <?php echo esc_html( $type['name'] ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( $type['name'] ); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h3 class="tnt-resource-card__title">
            <a href="<?php echo esc_url( $resource['permalink'] ); ?>">
                <?php echo esc_html( $resource['title'] ?? '' ); ?>
            </a>
        </h3>

        <?php if ( ! empty( $resource['excerpt'] ) ) : ?>
            <p class="tnt-resource-card__excerpt">
                <?php echo esc_html( $resource['excerpt'] ); ?>
            </p>
        <?php endif; ?>

        <?php if ( ! empty( $primary_topic['name'] ) ) : ?>
            <div class="tnt-resource-card__topic">
                <?php if ( ! empty( $primary_topic['url'] ) ) : ?>
                    <a href="<?php echo esc_url( $primary_topic['url'] ); ?>">
                        <?php echo esc_html( $primary_topic['name'] ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( $primary_topic['name'] ); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $tags ) ) : ?>
            <div
                class="tnt-resource-card__tags"
                aria-label="<?php echo esc_attr__( 'Resource tags', 'toolntip-core' ); ?>"
            >
                <?php foreach ( $tags as $tag ) : ?>
                    <?php if ( empty( $tag['name'] ) ) : ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <?php if ( ! empty( $tag['url'] ) ) : ?>
                        <a
                            class="tnt-resource-card__tag"
                            href="<?php echo esc_url( $tag['url'] ); ?>"
                        >
                            <?php echo esc_html( $tag['name'] ); ?>
                        </a>
                    <?php else : ?>
                        <span class="tnt-resource-card__tag">
                            <?php echo esc_html( $tag['name'] ); ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <footer class="tnt-resource-card__footer">

            <?php if ( ! empty( $date['display'] ) ) : ?>
                <time
                    class="tnt-resource-card__date"
                    datetime="<?php echo esc_attr( $date['machine'] ?? '' ); ?>"
                >
                    <?php echo esc_html( $date['display'] ); ?>
                </time>
            <?php endif; ?>

            <a
                class="tnt-btn tnt-btn-secondary tnt-resource-card__action"
                href="<?php echo esc_url( $resource['permalink'] ); ?>"
            >
                <?php echo esc_html__( 'Read Resource', 'toolntip-core' ); ?>
            </a>

        </footer>

    </div>

</article>
