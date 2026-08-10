<?php
/**
 * Tool Information.
 *
 * Displays normalized Tool metadata in the Tool Detail sidebar.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$platform_label = ! empty( $tool['platform'] )
    ? implode( ', ', $tool['platform'] )
    : '';

$category_names = array();

if ( ! empty( $tool['categories'] ) ) {

    foreach ( $tool['categories'] as $category ) {

        if ( ! empty( $category['name'] ) ) {
            $category_names[] = $category['name'];
        }

    }

}

$category_label = ! empty( $category_names )
    ? implode( ', ', $category_names )
    : '';

$tag_names = array();

if ( ! empty( $tool['tags'] ) ) {

    foreach ( $tool['tags'] as $tag ) {

        if ( ! empty( $tag['name'] ) ) {
            $tag_names[] = $tag['name'];
        }

    }

}

$tag_label = ! empty( $tag_names )
    ? implode( ', ', $tag_names )
    : '';
?>

<section class="tnt-tool-information">

    <h2 class="tnt-tool-information__title">
        Tool Information
    </h2>

    <dl class="tnt-tool-information__list">

        <?php if ( ! empty( $tool['developer'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Developer</dt>
                <dd><?php echo esc_html( $tool['developer'] ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tool['pricing'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Pricing</dt>
                <dd><?php echo esc_html( $tool['pricing'] ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $platform_label ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Platform</dt>
                <dd><?php echo esc_html( $platform_label ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tool['tool_type'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Tool Type</dt>
                <dd><?php echo esc_html( $tool['tool_type'] ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $category_label ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Category</dt>
                <dd><?php echo esc_html( $category_label ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tag_label ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Tags</dt>
                <dd><?php echo esc_html( $tag_label ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tool['last_verified'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt>Last Verified</dt>
                <dd><?php echo esc_html( $tool['last_verified'] ); ?></dd>
            </div>

        <?php endif; ?>

    </dl>

</section>