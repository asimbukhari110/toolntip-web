<?php
/**
 * Tool Detail Component Shortcodes.
 *
 * Thin adapters over the existing finalized Tool Detail presentation
 * components. These shortcodes do not reproduce or alter component markup;
 * every visible component is rendered through the existing tnt_render()
 * contract used by templates/tool-detail.php.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get normalized Tool Detail data for a component shortcode.
 *
 * Uses the established Tool shell resolver so shortcodes support explicit
 * post_id/tool_slug arguments, Tool CPT context, and linked Application Page
 * context without introducing a second resolution path.
 *
 * @param array|string $atts      Shortcode attributes.
 * @param string       $shortcode Shortcode name.
 * @return array|null
 */
function tnt_tool_detail_component_shortcode_data( $atts, $shortcode ) {
    $tool = tnt_tool_shell_shortcode_tool( $atts, $shortcode );

    if ( ! $tool instanceof WP_Post ) {
        return null;
    }

    $tool_data = tnt_get_tool_data( $tool->ID );

    return is_array( $tool_data ) && ! empty( $tool_data )
        ? $tool_data
        : null;
}

/**
 * Render an existing Tool Detail template component as shortcode output.
 *
 * @param array|string $atts      Shortcode attributes.
 * @param string       $shortcode Shortcode name.
 * @param string       $component Existing templates/parts component name.
 * @return string
 */
function tnt_tool_detail_component_shortcode_render( $atts, $shortcode, $component ) {
    $tool_data = tnt_tool_detail_component_shortcode_data( $atts, $shortcode );

    if ( empty( $tool_data ) ) {
        return '';
    }

    ob_start();
    tnt_render( $component, $tool_data );

    return (string) ob_get_clean();
}

/**
 * Generic callback for finalized Tool Detail component shortcodes.
 *
 * @param array|string $atts          Shortcode attributes.
 * @param string|null  $content       Enclosed content. Unused.
 * @param string       $shortcode_tag Invoked shortcode tag.
 * @return string
 */
function tnt_tool_detail_component_shortcode( $atts, $content = null, $shortcode_tag = '' ) {
    unset( $content );

    $component_map = array(
        'tnt_detail_nav'           => 'tool-detail-nav',
        'tnt_detail_about'         => 'about',
        'tnt_detail_features'      => 'features',
        'tnt_detail_screenshots'   => 'screenshots',
        'tnt_detail_video'         => 'video',
        'tnt_detail_pros_cons'     => 'pros-cons',
        'tnt_detail_faq'           => 'faq',
        'tnt_detail_reviews'       => 'reviews',
        'tnt_detail_information'   => 'tool-information',
        'tnt_detail_similar_tools' => 'similar-tools',
        'tnt_detail_schema'        => 'schema',
        'tnt_tool_detail_nav'       => 'tool-detail-nav',
        'tnt_tool_about'            => 'about',
        'tnt_tool_pros_cons'        => 'pros-cons',
        'tnt_tool_reviews'          => 'reviews',
        'tnt_tool_information'      => 'tool-information',
        'tnt_tool_similar_tools'    => 'similar-tools',
        'tnt_tool_schema'           => 'schema',
    );

    if ( in_array( $shortcode_tag, array( 'tnt_detail_hero', 'tnt_tool_detail_hero' ), true ) ) {
        $tool_data = tnt_tool_detail_component_shortcode_data( $atts, $shortcode_tag );

        if ( empty( $tool_data ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="tnt-tool-detail tnt-detail-component tnt-detail-component--hero">
            <header class="tnt-tool-detail__hero">
                <div class="tnt-tool-hero tnt-tool-hero--identity-only">
                    <div class="tnt-tool-hero__identity">
                        <?php tnt_render( 'hero-identity-content', $tool_data ); ?>
                    </div>
                </div>
            </header>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    if ( ! isset( $component_map[ $shortcode_tag ] ) ) {
        return '';
    }

    $output = tnt_tool_detail_component_shortcode_render(
        $atts,
        $shortcode_tag,
        $component_map[ $shortcode_tag ]
    );

    /*
     * The finalized Key Features presentation is intentionally scoped beneath
     * .tnt-tool-detail in tool-detail.css. The full Tool Detail template
     * already provides that scope; the standalone Elementor shortcode does
     * not. Add only the missing presentation scope here so the shortcode uses
     * the exact canonical features template and CSS without duplicating or
     * changing either component.
     */
    if ( 'tnt_detail_features' === $shortcode_tag && '' !== $output ) {
        return '<div class="tnt-tool-detail tnt-detail-component tnt-detail-component--features">' . $output . '</div>';
    }

    /*
     * Tool Information uses the same Tool Detail design tokens and canonical
     * sidebar-card styles as templates/tool-detail.php. The standalone
     * Elementor shortcode must provide the missing .tnt-tool-detail scope so
     * CSS custom properties, card borders, row separators, tag pills, and the
     * finalized information layout resolve exactly as they do natively.
     * The canonical tool-information template remains unchanged.
     */
    if ( 'tnt_detail_information' === $shortcode_tag && '' !== $output ) {
        return '<div class="tnt-tool-detail tnt-detail-component tnt-detail-component--information">' . $output . '</div>';
    }

    /*
     * Similar Tools is a finalized Tool Detail sidebar card whose compact row
     * layout, logo sizing, separators, rating alignment, and View More action
     * are intentionally scoped beneath .tnt-tool-detail in tool-detail.css.
     * Provide only that missing presentation scope for the standalone
     * Elementor shortcode; the canonical similar-tools template remains
     * unchanged.
     */
    if ( 'tnt_detail_similar_tools' === $shortcode_tag && '' !== $output ) {
        return '<div class="tnt-tool-detail tnt-detail-component tnt-detail-component--similar-tools">' . $output . '</div>';
    }

    /*
     * Pros & Cons uses the finalized Tool Detail component layout. Its panel
     * flow, two-column grid, green/red surfaces, borders, and typography are
     * scoped beneath .tnt-tool-detail in tool-detail.css. The standalone
     * Elementor shortcode must provide that same presentation scope while
     * continuing to render the canonical pros-cons template unchanged.
     */
    if ( 'tnt_detail_pros_cons' === $shortcode_tag && '' !== $output ) {
        return '<div class="tnt-tool-detail tnt-detail-component tnt-detail-component--pros-cons">' . $output . '</div>';
    }

    /*
     * FAQ is a finalized Tool Detail accordion component. Its outer card,
     * heading icon, question rows, plus controls, spacing, and interactive
     * presentation are scoped beneath .tnt-tool-detail in tool-detail.css.
     * Provide only that missing presentation scope for the standalone
     * Elementor shortcode while leaving the canonical FAQ template and its
     * behavior unchanged.
     */
    if ( 'tnt_detail_faq' === $shortcode_tag && '' !== $output ) {
        return '<div class="tnt-tool-detail tnt-detail-component tnt-detail-component--faq">' . $output . '</div>';
    }

    /*
     * Video Overview is rendered inside the same media-section composition
     * used by the canonical Tool Detail page. This preserves the complete
     * video.php component (header icon + "Video Overview" title + media)
     * while supplying the exact parent scope its finalized CSS expects.
     * No video markup is reproduced here; the canonical template remains the
     * single source of truth.
     */
    if ( 'tnt_detail_video' === $shortcode_tag && '' !== $output ) {
        return '<div class="tnt-tool-detail tnt-detail-component tnt-detail-component--video"><section class="tnt-tool-detail__section tnt-tool-detail__section--media">' . $output . '</section></div>';
    }

    return $output;
}

/**
 * Render the existing Tool Detail after-hero monetization surface.
 *
 * This preserves the same placement key and wrapper class used by
 * templates/tool-detail.php while keeping monetization resolution in Core.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_detail_after_hero_monetization_shortcode( $atts ) {
    $tool = tnt_tool_shell_shortcode_tool(
        $atts,
        'tnt_detail_after_hero_monetization'
    );

    if ( ! $tool instanceof WP_Post || ! function_exists( 'tnt_render_monetization_placement' ) ) {
        return '';
    }

    $monetization = tnt_render_monetization_placement(
        'external-after-hero',
        $tool
    );

    if ( '' === $monetization ) {
        return '';
    }

    return '<div class="tnt-tool-detail__after-hero-monetization">' . $monetization . '</div>';
}

$tool_detail_component_shortcodes = array(
    'tnt_detail_hero',
    'tnt_detail_nav',
    'tnt_detail_about',
    'tnt_detail_features',
    'tnt_detail_screenshots',
    'tnt_detail_video',
    'tnt_detail_pros_cons',
    'tnt_detail_faq',
    'tnt_detail_reviews',
    'tnt_detail_information',
    'tnt_detail_similar_tools',
    'tnt_detail_schema',
);

foreach ( $tool_detail_component_shortcodes as $tool_detail_component_shortcode ) {
    add_shortcode(
        $tool_detail_component_shortcode,
        'tnt_tool_detail_component_shortcode'
    );
}



/**
 * Safe aliases for Tool Detail components that do not conflict with the
 * established Tool Shell shortcode namespace.
 *
 * These aliases render the exact same finalized Tool Detail components as
 * their tnt_detail_* counterparts. Existing shortcodes are never replaced.
 */
$tool_detail_component_aliases = array(
    'tnt_tool_detail_hero'  => 'tnt_tool_detail_component_shortcode',
    'tnt_tool_detail_nav'   => 'tnt_tool_detail_component_shortcode',
    'tnt_tool_about'        => 'tnt_tool_detail_component_shortcode',
    'tnt_tool_pros_cons'    => 'tnt_tool_detail_component_shortcode',
    'tnt_tool_reviews'      => 'tnt_tool_detail_component_shortcode',
    'tnt_tool_information'  => 'tnt_tool_detail_component_shortcode',
    'tnt_tool_similar_tools'=> 'tnt_tool_detail_component_shortcode',
    'tnt_tool_schema'       => 'tnt_tool_detail_component_shortcode',
);

foreach ( $tool_detail_component_aliases as $alias => $callback ) {
    if ( ! shortcode_exists( $alias ) ) {
        add_shortcode( $alias, $callback );
    }
}


/**
 * Render the canonical Tool Detail Video Overview component.
 *
 * This dedicated callback intentionally bypasses the generic component
 * adapter so the complete canonical video.php template (header + media)
 * is emitted inside the exact Tool Detail presentation scope expected by
 * tool-detail.css.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_detail_video_shortcode( $atts ) {
    $tool_data = tnt_tool_detail_component_shortcode_data(
        $atts,
        'tnt_detail_video'
    );

    if ( empty( $tool_data ) || empty( $tool_data['video'] ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="tnt-tool-detail tnt-detail-component tnt-detail-component--video">
        <section class="tnt-tool-detail__section tnt-tool-detail__section--media">
            <?php tnt_render( 'video', $tool_data ); ?>
        </section>
    </div>
    <?php

    return (string) ob_get_clean();
}

remove_shortcode( 'tnt_detail_video' );
add_shortcode( 'tnt_detail_video', 'tnt_detail_video_shortcode' );


/**
 * Render the canonical Tool Detail Screenshots component.
 *
 * This dedicated callback keeps the existing screenshots.php template as
 * the single source of truth while supplying the exact Tool Detail media
 * section scope required by the finalized screenshot grid presentation.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function tnt_detail_screenshots_shortcode( $atts ) {
    $tool_data = tnt_tool_detail_component_shortcode_data(
        $atts,
        'tnt_detail_screenshots'
    );

    if ( empty( $tool_data ) || empty( $tool_data['screenshots'] ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="tnt-tool-detail tnt-detail-component tnt-detail-component--screenshots">
        <section class="tnt-tool-detail__section tnt-tool-detail__section--media">
            <?php tnt_render( 'screenshots', $tool_data ); ?>
        </section>
    </div>
    <?php

    return (string) ob_get_clean();
}

remove_shortcode( 'tnt_detail_screenshots' );
add_shortcode( 'tnt_detail_screenshots', 'tnt_detail_screenshots_shortcode' );

add_shortcode(
    'tnt_detail_after_hero_monetization',
    'tnt_detail_after_hero_monetization_shortcode'
);
