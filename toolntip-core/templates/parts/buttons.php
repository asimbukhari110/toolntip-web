<?php
/**
 * Tool Buttons.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$button           = $tool['use_tool'];
$official_website = ! empty( $tool['official_website'] )
    ? $tool['official_website']
    : '';

/**
 * Normalize a URL for duplicate CTA comparison only.
 *
 * This does not alter the URL that is rendered. It simply prevents
 * equivalent Use Tool and Official Website destinations from being
 * presented as two separate actions.
 *
 * @param string $url URL to normalize.
 * @return string
 */
$normalize_url = static function ( $url ) {

    if ( empty( $url ) ) {
        return '';
    }

    $url   = trim( $url );
    $parts = wp_parse_url( $url );

    if ( empty( $parts['host'] ) ) {
        return untrailingslashit( strtolower( $url ) );
    }

    $host = strtolower( preg_replace( '/^www\./i', '', $parts['host'] ) );
    $path = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';

    return $host . $path;
};

$primary_url    = ! empty( $button['url'] ) ? $button['url'] : '';
$same_cta_url   = $primary_url && $official_website
    ? $normalize_url( $primary_url ) === $normalize_url( $official_website )
    : false;
$primary_label  = $same_cta_url
    ? __( 'Visit Website', 'toolntip-core' )
    : $button['label'];
?>

<div class="tnt-tool-actions">

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
            <?php echo esc_html( $primary_label ); ?>
        </a>

    <?php endif; ?>

    <?php if ( $official_website && ! $same_cta_url ) : ?>

        <a
            href="<?php echo esc_url( $official_website ); ?>"
            class="tnt-btn tnt-btn-secondary"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?php echo esc_html__( 'Official Website', 'toolntip-core' ); ?>
        </a>

    <?php endif; ?>

</div>
