<?php
/**
 * Tool Card Component.
 *
 * Compact summary representation of a Tool.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tagline = $tool['hero']['tagline'] ?? '';

$rating = $tool['rating'] ?? array();

$rating_value = isset( $rating['value'] )
    ? (float) $rating['value']
    : 0;

$primary_category = ! empty( $tool['categories'][0]['name'] )
    ? $tool['categories'][0]['name']
    : '';

$platform_label = ! empty( $tool['platform'] )
    ? implode( ', ', $tool['platform'] )
    : '';


$features = ! empty( $tool['features'] )
    ? array_slice( $tool['features'], 0, 3 )
    : array();

$actions = ! empty( $tool['actions'] ) && is_array( $tool['actions'] )
    ? $tool['actions']
    : array();

$button = $actions['use_tool'] ?? ( $tool['use_tool'] ?? array() );
$affiliate = $actions['affiliate'] ?? array();
$details_url = ! empty( $actions['details']['url'] )
    ? $actions['details']['url']
    : get_permalink( $tool['post_id'] );
?>

<article class="tnt-tool-card<?php echo ! empty( $tool['featured'] ) ? ' tnt-tool-card--featured' : ''; ?>">

    <?php if ( ! empty( $tool['featured'] ) ) : ?>
        <div class="tnt-tool-card__featured-ribbon" role="status" aria-label="<?php echo esc_attr__( 'Featured Tool', 'toolntip-core' ); ?>">
            <span class="tnt-tool-card__featured-ribbon-star" aria-hidden="true">&#9733;</span>
            <span><?php echo esc_html__( 'Featured', 'toolntip-core' ); ?></span>
        </div>
    <?php endif; ?>

    <header class="tnt-tool-card__header">

        <div class="tnt-tool-card__logo">
            <?php tnt_render( 'logo', $tool ); ?>
        </div>

        <div class="tnt-tool-card__identity">

            <h3 class="tnt-tool-card__title">
                <a href="<?php echo esc_url( $details_url ); ?>">
                    <?php echo esc_html( $tool['title'] ); ?>
                </a>
            </h3>

            <?php if ( ! empty( $tagline ) ) : ?>
                <p class="tnt-tool-card__tagline">
                    <?php echo esc_html( $tagline ); ?>
                </p>
            <?php endif; ?>

        </div>

    </header>

    <div class="tnt-tool-card__signals">

        <?php if ( $rating_value > 0 ) : ?>
            <span class="tnt-tool-card__signal tnt-tool-card__signal--rating">
                Rating:
                <?php echo esc_html( number_format_i18n( $rating_value, 1 ) ); ?>
            </span>
        <?php endif; ?>

        <?php if ( ! empty( $tool['pricing'] ) ) : ?>
            <span class="tnt-tool-card__signal">
                <?php echo esc_html( $tool['pricing'] ); ?>
            </span>
        <?php endif; ?>

        <?php if ( ! empty( $platform_label ) ) : ?>
			<span class="tnt-tool-card__signal">
				<?php echo esc_html( $platform_label ); ?>
			</span>
		<?php endif; ?>

        <?php if ( ! empty( $primary_category ) ) : ?>
            <span class="tnt-tool-card__signal">
                <?php echo esc_html( $primary_category ); ?>
            </span>
        <?php endif; ?>

    </div>

    <?php if ( ! empty( $features ) ) : ?>

        <ul class="tnt-tool-card__features">

            <?php foreach ( $features as $feature ) : ?>

                <li>
                    <?php echo esc_html( $feature ); ?>
                </li>

            <?php endforeach; ?>

        </ul>

    <?php endif; ?>

    <footer class="tnt-tool-card__actions">


		<?php if ( ! empty( $button ) ) : ?>

			<?php if ( ! empty( $button['disabled'] ) ) : ?>

				<span
					class="tnt-btn tnt-btn-primary is-disabled"
					aria-disabled="true"
				>
					<?php echo esc_html( $button['label'] ); ?>
				</span>

			<?php else : ?>

				<a
					href="<?php echo esc_url( $button['url'] ); ?>"
					class="tnt-btn tnt-btn-primary"
					<?php echo ! empty( $button['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
				>
					<?php echo esc_html( $button['label'] ); ?>
				</a>

			<?php endif; ?>

		<?php endif; ?>


        <a
            href="<?php echo esc_url( $details_url ); ?>"
            class="tnt-btn tnt-btn-secondary"
        >
            View Details
        </a>

        <?php if ( ! empty( $affiliate['url'] ) ) : ?>
            <a
                href="<?php echo esc_url( $affiliate['url'] ); ?>"
                class="tnt-btn tnt-btn-secondary tnt-btn-partner"
                <?php echo ! empty( $affiliate['external'] ) ? 'target="_blank"' : ''; ?>
                rel="<?php echo esc_attr( $affiliate['rel'] ?? 'sponsored nofollow' ); ?>"
            >
                <?php echo esc_html( $affiliate['label'] ); ?>
            </a>
        <?php endif; ?>

    </footer>

</article>
