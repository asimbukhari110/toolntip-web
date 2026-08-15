<?php
/**
 * Internal Tool Application Shell Helpers.
 *
 * Presentation-neutral Core API used by the granular Tool shell shortcodes
 * today and available to native ToolNTip presentation layers in the future.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve a Tool for the application-shell API.
 *
 * Resolution order is intentionally explicit and conservative:
 * 1. explicit post_id
 * 2. explicit tool_slug
 * 3. current post when it is a Tool CPT
 * 4. current Application Page linked Tool context
 * 5. unresolved calls return null
 *
 * @param array $args Context arguments.
 * @return WP_Post|null
 */
function tnt_resolve_tool_shell_context( $args = array() ) {

    $args = wp_parse_args(
        $args,
        array(
            'post_id'   => 0,
            'tool_slug' => '',
        )
    );

    $post_id = absint( $args['post_id'] );

    if ( $post_id > 0 ) {
        $tool = tnt_get_tool( $post_id );

        if ( $tool ) {
            return $tool;
        }
    }

    $tool_slug = sanitize_title( (string) $args['tool_slug'] );

    if ( $tool_slug !== '' ) {
        $tool = tnt_get_tool( $tool_slug );

        if ( $tool ) {
            return $tool;
        }
    }

    $current_post = get_post();

    if ( $current_post instanceof WP_Post && $current_post->post_type === 'tool' ) {
        return tnt_get_tool( $current_post );
    }

    /*
     * Resolve Tool from the current Application Page binding.
     */
    $page_id = get_queried_object_id();

    if ( ! $page_id ) {
        $page_id = get_the_ID();
    }

    $page_id = absint( $page_id );

    if ( $page_id > 0 && 'page' === get_post_type( $page_id ) ) {
        $linked_tool_id = absint(
            get_post_meta(
                $page_id,
                '_tnt_tool_context_id',
                true
            )
        );

        if ( $linked_tool_id > 0 ) {
            $tool = tnt_get_tool( $linked_tool_id );

            if ( $tool ) {
                return $tool;
            }
        }
    }

    return null;
}

/**
 * Get Tool tagline.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_tagline( $tool ) {
    return trim( (string) get_field( 'tool_tagline', $tool->ID ) );
}

/**
 * Get Tool excerpt.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_excerpt( $tool ) {
    return trim( (string) get_the_excerpt( $tool ) );
}

/**
 * Get the native Tool CPT post content.
 *
 * This deliberately does not substitute the Tool Detail about_this_tool field.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_description( $tool ) {
    return (string) get_post_field( 'post_content', $tool->ID );
}

/**
 * Get editor rating value.
 *
 * @param WP_Post $tool Tool post.
 * @return float
 */
function tnt_get_tool_shell_rating( $tool ) {
    $rating = tnt_get_tool_rating( $tool );

    return isset( $rating['editor']['value'] )
        ? (float) $rating['editor']['value']
        : 0.0;
}

/**
 * Get authoritative community review count.
 *
 * @param WP_Post $tool Tool post.
 * @return int
 */
function tnt_get_tool_shell_review_count( $tool ) {
    $rating = tnt_get_tool_rating( $tool );

    return isset( $rating['community']['count'] )
        ? (int) $rating['community']['count']
        : 0;
}

/**
 * Determine whether Tool is verified.
 *
 * @param WP_Post $tool Tool post.
 * @return bool
 */
function tnt_get_tool_shell_verified( $tool ) {
    return (bool) get_field( 'verified', $tool->ID );
}

/**
 * Get Tool pricing value.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_pricing( $tool ) {
    return trim( (string) get_field( 'pricing', $tool->ID ) );
}

/**
 * Get normalized Tool platforms.
 *
 * @param WP_Post $tool Tool post.
 * @return array
 */
function tnt_get_tool_shell_platforms( $tool ) {
    $platforms = get_field( 'platform', $tool->ID );

    if ( empty( $platforms ) ) {
        return array();
    }

    if ( ! is_array( $platforms ) ) {
        $platforms = array( $platforms );
    }

    return array_values(
        array_filter(
            array_map(
                static function ( $platform ) {
                    return trim( (string) $platform );
                },
                $platforms
            )
        )
    );
}

/**
 * Get Tool developer/vendor.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_developer( $tool ) {
    return trim( (string) get_field( 'developer', $tool->ID ) );
}

/**
 * Get Tool type.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_type( $tool ) {
    return trim( (string) get_field( 'tool_type', $tool->ID ) );
}

/**
 * Get the primary Use Tool destination from the existing action contract.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_url( $tool ) {
    $actions = tnt_get_tool_actions( $tool );

    return ! empty( $actions['use_tool']['url'] )
        ? (string) $actions['use_tool']['url']
        : '';
}

/**
 * Get native Tool Detail permalink.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_get_tool_shell_permalink( $tool ) {
    return (string) get_permalink( $tool );
}

/**
 * Render the current Application Page Featured Image for the shell Hero.
 *
 * This intentionally does not inspect Tool CPT image fields or Tool context.
 * No image means no markup at all.
 *
 * @param string|array $size WordPress image size.
 * @return string
 */
function tnt_get_tool_shell_hero_image( $size = 'large' ) {
    $page_id = get_queried_object_id();

    if ( ! $page_id ) {
        $page_id = get_the_ID();
    }

    $page_id = absint( $page_id );

    if ( $page_id <= 0 || ! has_post_thumbnail( $page_id ) ) {
        return '';
    }

    $attachment_id = get_post_thumbnail_id( $page_id );

    if ( ! $attachment_id ) {
        return '';
    }

    return (string) wp_get_attachment_image(
        $attachment_id,
        $size,
        false,
        array(
            'class'   => 'tnt-tool-hero-image',
            'loading' => 'lazy',
        )
    );
}

/**
 * Render Tool identity icon/logo.
 *
 * Uses the existing Tool logo helper and remains separate
 * from the Application Page Hero image.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_render_tool_shell_icon( $tool ) {

    $logo = tnt_get_tool_logo( $tool );

    if (
        empty( $logo ) ||
        ! empty( $logo['placeholder'] ) ||
        empty( $logo['url'] )
    ) {
        return '';
    }

    $width  = absint( $logo['width'] ?? 0 );
    $height = absint( $logo['height'] ?? 0 );

    $attributes = '';

    if ( $width > 0 ) {
        $attributes .= ' width="' . esc_attr( (string) $width ) . '"';
    }

    if ( $height > 0 ) {
        $attributes .= ' height="' . esc_attr( (string) $height ) . '"';
    }

    return sprintf(
        '<img class="tnt-tool-icon" src="%1$s" alt="%2$s"%3$s loading="lazy" decoding="async">',
        esc_url( $logo['url'] ),
        esc_attr( (string) ( $logo['alt'] ?? '' ) ),
        $attributes
    );
}

/**
 * Convert normalized taxonomy data to names.
 *
 * @param array $terms Normalized taxonomy term arrays.
 * @return array
 */
function tnt_get_tool_shell_term_names( $terms ) {
    if ( empty( $terms ) || ! is_array( $terms ) ) {
        return array();
    }

    $names = array();

    foreach ( $terms as $term ) {
        if ( is_array( $term ) && ! empty( $term['name'] ) ) {
            $names[] = trim( (string) $term['name'] );
        }
    }

    return array_values( array_filter( $names ) );
}

/**
 * Render a semantic string list.
 *
 * @param array  $items      List values.
 * @param string $class_name ToolNTip-prefixed class.
 * @return string
 */
function tnt_render_tool_shell_list( $items, $class_name ) {
    if ( empty( $items ) || ! is_array( $items ) ) {
        return '';
    }

    $output = '<ul class="' . esc_attr( $class_name ) . '">';

    foreach ( $items as $item ) {
        $item = trim( (string) $item );

        if ( $item === '' ) {
            continue;
        }

        $output .= '<li>' . esc_html( $item ) . '</li>';
    }

    $output .= '</ul>';

    return $output === '<ul class="' . esc_attr( $class_name ) . '"></ul>' ? '' : $output;
}

/**
 * Render verified semantic markup.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_render_tool_shell_verified( $tool ) {
    if ( ! tnt_get_tool_shell_verified( $tool ) ) {
        return '';
    }

    return '<span class="tnt-tool-verified">' . esc_html__( 'Verified', 'toolntip-core' ) . '</span>';
}

/**
 * Render compact application-shell metadata.
 *
 * Only existing values are emitted. Layout remains presentation-layer owned.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_render_tool_shell_meta( $tool ) {
    $items = array();

    $pricing = tnt_get_tool_shell_pricing( $tool );
    if ( $pricing !== '' ) {
        $items[] = array( 'class' => 'pricing', 'value' => $pricing );
    }

    $platforms = tnt_get_tool_shell_platforms( $tool );
    if ( ! empty( $platforms ) ) {
        $items[] = array( 'class' => 'platform', 'value' => implode( ', ', $platforms ) );
    }

    $categories = tnt_get_tool_shell_term_names( tnt_get_tool_categories( $tool ) );
    if ( ! empty( $categories ) ) {
        $items[] = array( 'class' => 'category', 'value' => implode( ', ', $categories ) );
    }

    $type = tnt_get_tool_shell_type( $tool );
    if ( $type !== '' ) {
        $items[] = array( 'class' => 'type', 'value' => $type );
    }

    $rating = tnt_get_tool_shell_rating( $tool );
    if ( $rating > 0 ) {
        $items[] = array(
            'class' => 'rating',
            'value' => sprintf(
                /* translators: %s: editor rating value out of five. */
                __( '★ %s/5', 'toolntip-core' ),
                number_format_i18n( $rating, 1 )
            ),
        );
    }

    $verified = tnt_render_tool_shell_verified( $tool );

    if ( empty( $items ) && $verified === '' ) {
        return '';
    }

    $parts = array();

    foreach ( $items as $item ) {
        $parts[] =
            '<span class="tnt-tool-meta__item tnt-tool-meta__item--' .
            esc_attr( $item['class'] ) .
            '">' .
            esc_html( $item['value'] ) .
            '</span>';
    }

    if ( $verified !== '' ) {
        $parts[] = $verified;
    }

    $separator = '<span class="tnt-tool-meta__separator" aria-hidden="true"> · </span>';

    return '<div class="tnt-tool-meta">' .
        implode( $separator, $parts ) .
        '</div>';
}

/**
 * Render Tool screenshots with native WordPress responsive image markup.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_render_tool_shell_screenshots( $tool ) {
    $screenshots = tnt_get_tool_screenshots( $tool );

    if ( empty( $screenshots ) ) {
        return '';
    }

    $images = '';

    foreach ( $screenshots as $screenshot ) {
        $attachment_id = ! empty( $screenshot['id'] ) ? absint( $screenshot['id'] ) : 0;

        if ( $attachment_id <= 0 ) {
            continue;
        }

        $images .= wp_get_attachment_image(
            $attachment_id,
            'large',
            false,
            array(
                'class'   => 'tnt-tool-screenshots__image',
                'loading' => 'lazy',
            )
        );
    }

    if ( $images === '' ) {
        return '';
    }

    return '<div class="tnt-tool-screenshots">' . $images . '</div>';
}

/**
 * Render Tool demo video returned by the existing oEmbed helper.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_render_tool_shell_video( $tool ) {
    $video = tnt_get_tool_video( $tool );

    if ( empty( $video['embed'] ) ) {
        return '';
    }

    $allowed = wp_kses_allowed_html( 'post' );
    $allowed['iframe'] = array(
        'src'             => true,
        'width'           => true,
        'height'          => true,
        'frameborder'     => true,
        'allow'           => true,
        'allowfullscreen' => true,
        'title'           => true,
        'loading'         => true,
        'referrerpolicy'  => true,
        'class'           => true,
    );

    $embed = wp_kses( $video['embed'], $allowed );

    if ( $embed === '' ) {
        return '';
    }

    return '<div class="tnt-tool-video">' . $embed . '</div>';
}

/**
 * Render Tool FAQs using semantic details/summary markup.
 *
 * @param WP_Post $tool Tool post.
 * @return string
 */
function tnt_render_tool_shell_faq( $tool ) {
    $faqs = tnt_get_tool_faqs( $tool );

    if ( empty( $faqs ) ) {
        return '';
    }

    $output = '<section class="tnt-tool-faq">';

    foreach ( $faqs as $faq ) {
        if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
            continue;
        }

        $output .= '<details class="tnt-tool-faq__item">';
        $output .= '<summary class="tnt-tool-faq__question">' . esc_html( $faq['question'] ) . '</summary>';
        $output .= '<div class="tnt-tool-faq__answer">' . wp_kses_post( $faq['answer'] ) . '</div>';
        $output .= '</details>';
    }

    $output .= '</section>';

    return $output === '<section class="tnt-tool-faq"></section>' ? '' : $output;
}
