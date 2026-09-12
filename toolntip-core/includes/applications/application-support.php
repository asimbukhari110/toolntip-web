<?php
/**
 * Lean supporting-content composition for internal application pages.
 *
 * The application shell owns identity, monetization and runtime. This layer
 * deliberately renders only concise, editorial Tool content that complements
 * the application experience. It never renders ads, runtime markup or global
 * identity and it does not recreate the legacy internal-tool bottom.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Render compact Similar Tools discovery cards for an application page.
 *
 * Selection, ranking and eligibility remain owned by the existing
 * tnt_get_related_tools() contract. This renderer changes presentation only.
 *
 * @param WP_Post $tool Current Tool.
 * @param int     $limit Maximum related Tools to show.
 * @return string
 */
function tnt_render_application_similar_tools( WP_Post $tool, $limit = 4 ) {
    if ( ! function_exists( 'tnt_get_related_tools' ) ) {
        return '';
    }

    $related_tools = tnt_get_related_tools( $tool, max( 1, (int) $limit ) );
    if ( empty( $related_tools ) || ! is_array( $related_tools ) ) {
        return '';
    }

    $cards = array();

    foreach ( $related_tools as $related_tool ) {
        if ( ! is_array( $related_tool ) || empty( $related_tool['post_id'] ) || empty( $related_tool['title'] ) ) {
            continue;
        }

        $related_id = (int) $related_tool['post_id'];
        $url        = get_permalink( $related_id );
        if ( ! $url ) {
            continue;
        }

        $category = ! empty( $related_tool['categories'][0]['name'] )
            ? (string) $related_tool['categories'][0]['name']
            : esc_html__( 'Tool', 'toolntip-core' );

        ob_start();
        ?>
        <a class="tnt-application-similar-tool" href="<?php echo esc_url( $url ); ?>">
            <?php if ( ! empty( $related_tool['featured'] ) ) : ?>
                <span class="tnt-application-similar-tool__ribbon">
                    <?php echo esc_html__( 'Featured', 'toolntip-core' ); ?>
                </span>
            <?php endif; ?>

            <span class="tnt-application-similar-tool__logo" aria-hidden="true">
                <?php tnt_render( 'logo', $related_tool ); ?>
            </span>

            <span class="tnt-application-similar-tool__body">
                <span class="tnt-application-similar-tool__name">
                    <?php echo esc_html( $related_tool['title'] ); ?>
                </span>
                <span class="tnt-application-similar-tool__meta">
                    <?php echo esc_html( $category ); ?>
                </span>
            </span>

            <span class="tnt-application-similar-tool__arrow" aria-hidden="true">&#8594;</span>
        </a>
        <?php
        $card = trim( (string) ob_get_clean() );
        if ( '' !== $card ) {
            $cards[] = $card;
        }
    }

    if ( empty( $cards ) ) {
        return '';
    }

    return '<section class="tnt-application-support__section tnt-application-support__section--similar"><h2 class="tnt-application-support__title">' . esc_html__( 'Similar Tools', 'toolntip-core' ) . '</h2><div class="tnt-application-similar-tools">' . implode( '', $cards ) . '</div></section>';
}

/**
 * Resolve and render application supporting content.
 *
 * Current v1 sections intentionally map only to existing canonical Tool data.
 * Related Resource/How-To discovery is deferred until its relationship contract
 * is defined; no title/tag guessing is performed here.
 *
 * @param array $args Tool shell resolver arguments.
 * @return string
 */
function tnt_render_application_support( $args = array() ) {
    $tool = tnt_resolve_tool_shell_context( is_array( $args ) ? $args : array() );
    if ( ! $tool instanceof WP_Post ) {
        return '';
    }

    // Application support is only meaningful for an enabled internal app.
    if ( function_exists( 'tnt_resolve_application_context' ) ) {
        $context = tnt_resolve_application_context( array( 'post_id' => $tool->ID ) );
        if ( empty( $context['enabled'] ) ) {
            return '';
        }
    }

    $sections = array();

    // Editorial ownership: this field must describe this ToolNTip application,
    // not duplicate generic educational or How-To content.
    $description = tnt_get_tool_shell_description( $tool );
    if ( '' !== trim( wp_strip_all_tags( (string) $description ) ) ) {
        $sections[] = '<section class="tnt-application-support__section tnt-application-support__section--about"><h2 class="tnt-application-support__title">' . esc_html__( 'About This Application', 'toolntip-core' ) . '</h2><div class="tnt-application-support__content">' . wp_kses_post( apply_filters( 'the_content', $description ) ) . '</div></section>';
    }

    // Existing Tool features are presented as a concise capability summary.
    $features = tnt_render_tool_shell_list( tnt_get_tool_features( $tool ), 'tnt-application-support__capabilities' );
    if ( '' !== $features ) {
        $sections[] = '<section class="tnt-application-support__section tnt-application-support__section--capabilities"><h2 class="tnt-application-support__title">' . esc_html__( 'Key Capabilities', 'toolntip-core' ) . '</h2>' . $features . '</section>';
    }

    // FAQ remains conditional and should contain application-specific questions.
    // Generic educational FAQs belong to Resources/How-To content instead.
    $faq = tnt_render_tool_shell_faq( $tool );
    if ( '' !== $faq ) {
        $sections[] = '<section class="tnt-application-support__section tnt-application-support__section--faq"><h2 class="tnt-application-support__title">' . esc_html__( 'Application FAQ', 'toolntip-core' ) . '</h2>' . $faq . '</section>';
    }

    // Similar Tools reuses the existing ToolNTip relationship/ranking engine.
    // Only the application-page presentation is new.
    $similar_tools = tnt_render_application_similar_tools( $tool, 4 );
    if ( '' !== $similar_tools ) {
        $sections[] = $similar_tools;
    }

    /**
     * Filters lean application support sections.
     *
     * This is a trusted PHP extension point, not editor-executable configuration.
     * It permits future Privacy/Processing, Limitations and governed related-
     * content sections without coupling them to the runtime or monetization.
     *
     * @param array   $sections Rendered section markup.
     * @param WP_Post $tool     Resolved Tool.
     */
    $sections = apply_filters( 'tnt_application_support_sections', $sections, $tool );
    $sections = is_array( $sections ) ? array_values( array_filter( $sections, 'is_string' ) ) : array();

    if ( empty( $sections ) ) {
        return '';
    }

    if ( wp_style_is( 'tnt-application-support', 'registered' ) ) {
        wp_enqueue_style( 'tnt-application-support' );
    }

    return '<div class="tnt-application-support" data-tool-id="' . esc_attr( (string) $tool->ID ) . '">' . implode( '', $sections ) . '</div>';
}
