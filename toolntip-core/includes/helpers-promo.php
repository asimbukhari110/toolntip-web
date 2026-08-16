<?php
/**
 * Promotional & Monetization Placement Helpers.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_monetization_settings() {
    $defaults = function_exists( 'tnt_get_default_monetization_settings' )
        ? tnt_get_default_monetization_settings()
        : array(
            'global_loader_code' => '',
            'ad_units'           => array(),
            'directory_policy'   => array(),
            'placements'         => array(),
        );

    $stored = get_option( 'tnt_monetization_settings', null );

    if ( is_array( $stored ) ) {
        return wp_parse_args( $stored, $defaults );
    }

    $settings = $defaults;
    $legacy_internal = absint( get_option( 'tnt_promo_internal_after_app_tool_id', 0 ) );
    $legacy_external = absint( get_option( 'tnt_promo_external_after_hero_tool_id', 0 ) );

    if ( $legacy_internal > 0 ) {
        $settings['placements']['internal-after-app'] = array(
            'mode' => 'tool', 'tool_ids' => array( $legacy_internal ), 'custom_code' => '',
        );
    }

    if ( $legacy_external > 0 ) {
        $settings['placements']['external-after-hero'] = array(
            'mode' => 'tool', 'tool_ids' => array( $legacy_external ), 'custom_code' => '',
        );
    }

    return $settings;
}


/**
 * Return a reusable ad-unit configuration.
 *
 * @param string $unit Ad-unit key.
 *
 * @return array
 */
function tnt_get_monetization_ad_unit_config( $unit ) {
    $unit = sanitize_key( $unit );

    if ( '' === $unit || ! function_exists( 'tnt_get_monetization_ad_unit_definitions' ) ) {
        return array();
    }

    $definitions = tnt_get_monetization_ad_unit_definitions();
    if ( empty( $definitions[ $unit ] ) || ! is_array( $definitions[ $unit ] ) ) {
        return array();
    }

    $settings = tnt_get_monetization_settings();
    $stored = isset( $settings['ad_units'][ $unit ] ) && is_array( $settings['ad_units'][ $unit ] )
        ? $settings['ad_units'][ $unit ]
        : array();

    return array(
        'key'        => $unit,
        'label'      => (string) ( $definitions[ $unit ]['label'] ?? $unit ),
        'size'       => (string) ( $definitions[ $unit ]['size'] ?? '' ),
        'dimensions' => (string) ( $definitions[ $unit ]['dimensions'] ?? '' ),
        'code'       => trim( (string) ( $stored['code'] ?? '' ) ),
    );
}

/**
 * Return administrator-managed ad-unit markup.
 *
 * This renderer intentionally accepts no arbitrary shortcode-provided HTML.
 * The trusted markup must come from the centralized monetization settings.
 *
 * @param string $unit    Ad-unit key.
 * @param string $context Rendering context identifier.
 *
 * @return string
 */
function tnt_get_monetization_ad_unit_markup( $unit, $context = 'manual' ) {
    $config = tnt_get_monetization_ad_unit_config( $unit );

    if ( empty( $config['code'] ) ) {
        return '';
    }

    $unit_key = sanitize_html_class( (string) $config['key'] );
    $context_key = sanitize_html_class( sanitize_key( $context ) );

    $markup  = '<aside class="tnt-ad-unit tnt-ad-unit--' . esc_attr( $unit_key ) . ' tnt-ad-unit--context-' . esc_attr( $context_key ) . '"';
    $markup .= ' data-tnt-ad-unit="' . esc_attr( $config['key'] ) . '"';
    $markup .= ' data-tnt-ad-size="' . esc_attr( $config['dimensions'] ) . '"';
    $markup .= ' aria-label="' . esc_attr__( 'Advertisement', 'toolntip-core' ) . '">';
    $markup .= '<div class="tnt-ad-unit__disclosure">' . esc_html__( 'Advertisement', 'toolntip-core' ) . '</div>';
    $markup .= '<div class="tnt-ad-unit__content">';
    $markup .= $config['code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $markup .= '</div>';
    $markup .= '</aside>';

    return $markup;
}

/**
 * Return the normalized Tool Directory in-grid advertising policy.
 *
 * The policy is intentionally state-free. WEB-006.6 archive rendering can
 * combine it with tools_loaded and ads_rendered request/session state without
 * coupling manual Elementor ad shortcodes to the automated archive budget.
 *
 * @return array
 */
function tnt_get_tool_directory_ad_policy() {
    $defaults = function_exists( 'tnt_get_default_tool_directory_ad_policy' )
        ? tnt_get_default_tool_directory_ad_policy()
        : array(
            'mode'                  => 'disabled',
            'ad_unit'               => 'rectangle',
            'strategy'              => 'random',
            'max_ads'               => 2,
            'min_before_first'      => 4,
            'min_between'           => 6,
            'allow_first_row'       => false,
            'allow_final_grid_item' => false,
        );

    $settings = tnt_get_monetization_settings();
    $stored = isset( $settings['directory_policy']['in_grid'] ) && is_array( $settings['directory_policy']['in_grid'] )
        ? $settings['directory_policy']['in_grid']
        : array();

    $policy = wp_parse_args( $stored, $defaults );

    $mode = sanitize_key( $policy['mode'] ?? $defaults['mode'] );
    if ( ! in_array( $mode, array( 'disabled', 'custom' ), true ) ) {
        $mode = $defaults['mode'];
    }

    $ad_unit = sanitize_key( $policy['ad_unit'] ?? $defaults['ad_unit'] );
    if ( function_exists( 'tnt_get_monetization_ad_unit_definitions' ) ) {
        $definitions = tnt_get_monetization_ad_unit_definitions();
        if ( ! isset( $definitions[ $ad_unit ] ) ) {
            $ad_unit = $defaults['ad_unit'];
        }
    }

    return array(
        'mode'                  => $mode,
        'ad_unit'               => $ad_unit,
        'strategy'              => 'random',
        'max_ads'               => max( 0, min( 4, (int) ( $policy['max_ads'] ?? $defaults['max_ads'] ) ) ),
        'min_before_first'      => max( 0, (int) ( $policy['min_before_first'] ?? $defaults['min_before_first'] ) ),
        'min_between'           => max( 0, (int) ( $policy['min_between'] ?? $defaults['min_between'] ) ),
        'allow_first_row'       => ! empty( $policy['allow_first_row'] ),
        'allow_final_grid_item' => ! empty( $policy['allow_final_grid_item'] ),
    );
}


/**
 * Return the fixed Tool Directory before-grid advertisement.
 *
 * The wide Leaderboard ad unit is intentionally outside the automated
 * in-grid budget. An empty administrator-managed unit renders nothing.
 *
 * @return string
 */
function tnt_get_tool_directory_before_grid_ad_markup() {
    return tnt_get_monetization_ad_unit_markup( 'leaderboard', 'directory-before-grid' );
}

/**
 * Build a deterministic seed for one Tool Directory result batch.
 *
 * The seed deliberately includes the current result state and Tool IDs so a
 * cached/archive request receives stable ad slots instead of a new shuffle on
 * every renderer invocation. Future AJAX callers may pass their own seed.
 *
 * @param array  $tool_ids Tool IDs in the current batch.
 * @param string $seed     Optional caller-provided seed.
 *
 * @return string
 */
function tnt_get_tool_directory_ad_seed( $tool_ids = array(), $seed = '' ) {
    if ( '' !== (string) $seed ) {
        return (string) $seed;
    }

    $tool_ids = array_values( array_filter( array_map( 'absint', (array) $tool_ids ) ) );
    $paged = max( 1, (int) get_query_var( 'paged' ) );

    $parts = array(
        'tool-directory',
        'page:' . $paged,
        'query:' . (string) wp_json_encode( wp_unslash( $_GET ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        'tools:' . implode( ',', $tool_ids ),
    );

    return implode( '|', $parts );
}

/**
 * Calculate controlled, deterministic in-grid advertisement slots.
 *
 * Slots are returned as 1-based local Tool positions: a slot value of 4 means
 * render an advertisement after the fourth successfully normalized Tool card
 * in the current batch. The function is state-aware so a future AJAX/Load More
 * transport can pass tools_loaded / ads_rendered without changing policy code.
 * An optional last_ad_after global Tool index is supported for cross-batch
 * minimum-spacing enforcement when that state is available.
 *
 * @param int   $tool_count Number of renderable Tools in this batch.
 * @param array $state      Runtime state: tools_loaded, ads_rendered,
 *                          last_ad_after, columns, tool_ids, seed.
 *
 * @return array<int>
 */
function tnt_get_tool_directory_ad_slots( $tool_count, $state = array() ) {
    $tool_count = max( 0, (int) $tool_count );
    if ( $tool_count <= 0 ) {
        return array();
    }

    $policy = tnt_get_tool_directory_ad_policy();
    if ( 'custom' !== ( $policy['mode'] ?? 'disabled' ) ) {
        return array();
    }

    $ad_unit = sanitize_key( (string) ( $policy['ad_unit'] ?? 'rectangle' ) );
    $ad_config = tnt_get_monetization_ad_unit_config( $ad_unit );
    if ( empty( $ad_config['code'] ) ) {
        return array();
    }

    $tools_loaded = max( 0, (int) ( $state['tools_loaded'] ?? 0 ) );
    $ads_rendered = max( 0, (int) ( $state['ads_rendered'] ?? 0 ) );
    $columns = max( 1, (int) ( $state['columns'] ?? 4 ) );
    $last_ad_after = isset( $state['last_ad_after'] ) ? max( 0, (int) $state['last_ad_after'] ) : 0;
    $remaining_budget = max( 0, (int) $policy['max_ads'] - $ads_rendered );

    if ( $remaining_budget <= 0 ) {
        return array();
    }

    $min_before_first = max( 0, (int) $policy['min_before_first'] );
    $min_between = max( 0, (int) $policy['min_between'] );
    $candidate_slots = array();

    for ( $local_after = 1; $local_after <= $tool_count; $local_after++ ) {
        $global_after = $tools_loaded + $local_after;

        if ( empty( $policy['allow_first_row'] ) && $global_after < $columns ) {
            continue;
        }

        if ( 0 === $ads_rendered && $global_after < $min_before_first ) {
            continue;
        }

        if ( $last_ad_after > 0 && ( $global_after - $last_ad_after ) < $min_between ) {
            continue;
        }

        if ( empty( $policy['allow_final_grid_item'] ) && $local_after === $tool_count ) {
            continue;
        }

        $candidate_slots[] = array(
            'local'  => $local_after,
            'global' => $global_after,
        );
    }

    if ( empty( $candidate_slots ) ) {
        return array();
    }

    $seed = tnt_get_tool_directory_ad_seed(
        (array) ( $state['tool_ids'] ?? array() ),
        (string) ( $state['seed'] ?? '' )
    );

    foreach ( $candidate_slots as &$candidate ) {
        $candidate['score'] = hash( 'sha256', $seed . '|slot:' . $candidate['global'] );
    }
    unset( $candidate );

    usort(
        $candidate_slots,
        static function ( $a, $b ) {
            $score_compare = strcmp( $a['score'], $b['score'] );
            if ( 0 !== $score_compare ) {
                return $score_compare;
            }

            return $a['global'] <=> $b['global'];
        }
    );

    /*
     * Determine how many ads can actually fit, then choose each slot from a
     * deterministic random pool that still leaves enough room for the
     * remaining ads. This avoids a random early slot accidentally reducing a
     * two-ad layout to one ad when a valid two-ad sequence exists.
     */
    $by_position = $candidate_slots;
    usort(
        $by_position,
        static function ( $a, $b ) {
            return $a['global'] <=> $b['global'];
        }
    );

    $feasible = array();
    $previous_global = 0;
    foreach ( $by_position as $candidate ) {
        if ( empty( $feasible ) || ( $candidate['global'] - $previous_global ) >= $min_between ) {
            $feasible[] = $candidate;
            $previous_global = $candidate['global'];
        }
    }

    $target_count = min( $remaining_budget, count( $feasible ) );
    if ( $target_count <= 0 ) {
        return array();
    }

    $selected = array();
    $selected_globals = array();
    $lower_bound = $last_ad_after > 0 ? $last_ad_after + $min_between : 0;
    $last_position = end( $by_position );
    $last_global = is_array( $last_position ) ? (int) ( $last_position['global'] ?? 0 ) : 0;
    reset( $by_position );

    for ( $selection_index = 0; $selection_index < $target_count; $selection_index++ ) {
        $remaining_after_this = $target_count - $selection_index - 1;
        $upper_bound = $last_global - ( $remaining_after_this * $min_between );
        $pool = array();

        foreach ( $candidate_slots as $candidate ) {
            if ( in_array( $candidate['global'], $selected_globals, true ) ) {
                continue;
            }

            if ( $candidate['global'] < $lower_bound || $candidate['global'] > $upper_bound ) {
                continue;
            }

            $pool[] = $candidate;
        }

        if ( empty( $pool ) ) {
            break;
        }

        usort(
            $pool,
            static function ( $a, $b ) {
                $score_compare = strcmp( $a['score'], $b['score'] );
                if ( 0 !== $score_compare ) {
                    return $score_compare;
                }

                return $a['global'] <=> $b['global'];
            }
        );

        $chosen = $pool[0];
        $selected[] = $chosen;
        $selected_globals[] = $chosen['global'];
        $lower_bound = $chosen['global'] + max( 1, $min_between );
    }

    $slots = array_map(
        static function ( $candidate ) {
            return (int) $candidate['local'];
        },
        $selected
    );

    sort( $slots, SORT_NUMERIC );

    return $slots;
}

/**
 * Return the configured automated Tool Directory in-grid advertisement.
 *
 * @return string
 */
function tnt_get_tool_directory_in_grid_ad_markup() {
    $policy = tnt_get_tool_directory_ad_policy();
    if ( 'custom' !== ( $policy['mode'] ?? 'disabled' ) ) {
        return '';
    }

    return tnt_get_monetization_ad_unit_markup(
        sanitize_key( (string) ( $policy['ad_unit'] ?? 'rectangle' ) ),
        'directory-in-grid'
    );
}

function tnt_get_monetization_placement_config( $placement ) {
    $placement = sanitize_key( $placement );
    if ( '' === $placement ) {
        return array();
    }

    $settings = tnt_get_monetization_settings();
    if ( empty( $settings['placements'][ $placement ] ) || ! is_array( $settings['placements'][ $placement ] ) ) {
        return array();
    }

    $config = $settings['placements'][ $placement ];
    $mode = sanitize_key( $config['mode'] ?? 'disabled' );
    if ( ! in_array( $mode, array( 'disabled', 'tool', 'custom' ), true ) ) {
        $mode = 'disabled';
    }

    $tool_ids = array();
    if ( ! empty( $config['tool_ids'] ) && is_array( $config['tool_ids'] ) ) {
        foreach ( $config['tool_ids'] as $tool_id ) {
            $tool_id = absint( $tool_id );
            if ( $tool_id > 0 ) {
                $tool_ids[] = $tool_id;
            }
        }
    }

    return array(
        'mode' => $mode,
        'tool_ids' => array_values( array_unique( $tool_ids ) ),
        'custom_code' => (string) ( $config['custom_code'] ?? '' ),
    );
}

function tnt_get_promo_current_tool_id( $tool ) {
    if ( $tool instanceof WP_Post ) {
        return absint( $tool->ID );
    }

    if ( is_array( $tool ) ) {
        if ( ! empty( $tool['id'] ) ) {
            return absint( $tool['id'] );
        }
        if ( ! empty( $tool['post'] ) && $tool['post'] instanceof WP_Post ) {
            return absint( $tool['post']->ID );
        }
    }

    return 0;
}

function tnt_get_eligible_tool_promo_ids( $placement, $tool = array() ) {
    $config = tnt_get_monetization_placement_config( $placement );
    if ( 'tool' !== ( $config['mode'] ?? 'disabled' ) ) {
        return array();
    }

    $current_tool_id = tnt_get_promo_current_tool_id( $tool );
    $eligible_ids = array();

    foreach ( $config['tool_ids'] ?? array() as $tool_id ) {
        $tool_id = absint( $tool_id );
        if ( $tool_id <= 0 || ( $current_tool_id > 0 && $current_tool_id === $tool_id ) ) {
            continue;
        }

        $promoted_tool = tnt_get_tool( $tool_id );
        if ( ! $promoted_tool instanceof WP_Post || 'tool' !== $promoted_tool->post_type || 'publish' !== $promoted_tool->post_status ) {
            continue;
        }

        $eligible_ids[] = $tool_id;
    }

    return array_values( array_unique( $eligible_ids ) );
}

function tnt_choose_tool_promo_id( $placement, $tool = array() ) {
    $eligible_ids = tnt_get_eligible_tool_promo_ids( $placement, $tool );
    if ( empty( $eligible_ids ) ) {
        return 0;
    }

    $random_key = array_rand( $eligible_ids );
    return absint( $eligible_ids[ $random_key ] );
}

function tnt_get_tool_promo_placement( $placement, $tool = array() ) {
    $promoted_tool_id = tnt_choose_tool_promo_id( $placement, $tool );
    if ( $promoted_tool_id <= 0 ) {
        return array();
    }

    $promoted_tool = tnt_get_tool( $promoted_tool_id );
    if ( ! $promoted_tool instanceof WP_Post ) {
        return array();
    }

    $actions = tnt_get_tool_actions( $promoted_tool );
    $url = '';
    $external = false;

    if ( ! empty( $actions['use_tool']['url'] ) ) {
        $url = (string) $actions['use_tool']['url'];
        $external = ! empty( $actions['use_tool']['external'] );
    }

    if ( '' === $url ) {
        $url = (string) get_permalink( $promoted_tool );
    }

    if ( '' === $url ) {
        return array();
    }

    return array(
        'type'        => 'house-tool',
        'provider'    => 'tool',
        'placement'   => sanitize_key( $placement ),
        'tool_id'     => $promoted_tool->ID,
        'label'       => __( 'Featured Tool', 'toolntip-core' ),
        'eyebrow'     => __( 'ToolNTip', 'toolntip-core' ),
        'title'       => get_the_title( $promoted_tool ),
        'description' => tnt_get_tool_shell_tagline( $promoted_tool ),
        'cta_label'   => __( 'Open Tool', 'toolntip-core' ),
        'url'         => $url,
        'external'    => $external,
        'logo'        => tnt_get_tool_logo( $promoted_tool ),
    );
}

function tnt_get_monetization_placement( $placement, $tool = array() ) {
    $config = tnt_get_monetization_placement_config( $placement );
    $mode = $config['mode'] ?? 'disabled';

    if ( 'disabled' === $mode ) {
        return array();
    }

    if ( 'tool' === $mode ) {
        return tnt_get_tool_promo_placement( $placement, $tool );
    }

    if ( 'custom' === $mode ) {
        $code = trim( (string) ( $config['custom_code'] ?? '' ) );
        if ( '' === $code ) {
            return array();
        }

        return array(
            'type' => 'custom-code',
            'provider' => 'custom',
            'placement' => sanitize_key( $placement ),
            'code' => $code,
        );
    }

    return array();
}

function tnt_get_global_tool_promo_id( $placement ) {
    return tnt_choose_tool_promo_id( $placement, array() );
}

function tnt_get_promo_placement( $placement, $tool = array() ) {
    if ( 'tool-detail-hero' === $placement ) {
        return array(
            'type' => 'house',
            'provider' => 'house',
            'label' => __( 'Featured', 'toolntip-core' ),
            'eyebrow' => __( 'ToolNTip', 'toolntip-core' ),
            'title' => __( 'Discover More Developer Tools', 'toolntip-core' ),
            'description' => __( 'Explore curated tools to improve your development workflow.', 'toolntip-core' ),
            'cta_label' => __( 'Explore Tools', 'toolntip-core' ),
            'url' => home_url( '/tools/' ),
            'external' => false,
        );
    }

    return tnt_get_monetization_placement( $placement, $tool );
}


/**
 * Determine whether a WordPress page contains a manual ToolNTip ad shortcode.
 *
 * Elementor stores widget configuration in _elementor_data rather than always
 * persisting the shortcode in post_content, so both sources are inspected.
 *
 * @param int $post_id Page ID.
 *
 * @return bool
 */
function tnt_page_uses_manual_ad_shortcode( $post_id ) {
    $post_id = absint( $post_id );
    if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
        return false;
    }

    $shortcodes = array(
        'tnt_ad_leaderboard',
        'tnt_ad_rectangle',
        'tnt_ad_horizontal',
        'tnt_ad_sidebar',
        'tnt_ad_mobile',
    );

    $post = get_post( $post_id );
    $content = $post instanceof WP_Post ? (string) $post->post_content : '';
    $elementor_data = (string) get_post_meta( $post_id, '_elementor_data', true );

    foreach ( $shortcodes as $shortcode ) {
        if ( '' !== $content && has_shortcode( $content, $shortcode ) ) {
            return true;
        }

        if ( '' !== $elementor_data && false !== strpos( $elementor_data, '[' . $shortcode ) ) {
            return true;
        }
    }

    return false;
}

function tnt_is_monetization_frontend_context() {
    if ( is_admin() ) {
        return false;
    }

    if ( is_post_type_archive( 'tool' ) ) {
        return true;
    }

    $object_id = get_queried_object_id();
    if ( $object_id <= 0 ) {
        return false;
    }

    if ( 'tool' === get_post_type( $object_id ) ) {
        return true;
    }

    if ( 'page' === get_post_type( $object_id ) ) {
        if ( absint( get_post_meta( $object_id, '_tnt_tool_context_id', true ) ) > 0 ) {
            return true;
        }

        return tnt_page_uses_manual_ad_shortcode( $object_id );
    }

    return false;
}

function tnt_render_monetization_global_loader() {
    if ( ! tnt_is_monetization_frontend_context() ) {
        return;
    }

    $settings = tnt_get_monetization_settings();
    $code = trim( (string) ( $settings['global_loader_code'] ?? '' ) );
    if ( '' === $code ) {
        return;
    }

    echo "\n<!-- ToolNTip Monetization Loader -->\n";
    echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo "\n<!-- /ToolNTip Monetization Loader -->\n";
}
add_action( 'wp_head', 'tnt_render_monetization_global_loader', 30 );
