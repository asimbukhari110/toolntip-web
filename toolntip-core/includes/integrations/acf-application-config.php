<?php
/**
 * ToolNTip Internal Application ACF Configuration.
 *
 * Adds the minimal authoring controls required to opt a Tool into the
 * registered internal-application runtime system. Runtime choices are sourced
 * from the trusted Core registry; editors never provide executable paths,
 * callbacks, JavaScript, CSS, or arbitrary asset URLs.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return runtime choices suitable for an ACF select field.
 *
 * @return array
 */
function tnt_get_application_runtime_acf_choices() {
    $choices = array();

    foreach ( tnt_get_application_runtimes() as $runtime_id => $runtime ) {
        if ( ! is_array( $runtime ) || empty( $runtime['label'] ) ) {
            continue;
        }

        $choices[ sanitize_key( $runtime_id ) ] = sanitize_text_field( (string) $runtime['label'] );
    }

    return $choices;
}

/**
 * Register the Internal Application Configuration field group.
 *
 * The group is additive to the existing Tool Details group and deliberately
 * contains only application-specific configuration. Existing Tool identity,
 * metadata, media, and supporting-content fields remain authoritative.
 *
 * @return void
 */
function tnt_register_application_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'                   => 'group_tnt_internal_application',
            'title'                 => __( 'Internal Application Configuration', 'toolntip-core' ),
            'fields'                => array(
                array(
                    'key'           => 'field_tnt_internal_application',
                    'label'         => __( 'Internal Application', 'toolntip-core' ),
                    'name'          => 'internal_application',
                    'type'          => 'true_false',
                    'instructions'  => __( 'Enable only when this Tool should run through a registered ToolNTip internal application runtime.', 'toolntip-core' ),
                    'required'      => 0,
                    'default_value' => 0,
                    'ui'            => 1,
                    'ui_on_text'    => __( 'Enabled', 'toolntip-core' ),
                    'ui_off_text'   => __( 'Disabled', 'toolntip-core' ),
                ),
                array(
                    'key'               => 'field_tnt_runtime_module',
                    'label'             => __( 'Runtime Module', 'toolntip-core' ),
                    'name'              => 'runtime_module',
                    'type'              => 'select',
                    'instructions'      => __( 'Select a trusted runtime registered by ToolNTip Core.', 'toolntip-core' ),
                    'required'          => 1,
                    'choices'           => tnt_get_application_runtime_acf_choices(),
                    'default_value'     => false,
                    'allow_null'        => 1,
                    'multiple'          => 0,
                    'ui'                => 1,
                    'ajax'              => 0,
                    'return_format'     => 'value',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'    => 'field_tnt_internal_application',
                                'operator' => '==',
                                'value'    => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key'               => 'field_tnt_workspace_layout',
                    'label'             => __( 'Workspace Layout', 'toolntip-core' ),
                    'name'              => 'workspace_layout',
                    'type'              => 'select',
                    'instructions'      => __( 'Optional override. Leave blank to use the selected runtime module default.', 'toolntip-core' ),
                    'required'          => 0,
                    'choices'           => array(
                        'split'       => __( 'Split', 'toolntip-core' ),
                        'stacked'     => __( 'Stacked', 'toolntip-core' ),
                        'form-result' => __( 'Form / Result', 'toolntip-core' ),
                        'single'      => __( 'Single', 'toolntip-core' ),
                    ),
                    'default_value'     => false,
                    'allow_null'        => 1,
                    'multiple'          => 0,
                    'ui'                => 1,
                    'ajax'              => 0,
                    'return_format'     => 'value',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'    => 'field_tnt_internal_application',
                                'operator' => '==',
                                'value'    => '1',
                            ),
                        ),
                    ),
                ),
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'tool',
                    ),
                ),
            ),
            'menu_order'            => 20,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'active'                => true,
            'show_in_rest'          => 0,
        )
    );
}
add_action( 'acf/init', 'tnt_register_application_acf_fields' );

/**
 * Refresh Runtime Module choices from the trusted registry when ACF loads it.
 *
 * @param array $field ACF field definition.
 * @return array
 */
function tnt_load_application_runtime_acf_choices( $field ) {
    $field['choices'] = tnt_get_application_runtime_acf_choices();

    return $field;
}
add_filter( 'acf/load_field/name=runtime_module', 'tnt_load_application_runtime_acf_choices' );

/**
 * Validate a selected runtime ID against the trusted registry.
 *
 * Empty values are left to ACF's required-field handling when the field is
 * visible. Non-empty values must resolve to a registered runtime.
 *
 * @param mixed $valid Current ACF validation result.
 * @param mixed $value Submitted value.
 * @return mixed
 */
function tnt_validate_application_runtime_acf_value( $valid, $value ) {
    if ( true !== $valid || '' === (string) $value ) {
        return $valid;
    }

    if ( ! tnt_application_runtime_exists( sanitize_key( (string) $value ) ) ) {
        return __( 'Select a registered ToolNTip application runtime.', 'toolntip-core' );
    }

    return $valid;
}
add_filter( 'acf/validate_value/name=runtime_module', 'tnt_validate_application_runtime_acf_value', 10, 2 );

/**
 * Validate an optional workspace-layout override.
 *
 * The resolver performs the authoritative runtime-capability check at render
 * time. This save-time check rejects values outside Core's controlled layout
 * vocabulary.
 *
 * @param mixed $valid Current ACF validation result.
 * @param mixed $value Submitted value.
 * @return mixed
 */
function tnt_validate_application_layout_acf_value( $valid, $value ) {
    if ( true !== $valid || '' === (string) $value ) {
        return $valid;
    }

    if ( ! in_array( sanitize_key( (string) $value ), tnt_get_application_workspace_layouts(), true ) ) {
        return __( 'Select a recognized ToolNTip workspace layout.', 'toolntip-core' );
    }

    return $valid;
}
add_filter( 'acf/validate_value/name=workspace_layout', 'tnt_validate_application_layout_acf_value', 10, 2 );
