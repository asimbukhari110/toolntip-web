<?php
/**
 * Similar Tools
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Get related tools.
 */
$related_tools = tnt_get_related_tools( $tool );

/*
 * Nothing to show.
 */
if ( empty( $related_tools ) ) {
    return;
}
?>
<div id="similar-tools" class="tnt-tool-detail-anchor">
	<section class="tnt-similar-tools">

		<h2 class="tnt-similar-tools__title">
			<?php echo esc_html__( 'Similar Tools', 'toolntip-core' ); ?>
		</h2>

		<div class="tnt-similar-tools__list">

			<?php foreach ( $related_tools as $related_tool ) : ?>

				<?php
				$related_url = get_permalink( $related_tool['post_id'] );

				$related_rating = ! empty( $related_tool['rating']['value'] )
					? (float) $related_tool['rating']['value']
					: 0;

				$related_category = ! empty( $related_tool['categories'][0]['name'] )
					? $related_tool['categories'][0]['name']
					: '';
				?>

				<a
					class="tnt-similar-tool"
					href="<?php echo esc_url( $related_url ); ?>"
				>

					<span class="tnt-similar-tool__logo">
						<?php tnt_render( 'logo', $related_tool ); ?>
					</span>

					<span class="tnt-similar-tool__content">

						<span class="tnt-similar-tool__name">
							<?php echo esc_html( $related_tool['title'] ); ?>
						</span>

						<?php if ( ! empty( $related_category ) ) : ?>

							<span class="tnt-similar-tool__category">
								<?php echo esc_html( $related_category ); ?>
							</span>

						<?php endif; ?>

					</span>

					<?php if ( $related_rating > 0 ) : ?>

						<span class="tnt-similar-tool__rating">

							<span aria-hidden="true">★</span>

							<?php
							echo esc_html(
								number_format_i18n( $related_rating, 1 )
							);
							?>

						</span>

					<?php endif; ?>

				</a>

			<?php endforeach; ?>

		</div>

		<a
			class="tnt-similar-tools__more"
			href="<?php echo esc_url( home_url( '/tools/' ) ); ?>"
		>
			<?php echo esc_html__( 'View More Tools', 'toolntip-core' ); ?>

			<span aria-hidden="true">→</span>
		</a>

	</section>
</div>