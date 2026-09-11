<?php
/**
 * ToolNTip JSON Formatter runtime.
 *
 * Registers a trusted, client-side JSON formatting runtime. The runtime remains
 * dormant until the Core application shell is explicitly rendered for a Tool
 * configured with runtime ID `json_formatter`.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the JSON Formatter runtime workspace.
 *
 * @param array $context Resolved application context.
 * @return string
 */
function tnt_render_json_formatter_runtime( $context ) {
    wp_enqueue_style( 'tnt-json-formatter-runtime' );
    wp_enqueue_script( 'tnt-json-formatter-runtime' );

    $tool_id = isset( $context['tool_id'] ) ? absint( $context['tool_id'] ) : 0;

    ob_start();
    ?>
    <div class="tnt-json-formatter" data-tnt-runtime="json_formatter" data-tool-id="<?php echo esc_attr( (string) $tool_id ); ?>">
        <div class="tnt-json-formatter__header">
            <div class="tnt-json-formatter__actions" aria-label="<?php esc_attr_e( 'JSON actions', 'toolntip-core' ); ?>">
                <button type="button" class="tnt-json-formatter__button tnt-json-formatter__button--primary" data-action="format"><?php esc_html_e( 'Format', 'toolntip-core' ); ?></button>
                <button type="button" class="tnt-json-formatter__button" data-action="minify"><?php esc_html_e( 'Minify', 'toolntip-core' ); ?></button>
                <button type="button" class="tnt-json-formatter__button" data-action="validate"><?php esc_html_e( 'Validate', 'toolntip-core' ); ?></button>
                <button type="button" class="tnt-json-formatter__button" data-action="sample"><?php esc_html_e( 'Sample', 'toolntip-core' ); ?></button>
                <button type="button" class="tnt-json-formatter__button" data-action="clear"><?php esc_html_e( 'Clear', 'toolntip-core' ); ?></button>
            </div>
            <label class="tnt-json-formatter__indent">
                <span><?php esc_html_e( 'Indentation', 'toolntip-core' ); ?></span>
                <select data-setting="indent" aria-label="<?php esc_attr_e( 'Indentation', 'toolntip-core' ); ?>">
                    <option value="2" selected><?php esc_html_e( '2 spaces', 'toolntip-core' ); ?></option>
                    <option value="4"><?php esc_html_e( '4 spaces', 'toolntip-core' ); ?></option>
                    <option value="tab"><?php esc_html_e( 'Tab', 'toolntip-core' ); ?></option>
                </select>
            </label>
        </div>

        <div class="tnt-json-formatter__workspace">
            <section class="tnt-json-formatter__panel" aria-labelledby="tnt-json-input-label">
                <div class="tnt-json-formatter__panel-header">
                    <h2 id="tnt-json-input-label"><?php esc_html_e( 'Input JSON', 'toolntip-core' ); ?></h2>
                </div>
                <textarea class="tnt-json-formatter__editor" data-role="input" spellcheck="false" autocomplete="off" placeholder="<?php esc_attr_e( 'Paste JSON here…', 'toolntip-core' ); ?>"></textarea>
            </section>

            <section class="tnt-json-formatter__panel" aria-labelledby="tnt-json-output-label">
                <div class="tnt-json-formatter__panel-header">
                    <h2 id="tnt-json-output-label"><?php esc_html_e( 'Output JSON', 'toolntip-core' ); ?></h2>
                    <div class="tnt-json-formatter__result-actions">
                        <button type="button" class="tnt-json-formatter__button tnt-json-formatter__button--small" data-action="copy" disabled><?php esc_html_e( 'Copy', 'toolntip-core' ); ?></button>
                        <button type="button" class="tnt-json-formatter__button tnt-json-formatter__button--small" data-action="download" disabled><?php esc_html_e( 'Download', 'toolntip-core' ); ?></button>
                    </div>
                </div>
                <textarea class="tnt-json-formatter__editor" data-role="output" spellcheck="false" readonly placeholder="<?php esc_attr_e( 'Formatted JSON will appear here.', 'toolntip-core' ); ?>"></textarea>
            </section>
        </div>

        <div class="tnt-json-formatter__feedback" data-role="feedback" data-state="ready" role="status" aria-live="polite">
            <span data-role="message"><?php esc_html_e( 'Ready', 'toolntip-core' ); ?></span>
            <span class="tnt-json-formatter__stats" data-role="stats" hidden></span>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Register the JSON Formatter runtime with Core.
 *
 * @return void
 */
function tnt_register_json_formatter_runtime() {
    tnt_register_application_runtime(
        'json_formatter',
        array(
            'label'             => __( 'JSON Formatter', 'toolntip-core' ),
            'supported_layouts' => array( 'split', 'stacked' ),
            'default_layout'    => 'split',
            'renderer'          => 'tnt_render_json_formatter_runtime',
            'assets'            => array(
                'styles'  => array( 'tnt-json-formatter-runtime' ),
                'scripts' => array( 'tnt-json-formatter-runtime' ),
            ),
            'capabilities'      => array(
                'execution'      => 'client',
                'format'         => true,
                'minify'         => true,
                'validate'       => true,
                'sample'         => true,
                'clear'          => true,
                'copy'           => true,
                'download'       => true,
                'statistics'     => true,
                'indentation'    => true,
            ),
        )
    );
}
add_action( 'init', 'tnt_register_json_formatter_runtime', 5 );
