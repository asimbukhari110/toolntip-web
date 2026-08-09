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

$button = $tool['use_tool'] ?? array();

$details_url = get_permalink( $tool['post_id'] );
?>

<article class="tnt-tool-card">

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
            <span class="tnt-tool-card__signal">
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

    </footer>

</article>
