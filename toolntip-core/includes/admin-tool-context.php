<?php
/**
 * Application Page Tool Context.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Application Page -> Tool relationship.
 */
function tnt_register_tool_shell_page_context_meta() {

    register_post_meta(
        'page',
        '_tnt_tool_context_id',
        array(
            'type'              => 'integer',
            'single'            => true,
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'show_in_rest'      => false,
            'auth_callback'     => static function () {
                return current_user_can( 'edit_pages' );
            },
        )
    );
}
add_action( 'init', 'tnt_register_tool_shell_page_context_meta' );

/**
 * Add ToolNTip Application Context meta box to Pages.
 */
function tnt_add_tool_shell_context_meta_box() {

    add_meta_box(
        'tnt-tool-shell-context',
        __( 'ToolNTip Application Context', 'toolntip-core' ),
        'tnt_render_tool_shell_context_meta_box',
        'page',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes_page', 'tnt_add_tool_shell_context_meta_box' );

/**
 * Render linked Tool selector.
 *
 * @param WP_Post $post Current Page.
 */
function tnt_render_tool_shell_context_meta_box( $post ) {

    $selected_tool_id = absint(
        get_post_meta( $post->ID, '_tnt_tool_context_id', true )
    );

    $tools = get_posts(
        array(
            'post_type'      => 'tool',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    wp_nonce_field(
        'tnt_save_tool_shell_context',
        'tnt_tool_shell_context_nonce'
    );
    ?>
    <p>
        <label for="tnt-tool-context-id">
            <?php esc_html_e( 'Linked Tool', 'toolntip-core' ); ?>
        </label>
    </p>

    <select
        id="tnt-tool-context-id"
        name="tnt_tool_context_id"
    >
        <option value="0">
            <?php esc_html_e( '— No linked Tool —', 'toolntip-core' ); ?>
        </option>

        <?php foreach ( $tools as $tool ) : ?>
            <option
                value="<?php echo esc_attr( $tool->ID ); ?>"
                <?php selected( $selected_tool_id, $tool->ID ); ?>
            >
                <?php echo esc_html( get_the_title( $tool ) ); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <p class="description">
        <?php
        esc_html_e(
            'Select the Tool represented by this application page.',
            'toolntip-core'
        );
        ?>
    </p>
    <?php
}

/**
 * Save Application Page -> Tool relationship.
 *
 * @param int $post_id Page ID.
 */
function tnt_save_tool_shell_context_meta( $post_id ) {

    if (
        defined( 'DOING_AUTOSAVE' ) &&
        DOING_AUTOSAVE
    ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if (
        ! isset( $_POST['tnt_tool_shell_context_nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['tnt_tool_shell_context_nonce']
                )
            ),
            'tnt_save_tool_shell_context'
        )
    ) {
        return;
    }

    if ( ! current_user_can( 'edit_page', $post_id ) ) {
        return;
    }

    $tool_id = isset( $_POST['tnt_tool_context_id'] )
        ? absint(
            wp_unslash(
                $_POST['tnt_tool_context_id']
            )
        )
        : 0;

    if ( $tool_id <= 0 ) {
        delete_post_meta(
            $post_id,
            '_tnt_tool_context_id'
        );
        return;
    }

    $tool = tnt_get_tool( $tool_id );

    if ( ! $tool ) {
        return;
    }

    update_post_meta(
        $post_id,
        '_tnt_tool_context_id',
        $tool->ID
    );
}
add_action(
    'save_post_page',
    'tnt_save_tool_shell_context_meta'
);