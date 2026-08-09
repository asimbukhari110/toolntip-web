<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<?php if ( ! empty( $tool['categories'] ) || ! empty( $tool['tags'] ) ) : ?>

<div class="tnt-tool-taxonomies">

    <?php if ( ! empty( $tool['categories'] ) ) : ?>

        <div class="tnt-tool-categories">

            <?php foreach ( $tool['categories'] as $category ) : ?>

                <span class="tnt-category">

                    <?php echo esc_html( $category->name ); ?>

                </span>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php if ( ! empty( $tool['tags'] ) ) : ?>

        <div class="tnt-tool-tags">

            <?php foreach ( $tool['tags'] as $tag ) : ?>

                <span class="tnt-tag">

                    #<?php echo esc_html( $tag->name ); ?>

                </span>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php endif; ?>