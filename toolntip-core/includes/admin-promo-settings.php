<?php
/**
 * ToolNTip Promotions & Monetization Settings.
 *
 * Provider-neutral administration for first-party Tool promotions and
 * trusted ad-network/custom campaign code.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tnt_get_monetization_placement_definitions() {
    return array(
        'external-hero' => array(
            'label'       => __( 'Tool Detail — Hero', 'toolntip-core' ),
            'description' => __( 'Renders in the Tool Detail page hero promotional surface.', 'toolntip-core' ),
        ),
        'external-after-hero' => array(
            'label'       => __( 'Tool Detail — After Hero', 'toolntip-core' ),
            'description' => __( 'Renders immediately after the Tool Detail page hero.', 'toolntip-core' ),
        ),
        'internal-hero' => array(
            'label'       => __( 'Application Page — Hero', 'toolntip-core' ),
            'description' => __( 'Renders in the linked Tool Application Page hero composition.', 'toolntip-core' ),
        ),
        'internal-after-app' => array(
            'label'       => __( 'Application Page — After Application', 'toolntip-core' ),
            'description' => __( 'Renders immediately after the interactive application on the linked Tool Application Page.', 'toolntip-core' ),
        ),
    );
}

/**
 * Return reusable administrator-managed ad-unit definitions.
 *
 * These units are provider-neutral storage slots. The administrator may paste
 * AdSense, another ad network, or other trusted campaign markup into them.
 *
 * @return array
 */
function tnt_get_monetization_ad_unit_definitions() {
    return array(
        'leaderboard' => array(
            'label'       => __( 'Leaderboard / Wide Banner', 'toolntip-core' ),
            'size'        => '970 × 90',
            'dimensions'  => '970x90',
            'description' => __( 'Recommended for wide desktop banner placements such as the Tool Directory before-grid surface.', 'toolntip-core' ),
            'shortcode'   => '[tnt_ad_leaderboard]',
        ),
        'rectangle' => array(
            'label'       => __( 'Rectangle / Medium Rectangle', 'toolntip-core' ),
            'size'        => '300 × 250',
            'dimensions'  => '300x250',
            'description' => __( 'Recommended for card grids and other compact content placements.', 'toolntip-core' ),
            'shortcode'   => '[tnt_ad_rectangle]',
        ),
        'horizontal' => array(
            'label'       => __( 'Horizontal Banner', 'toolntip-core' ),
            'size'        => '728 × 90',
            'dimensions'  => '728x90',
            'description' => __( 'Recommended for horizontal content breaks and medium-width page sections.', 'toolntip-core' ),
            'shortcode'   => '[tnt_ad_horizontal]',
        ),
        'sidebar' => array(
            'label'       => __( 'Sidebar / Half Page', 'toolntip-core' ),
            'size'        => '300 × 600',
            'dimensions'  => '300x600',
            'description' => __( 'Recommended for tall sidebar or rail placements.', 'toolntip-core' ),
            'shortcode'   => '[tnt_ad_sidebar]',
        ),
        'mobile' => array(
            'label'       => __( 'Mobile Banner', 'toolntip-core' ),
            'size'        => '320 × 100',
            'dimensions'  => '320x100',
            'description' => __( 'Recommended for mobile-specific banner placements when the ad network does not provide a responsive unit.', 'toolntip-core' ),
            'shortcode'   => '[tnt_ad_mobile]',
        ),
    );
}

/**
 * Return the default Tool Directory in-grid advertising policy.
 *
 * @return array
 */
function tnt_get_default_tool_directory_ad_policy() {
    return array(
        'mode'                  => 'disabled',
        'ad_unit'               => 'rectangle',
        'strategy'              => 'random',
        'max_ads'               => 2,
        'min_before_first'      => 4,
        'min_between'           => 6,
        'allow_first_row'       => false,
        'allow_final_grid_item' => false,
    );
}

/**
 * Clamp an integer setting to a known range.
 *
 * @param mixed $value Incoming value.
 * @param int   $min Minimum allowed value.
 * @param int   $max Maximum allowed value.
 * @param int   $fallback Fallback when the value is not numeric.
 *
 * @return int
 */
function tnt_sanitize_monetization_int_range( $value, $min, $max, $fallback ) {
    if ( ! is_numeric( $value ) ) {
        return (int) $fallback;
    }

    $value = (int) $value;

    return max( (int) $min, min( (int) $max, $value ) );
}

function tnt_get_default_monetization_settings() {
    $placements = array();
    $ad_units   = array();

    foreach ( tnt_get_monetization_placement_definitions() as $placement => $definition ) {
        $placements[ $placement ] = array(
            'mode'        => 'disabled',
            'tool_ids'    => array(),
            'custom_code' => '',
        );
    }

    foreach ( tnt_get_monetization_ad_unit_definitions() as $unit => $definition ) {
        $ad_units[ $unit ] = array(
            'code' => '',
        );
    }

    return array(
        'global_loader_code' => '',
        'ad_units'           => $ad_units,
        'directory_policy'   => array(
            'in_grid' => tnt_get_default_tool_directory_ad_policy(),
        ),
        'placements'         => $placements,
    );
}

function tnt_sanitize_monetization_tool_ids( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    $tool_ids = array();

    foreach ( $value as $tool_id ) {
        $tool_id = absint( $tool_id );
        if ( $tool_id > 0 ) {
            $tool_ids[] = $tool_id;
        }
    }

    return array_values( array_unique( $tool_ids ) );
}

function tnt_sanitize_monetization_settings( $value ) {
    $defaults = tnt_get_default_monetization_settings();
    $existing = get_option( 'tnt_monetization_settings', $defaults );

    if ( ! is_array( $existing ) ) {
        $existing = $defaults;
    }

    if ( ! is_array( $value ) ) {
        return $existing;
    }

    $can_save_code = current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
    $allowed_modes = array( 'disabled', 'tool', 'custom' );
    $sanitized = $defaults;

    $sanitized['global_loader_code'] = $can_save_code
        ? (string) ( $value['global_loader_code'] ?? '' )
        : (string) ( $existing['global_loader_code'] ?? '' );

    foreach ( tnt_get_monetization_ad_unit_definitions() as $unit => $definition ) {
        $incoming_unit = isset( $value['ad_units'][ $unit ] ) && is_array( $value['ad_units'][ $unit ] )
            ? $value['ad_units'][ $unit ]
            : array();

        $existing_unit = isset( $existing['ad_units'][ $unit ] ) && is_array( $existing['ad_units'][ $unit ] )
            ? $existing['ad_units'][ $unit ]
            : array();

        $sanitized['ad_units'][ $unit ] = array(
            'code' => $can_save_code
                ? (string) ( $incoming_unit['code'] ?? '' )
                : (string) ( $existing_unit['code'] ?? '' ),
        );
    }

    $default_policy = tnt_get_default_tool_directory_ad_policy();
    $incoming_policy = isset( $value['directory_policy']['in_grid'] ) && is_array( $value['directory_policy']['in_grid'] )
        ? $value['directory_policy']['in_grid']
        : array();

    $policy_mode = sanitize_key( $incoming_policy['mode'] ?? $default_policy['mode'] );
    if ( ! in_array( $policy_mode, array( 'disabled', 'custom' ), true ) ) {
        $policy_mode = $default_policy['mode'];
    }

    $ad_unit = sanitize_key( $incoming_policy['ad_unit'] ?? $default_policy['ad_unit'] );
    if ( ! array_key_exists( $ad_unit, tnt_get_monetization_ad_unit_definitions() ) ) {
        $ad_unit = $default_policy['ad_unit'];
    }

    $strategy = sanitize_key( $incoming_policy['strategy'] ?? $default_policy['strategy'] );
    if ( 'random' !== $strategy ) {
        $strategy = $default_policy['strategy'];
    }

    $sanitized['directory_policy']['in_grid'] = array(
        'mode'                  => $policy_mode,
        'ad_unit'               => $ad_unit,
        'strategy'              => $strategy,
        'max_ads'               => tnt_sanitize_monetization_int_range( $incoming_policy['max_ads'] ?? null, 0, 4, $default_policy['max_ads'] ),
        'min_before_first'      => tnt_sanitize_monetization_int_range( $incoming_policy['min_before_first'] ?? null, 0, 100, $default_policy['min_before_first'] ),
        'min_between'           => tnt_sanitize_monetization_int_range( $incoming_policy['min_between'] ?? null, 0, 100, $default_policy['min_between'] ),
        'allow_first_row'       => ! empty( $incoming_policy['allow_first_row'] ),
        'allow_final_grid_item' => ! empty( $incoming_policy['allow_final_grid_item'] ),
    );

    foreach ( tnt_get_monetization_placement_definitions() as $placement => $definition ) {
        $incoming = isset( $value['placements'][ $placement ] ) && is_array( $value['placements'][ $placement ] )
            ? $value['placements'][ $placement ]
            : array();

        $existing_placement = isset( $existing['placements'][ $placement ] ) && is_array( $existing['placements'][ $placement ] )
            ? $existing['placements'][ $placement ]
            : array();

        $mode = sanitize_key( $incoming['mode'] ?? 'disabled' );
        if ( ! in_array( $mode, $allowed_modes, true ) ) {
            $mode = 'disabled';
        }

        $sanitized['placements'][ $placement ] = array(
            'mode'        => $mode,
            'tool_ids'    => tnt_sanitize_monetization_tool_ids( $incoming['tool_ids'] ?? array() ),
            'custom_code' => $can_save_code
                ? (string) ( $incoming['custom_code'] ?? '' )
                : (string) ( $existing_placement['custom_code'] ?? '' ),
        );
    }

    return $sanitized;
}

function tnt_register_monetization_settings() {
    register_setting(
        'tnt_monetization_settings_group',
        'tnt_monetization_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'tnt_sanitize_monetization_settings',
            'default'           => tnt_get_default_monetization_settings(),
        )
    );
}
add_action( 'admin_init', 'tnt_register_monetization_settings' );

/**
 * Purge frontend page cache after monetization settings change.
 *
 * Monetization placements can affect multiple Tool and Tool Application
 * pages, so cached frontend markup must be invalidated after the global
 * settings option is successfully updated.
 *
 * The LiteSpeed hook is intentionally optional. ToolNTip Core does not
 * depend on LiteSpeed Cache being installed or active.
 */
function tnt_purge_monetization_cache_after_update( $old_value, $value, $option ) {

    if ( 'tnt_monetization_settings' !== $option ) {
        return;
    }

    if ( $old_value === $value ) {
        return;
    }

    if ( has_action( 'litespeed_purge_all' ) ) {
        do_action( 'litespeed_purge_all' );
    }
}

add_action(
    'update_option_tnt_monetization_settings',
    'tnt_purge_monetization_cache_after_update',
    10,
    3
);

function tnt_add_promo_settings_page() {
    add_submenu_page(
        'edit.php?post_type=tool',
        __( 'ToolNTip Promotions', 'toolntip-core' ),
        __( 'Promotions', 'toolntip-core' ),
        'manage_options',
        'tnt-promo-settings',
        'tnt_render_promo_settings_page'
    );
}
add_action( 'admin_menu', 'tnt_add_promo_settings_page' );

function tnt_get_promotion_admin_tools() {
    return get_posts(
        array(
            'post_type'      => 'tool',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
}

function tnt_render_monetization_tool_pool_field( $placement, $selected_ids, $tools ) {
    $field_name = sprintf(
        'tnt_monetization_settings[placements][%s][tool_ids][]',
        $placement
    );
    ?>
    <select name="<?php echo esc_attr( $field_name ); ?>" multiple size="8" style="min-width:360px;max-width:100%;">
        <?php foreach ( $tools as $tool ) : ?>
            <option value="<?php echo esc_attr( $tool->ID ); ?>" <?php selected( in_array( (int) $tool->ID, $selected_ids, true ) ); ?>>
                <?php echo esc_html( get_the_title( $tool ) ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php esc_html_e( 'Select one or more Tools. One eligible Tool is chosen randomly; the current Tool is automatically excluded.', 'toolntip-core' ); ?>
    </p>
    <?php
}

function tnt_render_promo_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings = function_exists( 'tnt_get_monetization_settings' )
        ? tnt_get_monetization_settings()
        : get_option( 'tnt_monetization_settings', tnt_get_default_monetization_settings() );

    $tools = tnt_get_promotion_admin_tools();
    $can_save_code = current_user_can( 'unfiltered_html' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'ToolNTip Promotions', 'toolntip-core' ); ?></h1>
        <p><?php esc_html_e( 'Configure ToolNTip first-party promotions, ad-network placements, or trusted custom campaign code from one provider-neutral control plane.', 'toolntip-core' ); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields( 'tnt_monetization_settings_group' ); ?>

            <h2><?php esc_html_e( 'Global Ad / Network Loader', 'toolntip-core' ); ?></h2>
            <p><?php esc_html_e( 'Optional loader code that should be available once on Tool pages, linked Application pages, and the Tool Directory archive.', 'toolntip-core' ); ?></p>

            <?php if ( $can_save_code ) : ?>
                <textarea name="tnt_monetization_settings[global_loader_code]" rows="8" class="large-text code" spellcheck="false"><?php echo esc_textarea( $settings['global_loader_code'] ?? '' ); ?></textarea>
            <?php else : ?>
                <textarea rows="6" class="large-text code" disabled><?php esc_html_e( 'Trusted code editing requires the unfiltered_html capability.', 'toolntip-core' ); ?></textarea>
            <?php endif; ?>

            <hr>
            <h2><?php esc_html_e( 'Reusable Ad Units', 'toolntip-core' ); ?></h2>
            <p><?php esc_html_e( 'Store provider-neutral ad-network or trusted campaign code once and reuse it through ToolNTip placements or the listed Elementor shortcodes.', 'toolntip-core' ); ?></p>

            <?php foreach ( tnt_get_monetization_ad_unit_definitions() as $unit => $definition ) : ?>
                <?php
                $unit_settings = isset( $settings['ad_units'][ $unit ] ) && is_array( $settings['ad_units'][ $unit ] )
                    ? $settings['ad_units'][ $unit ]
                    : array();
                $unit_code = (string) ( $unit_settings['code'] ?? '' );
                ?>
                <div class="tnt-admin-ad-unit" style="margin:20px 0;padding:18px 20px;border:1px solid #dcdcde;background:#fff;">
                    <h3 style="margin-top:0;">
                        <?php echo esc_html( $definition['label'] ); ?>
                        <span style="font-weight:400;color:#646970;">— <?php echo esc_html( $definition['size'] ); ?> px</span>
                    </h3>
                    <p><?php echo esc_html( $definition['description'] ); ?></p>
                    <p>
                        <strong><?php esc_html_e( 'Elementor shortcode:', 'toolntip-core' ); ?></strong>
                        <code><?php echo esc_html( $definition['shortcode'] ); ?></code>
                    </p>

                    <?php if ( $can_save_code ) : ?>
                        <textarea
                            name="<?php echo esc_attr( sprintf( 'tnt_monetization_settings[ad_units][%s][code]', $unit ) ); ?>"
                            rows="6"
                            class="large-text code"
                            spellcheck="false"
                            placeholder="<?php echo esc_attr( sprintf( __( 'Paste trusted %s ad-network or campaign code here.', 'toolntip-core' ), $definition['size'] ) ); ?>"
                        ><?php echo esc_textarea( $unit_code ); ?></textarea>
                    <?php else : ?>
                        <textarea rows="5" class="large-text code" disabled><?php esc_html_e( 'Trusted code editing requires the unfiltered_html capability.', 'toolntip-core' ); ?></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php
            $default_directory_policy = tnt_get_default_tool_directory_ad_policy();
            $directory_policy = isset( $settings['directory_policy']['in_grid'] ) && is_array( $settings['directory_policy']['in_grid'] )
                ? wp_parse_args( $settings['directory_policy']['in_grid'], $default_directory_policy )
                : $default_directory_policy;
            ?>

            <hr>
            <h2><?php esc_html_e( 'Tool Directory — In-Grid Advertising Policy', 'toolntip-core' ); ?></h2>
            <p><?php esc_html_e( 'Controls automatic sponsored-card insertion in the Tool Directory. Manual Elementor ad shortcodes and the dedicated before-grid banner do not consume this budget.', 'toolntip-core' ); ?></p>

            <div class="tnt-admin-directory-ad-policy" style="margin:20px 0;padding:20px;border:1px solid #dcdcde;background:#fff;">
                <table class="form-table" role="presentation"><tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Mode', 'toolntip-core' ); ?></th>
                        <td>
                            <select name="tnt_monetization_settings[directory_policy][in_grid][mode]">
                                <option value="disabled" <?php selected( $directory_policy['mode'], 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'toolntip-core' ); ?></option>
                                <option value="custom" <?php selected( $directory_policy['mode'], 'custom' ); ?>><?php esc_html_e( 'Ad Network / Custom Code', 'toolntip-core' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Ad Unit', 'toolntip-core' ); ?></th>
                        <td>
                            <select name="tnt_monetization_settings[directory_policy][in_grid][ad_unit]">
                                <?php foreach ( tnt_get_monetization_ad_unit_definitions() as $unit => $definition ) : ?>
                                    <option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $directory_policy['ad_unit'], $unit ); ?>>
                                        <?php echo esc_html( sprintf( '%s — %s px', $definition['label'], $definition['size'] ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Rectangle / Medium Rectangle (300 × 250) is the recommended default for a Tool card grid.', 'toolntip-core' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Placement Strategy', 'toolntip-core' ); ?></th>
                        <td>
                            <select name="tnt_monetization_settings[directory_policy][in_grid][strategy]">
                                <option value="random" <?php selected( $directory_policy['strategy'], 'random' ); ?>><?php esc_html_e( 'Random (Controlled)', 'toolntip-core' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Maximum In-Grid Ads per Archive View', 'toolntip-core' ); ?></th>
                        <td>
                            <input type="number" min="0" max="4" step="1" name="tnt_monetization_settings[directory_policy][in_grid][max_ads]" value="<?php echo esc_attr( (int) $directory_policy['max_ads'] ); ?>" class="small-text">
                            <p class="description"><?php esc_html_e( 'Allowed range: 0–4. Default: 2. This is the automated in-grid session budget.', 'toolntip-core' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Minimum Tools Before First Ad', 'toolntip-core' ); ?></th>
                        <td>
                            <input type="number" min="0" max="100" step="1" name="tnt_monetization_settings[directory_policy][in_grid][min_before_first]" value="<?php echo esc_attr( (int) $directory_policy['min_before_first'] ); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Minimum Tools Between Ads', 'toolntip-core' ); ?></th>
                        <td>
                            <input type="number" min="0" max="100" step="1" name="tnt_monetization_settings[directory_policy][in_grid][min_between]" value="<?php echo esc_attr( (int) $directory_policy['min_between'] ); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'First Row', 'toolntip-core' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="tnt_monetization_settings[directory_policy][in_grid][allow_first_row]" value="1" <?php checked( ! empty( $directory_policy['allow_first_row'] ) ); ?>>
                                <?php esc_html_e( 'Allow an automated ad in the first grid row', 'toolntip-core' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Default: No.', 'toolntip-core' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Final Grid Position', 'toolntip-core' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="tnt_monetization_settings[directory_policy][in_grid][allow_final_grid_item]" value="1" <?php checked( ! empty( $directory_policy['allow_final_grid_item'] ) ); ?>>
                                <?php esc_html_e( 'Allow an automated ad as the final grid item', 'toolntip-core' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Default: No.', 'toolntip-core' ); ?></p>
                        </td>
                    </tr>
                </tbody></table>
            </div>

            <hr>
            <h2><?php esc_html_e( 'Placement Configuration', 'toolntip-core' ); ?></h2>

            <?php foreach ( tnt_get_monetization_placement_definitions() as $placement => $definition ) : ?>
                <?php
                $placement_settings = isset( $settings['placements'][ $placement ] ) && is_array( $settings['placements'][ $placement ] )
                    ? $settings['placements'][ $placement ]
                    : array();

                $mode = sanitize_key( $placement_settings['mode'] ?? 'disabled' );
                $selected_ids = tnt_sanitize_monetization_tool_ids( $placement_settings['tool_ids'] ?? array() );
                $custom_code = (string) ( $placement_settings['custom_code'] ?? '' );
                ?>

                <div class="tnt-admin-monetization-placement" style="margin:24px 0;padding:20px;border:1px solid #dcdcde;background:#fff;">
                    <h3 style="margin-top:0;"><?php echo esc_html( $definition['label'] ); ?></h3>
                    <p><?php echo esc_html( $definition['description'] ); ?></p>

                    <table class="form-table" role="presentation"><tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Mode', 'toolntip-core' ); ?></th>
                            <td>
                                <select
                                    class="tnt-monetization-mode"
                                    name="<?php echo esc_attr( sprintf( 'tnt_monetization_settings[placements][%s][mode]', $placement ) ); ?>"
                                >
                                    <option value="disabled" <?php selected( $mode, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'toolntip-core' ); ?></option>
                                    <option value="tool" <?php selected( $mode, 'tool' ); ?>><?php esc_html_e( 'ToolNTip Promotion', 'toolntip-core' ); ?></option>
                                    <option value="custom" <?php selected( $mode, 'custom' ); ?>><?php esc_html_e( 'Ad Network / Custom Code', 'toolntip-core' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr class="tnt-monetization-mode-panel tnt-monetization-tool-panel">
                            <th scope="row"><?php esc_html_e( 'Tool Promotion Pool', 'toolntip-core' ); ?></th>
                            <td><?php tnt_render_monetization_tool_pool_field( $placement, $selected_ids, $tools ); ?></td>
                        </tr>
                        <tr class="tnt-monetization-mode-panel tnt-monetization-custom-panel">
                            <th scope="row"><?php esc_html_e( 'Ad Network / Custom Campaign Code', 'toolntip-core' ); ?></th>
                            <td>
                                <?php if ( $can_save_code ) : ?>
                                    <textarea name="<?php echo esc_attr( sprintf( 'tnt_monetization_settings[placements][%s][custom_code]', $placement ) ); ?>" rows="10" class="large-text code" spellcheck="false"><?php echo esc_textarea( $custom_code ); ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Trusted HTML/JavaScript. Use for AdSense, another ad network, your own JavaScript campaign, internal products, or other administrator-controlled promotions.', 'toolntip-core' ); ?></p>
                                <?php else : ?>
                                    <textarea rows="6" class="large-text code" disabled><?php esc_html_e( 'Trusted code editing requires the unfiltered_html capability.', 'toolntip-core' ); ?></textarea>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody></table>
                </div>
            <?php endforeach; ?>

            <?php submit_button(); ?>
        </form>

        <style>
            .tnt-admin-monetization-placement .tnt-monetization-mode-panel {
                transition: opacity 0.15s ease;
            }

            .tnt-admin-monetization-placement .tnt-monetization-mode-panel.tnt-is-inactive {
                opacity: 0.42;
            }

            .tnt-admin-monetization-placement .tnt-monetization-mode-panel.tnt-is-inactive select,
            .tnt-admin-monetization-placement .tnt-monetization-mode-panel.tnt-is-inactive textarea {
                pointer-events: none;
                background-color: #f0f0f1;
                cursor: not-allowed;
            }

            .tnt-admin-monetization-placement .tnt-monetization-mode-panel.tnt-is-inactive .description {
                opacity: 0.75;
            }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var placements = document.querySelectorAll('.tnt-admin-monetization-placement');

            placements.forEach(function (placement) {
                var modeSelect  = placement.querySelector('.tnt-monetization-mode');
                var toolPanel   = placement.querySelector('.tnt-monetization-tool-panel');
                var customPanel = placement.querySelector('.tnt-monetization-custom-panel');

                if (!modeSelect || !toolPanel || !customPanel) {
                    return;
                }

                function updateModeUI() {
                    var mode = modeSelect.value;

                    var toolActive   = mode === 'tool';
                    var customActive = mode === 'custom';

                    toolPanel.classList.toggle('tnt-is-inactive', !toolActive);
                    customPanel.classList.toggle('tnt-is-inactive', !customActive);

                    toolPanel.setAttribute('aria-disabled', toolActive ? 'false' : 'true');
                    customPanel.setAttribute('aria-disabled', customActive ? 'false' : 'true');
                }

                modeSelect.addEventListener('change', updateModeUI);

                updateModeUI();
            });
        });
        </script>
    </div>
    <?php
}
