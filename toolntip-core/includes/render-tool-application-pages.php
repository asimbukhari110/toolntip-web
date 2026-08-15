<?php
/**
 * Tool Application Page Composition Renderers.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_render_tool_promo_markup( $promo, $placement, $variant = 'standalone' ) {
    if ( empty( $promo ) || ! is_array( $promo ) ) {
        return '';
    }

    $promo_type = ! empty( $promo['type'] ) ? sanitize_html_class( $promo['type'] ) : 'house-tool';
    $placement_class = sanitize_html_class( $placement );
    $logo = isset( $promo['logo'] ) && is_array( $promo['logo'] ) ? $promo['logo'] : array();
    $logo_markup = '';

    if ( ! empty( $logo['url'] ) && empty( $logo['placeholder'] ) ) {
        $width = absint( $logo['width'] ?? 0 );
        $height = absint( $logo['height'] ?? 0 );
        $size_attributes = '';

        if ( $width > 0 ) {
            $size_attributes .= ' width="' . esc_attr( (string) $width ) . '"';
        }
        if ( $height > 0 ) {
            $size_attributes .= ' height="' . esc_attr( (string) $height ) . '"';
        }

        $logo_markup = sprintf(
            '<img class="tnt-tool-promo__logo" src="%1$s" alt="%2$s"%3$s loading="lazy" decoding="async">',
            esc_url( $logo['url'] ),
            esc_attr( (string) ( $logo['alt'] ?? '' ) ),
            $size_attributes
        );
    }

    if ( 'hero' === $variant ) {
        $output = '<aside class="tnt-tool-hero__promo tnt-tool-hero__promo--' . esc_attr( $promo_type ) . '" aria-label="' . esc_attr__( 'Promotional content', 'toolntip-core' ) . '">';

        if ( ! empty( $promo['label'] ) ) {
            $output .= '<span class="tnt-tool-hero__promo-label">' . esc_html( $promo['label'] ) . '</span>';
        }

        $output .= '<div class="tnt-tool-hero__promo-body">';

        if ( '' !== $logo_markup ) {
            $output .= '<div class="tnt-tool-hero__promo-mark tnt-tool-hero__promo-mark--image">' . $logo_markup . '</div>';
        } else {
            $output .= '<div class="tnt-tool-hero__promo-mark" aria-hidden="true">TNT</div>';
        }

        if ( ! empty( $promo['eyebrow'] ) ) {
            $output .= '<span class="tnt-tool-hero__promo-eyebrow">' . esc_html( $promo['eyebrow'] ) . '</span>';
        }
        if ( ! empty( $promo['title'] ) ) {
            $output .= '<strong class="tnt-tool-hero__promo-title">' . esc_html( $promo['title'] ) . '</strong>';
        }
        if ( ! empty( $promo['description'] ) ) {
            $output .= '<p class="tnt-tool-hero__promo-description">' . esc_html( $promo['description'] ) . '</p>';
        }

        $output .= '</div>';

        if ( ! empty( $promo['url'] ) && ! empty( $promo['cta_label'] ) ) {
            $output .= '<a class="tnt-tool-hero__promo-cta" href="' . esc_url( $promo['url'] ) . '"';
            if ( ! empty( $promo['external'] ) ) {
                $output .= ' target="_blank" rel="noopener noreferrer"';
            }
            $output .= '><span>' . esc_html( $promo['cta_label'] ) . '</span><span aria-hidden="true">&#8594;</span></a>';
        }

        $output .= '</aside>';
        return $output;
    }

    $output = '<aside class="tnt-tool-promo tnt-tool-promo--' . esc_attr( $placement_class ) . ' tnt-tool-promo--type-' . esc_attr( $promo_type ) . '" aria-label="' . esc_attr__( 'Promotional content', 'toolntip-core' ) . '">';

    if ( ! empty( $promo['label'] ) ) {
        $output .= '<span class="tnt-tool-promo__label">' . esc_html( $promo['label'] ) . '</span>';
    }

    $output .= '<div class="tnt-tool-promo__body">';
    if ( '' !== $logo_markup ) {
        $output .= $logo_markup;
    }
    if ( ! empty( $promo['eyebrow'] ) ) {
        $output .= '<span class="tnt-tool-promo__eyebrow">' . esc_html( $promo['eyebrow'] ) . '</span>';
    }
    if ( ! empty( $promo['title'] ) ) {
        $output .= '<strong class="tnt-tool-promo__title">' . esc_html( $promo['title'] ) . '</strong>';
    }
    if ( ! empty( $promo['description'] ) ) {
        $output .= '<p class="tnt-tool-promo__description">' . esc_html( $promo['description'] ) . '</p>';
    }
    $output .= '</div>';

    if ( ! empty( $promo['url'] ) && ! empty( $promo['cta_label'] ) ) {
        $output .= '<a class="tnt-tool-promo__cta" href="' . esc_url( $promo['url'] ) . '"';
        if ( ! empty( $promo['external'] ) ) {
            $output .= ' target="_blank" rel="noopener noreferrer"';
        }
        $output .= '><span>' . esc_html( $promo['cta_label'] ) . '</span><span aria-hidden="true">&#8594;</span></a>';
    }

    $output .= '</aside>';
    return $output;
}

function tnt_render_monetization_placement( $placement, $tool = array(), $args = array() ) {
    $placement = sanitize_key( $placement );
    if ( '' === $placement ) {
        return '';
    }

    $args = wp_parse_args( $args, array( 'variant' => 'standalone' ) );
    $payload = tnt_get_monetization_placement( $placement, $tool );

    if ( empty( $payload ) || ! is_array( $payload ) ) {
        return '';
    }

    if ( 'tool' === ( $payload['provider'] ?? '' ) ) {
        return tnt_render_tool_promo_markup( $payload, $placement, sanitize_key( $args['variant'] ) );
    }

    if ( 'custom' === ( $payload['provider'] ?? '' ) && ! empty( $payload['code'] ) ) {
        $class = 'tnt-monetization tnt-monetization--' . sanitize_html_class( $placement );
        if ( 'hero' === sanitize_key( $args['variant'] ) ) {
            $class .= ' tnt-monetization--hero';
        }

        return '<div class="' . esc_attr( $class ) . '">' . $payload['code'] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    return '';
}

function tnt_render_tool_promo( $placement, $tool = array() ) {
    $payload = tnt_get_tool_promo_placement( $placement, $tool );
    return empty( $payload ) ? '' : tnt_render_tool_promo_markup( $payload, $placement, 'standalone' );
}

function tnt_render_internal_tool_top( $tool, $args = array() ) {
    if ( ! $tool instanceof WP_Post ) {
        return '';
    }

    $icon = tnt_render_tool_shell_icon( $tool );
    $title = trim( (string) get_the_title( $tool ) );
    $tagline = tnt_get_tool_shell_tagline( $tool );
    $meta = tnt_render_tool_shell_meta( $tool );
    $hero_image = tnt_get_tool_shell_hero_image();
    $monetization = tnt_render_monetization_placement( 'internal-hero', $tool, array( 'variant' => 'hero' ) );

    if ( '' === $icon && '' === $title && '' === $tagline && '' === $meta && '' === $hero_image && '' === $monetization ) {
        return '';
    }

    $output = '<section class="tnt-internal-tool-top"><div class="tnt-internal-tool-top__identity">';
    if ( '' !== $icon ) {
        $output .= '<div class="tnt-internal-tool-top__icon">' . $icon . '</div>';
    }
    $output .= '<div class="tnt-internal-tool-top__content">';
    if ( '' !== $title ) {
        $output .= '<h1 class="tnt-internal-tool-top__title">' . esc_html( $title ) . '</h1>';
    }
    if ( '' !== $tagline ) {
        $output .= '<p class="tnt-internal-tool-top__tagline">' . esc_html( $tagline ) . '</p>';
    }
    if ( '' !== $meta ) {
        $output .= '<div class="tnt-internal-tool-top__meta">' . $meta . '</div>';
    }
    $output .= '</div></div>';
    if ( '' !== $hero_image ) {
        $output .= '<div class="tnt-internal-tool-top__hero">' . $hero_image . '</div>';
    }
    if ( '' !== $monetization ) {
        $output .= '<div class="tnt-internal-tool-top__monetization">' . $monetization . '</div>';
    }
    $output .= '</section>';

    return $output;
}

function tnt_render_internal_tool_bottom( $tool, $args = array() ) {
    if ( ! $tool instanceof WP_Post ) {
        return '';
    }

    $sections = array();
    $monetization = tnt_render_monetization_placement( 'internal-after-app', $tool );
    if ( '' !== $monetization ) {
        $sections[] = '<div class="tnt-internal-tool-bottom__promo">' . $monetization . '</div>';
    }

    $features = tnt_render_tool_shell_list( tnt_get_tool_features( $tool ), 'tnt-tool-features' );
    if ( '' !== $features ) {
        $sections[] = '<section class="tnt-internal-tool-section tnt-internal-tool-section--features"><h2 class="tnt-internal-tool-section__title">' . esc_html__( 'Features', 'toolntip-core' ) . '</h2>' . $features . '</section>';
    }

    $pros = tnt_render_tool_shell_list( tnt_get_tool_pros( $tool ), 'tnt-tool-pros' );
    $cons = tnt_render_tool_shell_list( tnt_get_tool_cons( $tool ), 'tnt-tool-cons' );
    if ( '' !== $pros || '' !== $cons ) {
        $evaluation = '<section class="tnt-internal-tool-section tnt-internal-tool-section--evaluation"><h2 class="tnt-internal-tool-section__title">' . esc_html__( 'Pros & Cons', 'toolntip-core' ) . '</h2><div class="tnt-internal-tool-evaluation">';
        if ( '' !== $pros ) {
            $evaluation .= '<div class="tnt-internal-tool-evaluation__pros"><h3>' . esc_html__( 'Pros', 'toolntip-core' ) . '</h3>' . $pros . '</div>';
        }
        if ( '' !== $cons ) {
            $evaluation .= '<div class="tnt-internal-tool-evaluation__cons"><h3>' . esc_html__( 'Cons', 'toolntip-core' ) . '</h3>' . $cons . '</div>';
        }
        $evaluation .= '</div></section>';
        $sections[] = $evaluation;
    }

    $screenshots = tnt_render_tool_shell_screenshots( $tool );
    if ( '' !== $screenshots ) {
        $sections[] = '<section class="tnt-internal-tool-section tnt-internal-tool-section--screenshots"><h2 class="tnt-internal-tool-section__title">' . esc_html__( 'Screenshots', 'toolntip-core' ) . '</h2>' . $screenshots . '</section>';
    }

    $video = tnt_render_tool_shell_video( $tool );
    if ( '' !== $video ) {
        $sections[] = '<section class="tnt-internal-tool-section tnt-internal-tool-section--video"><h2 class="tnt-internal-tool-section__title">' . esc_html__( 'Video', 'toolntip-core' ) . '</h2>' . $video . '</section>';
    }

    $description = tnt_get_tool_shell_description( $tool );
    if ( '' !== trim( $description ) ) {
        $sections[] = '<section class="tnt-internal-tool-section tnt-internal-tool-section--about"><h2 class="tnt-internal-tool-section__title">' . esc_html__( 'About This Tool', 'toolntip-core' ) . '</h2><div class="tnt-internal-tool-section__content">' . wp_kses_post( apply_filters( 'the_content', $description ) ) . '</div></section>';
    }

    $faq = tnt_render_tool_shell_faq( $tool );
    if ( '' !== $faq ) {
        $sections[] = '<section class="tnt-internal-tool-section tnt-internal-tool-section--faq"><h2 class="tnt-internal-tool-section__title">' . esc_html__( 'Frequently Asked Questions', 'toolntip-core' ) . '</h2>' . $faq . '</section>';
    }

    return empty( $sections ) ? '' : '<div class="tnt-internal-tool-bottom">' . implode( '', $sections ) . '</div>';
}

function tnt_render_external_tool_page( $tool, $args = array() ) {
    if ( ! $tool instanceof WP_Post ) {
        return '';
    }

    $tool_data = tnt_get_tool_data( $tool->ID );
    if ( empty( $tool_data ) || ! is_array( $tool_data ) ) {
        return '';
    }

    ob_start();
    ?>
    <article class="tnt-tool-detail tnt-external-tool-page">
        <header class="tnt-tool-detail__hero"><?php tnt_render( 'hero', $tool_data ); ?></header>
        <?php
        $monetization = tnt_render_monetization_placement( 'external-after-hero', $tool );
        if ( '' !== $monetization ) : ?>
            <div class="tnt-external-tool-page__promo tnt-external-tool-page__promo--after-hero"><?php echo $monetization; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        <?php endif; ?>

        <nav class="tnt-tool-detail__nav" aria-label="<?php echo esc_attr__( 'Tool page sections', 'toolntip-core' ); ?>"><?php tnt_render( 'tool-detail-nav', $tool_data ); ?></nav>

        <div class="tnt-tool-detail__layout">
            <main class="tnt-tool-detail__main">
                <section id="overview" class="tnt-tool-detail__section tnt-tool-detail__section--overview"><?php tnt_render( 'about', $tool_data ); ?><?php tnt_render( 'features', $tool_data ); ?></section>
                <section class="tnt-tool-detail__section tnt-tool-detail__section--media"><?php tnt_render( 'screenshots', $tool_data ); ?><?php tnt_render( 'video', $tool_data ); ?></section>
                <section class="tnt-tool-detail__section tnt-tool-detail__section--evaluation"><?php tnt_render( 'pros-cons', $tool_data ); ?><?php tnt_render( 'faq', $tool_data ); ?></section>
                <section class="tnt-tool-detail__section tnt-tool-detail__section--reviews"><?php tnt_render( 'reviews', $tool_data ); ?></section>
            </main>
            <aside class="tnt-tool-detail__sidebar" aria-label="<?php echo esc_attr__( 'Tool information and actions', 'toolntip-core' ); ?>">
                <div class="tnt-tool-detail__sidebar-inner"><?php tnt_render( 'tool-information', $tool_data ); ?><?php tnt_render( 'similar-tools', $tool_data ); ?></div>
            </aside>
        </div>
        <?php tnt_render( 'schema', $tool_data ); ?>
    </article>
    <?php
    return (string) ob_get_clean();
}
