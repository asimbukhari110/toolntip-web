<?php
/**
 * Tool About Component.
 *
 * DEF-002: keeps the existing about_this_tool field as the single source of
 * truth while allowing introductory content to sit beside Key Features and
 * extended editorial content to span the complete overview width.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['about'] ) ) {
    return;
}

$about_parts    = tnt_split_tool_about_content( $tool['about'] );
$about_intro    = $about_parts['intro'] ?? '';
$about_extended = $about_parts['extended'] ?? '';
?>

<?php if ( $about_intro !== '' ) : ?>

    <section class="tnt-section tnt-tool-about">

        <header class="tnt-overview-panel__heading">
            <span class="tnt-overview-panel__icon tnt-overview-panel__icon--info" aria-hidden="true">
                i
            </span>

            <h2 class="tnt-section-title">
                <?php echo esc_html__( 'About This Tool', 'toolntip-core' ); ?>
            </h2>
        </header>

        <div class="tnt-about-content">
            <?php echo wp_kses_post( $about_intro ); ?>
        </div>

    </section>

<?php endif; ?>

<?php if ( $about_extended !== '' ) : ?>

    <section class="tnt-tool-about-extended" aria-label="<?php echo esc_attr__( 'More about this tool', 'toolntip-core' ); ?>">
        <div class="tnt-about-content tnt-about-content--extended">
            <?php echo wp_kses_post( $about_extended ); ?>
        </div>
    </section>

<?php endif; ?>
