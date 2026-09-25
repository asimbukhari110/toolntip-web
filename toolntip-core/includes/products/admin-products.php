<?php
/**
 * ToolNTip Product administration UI.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether the current user may manage Product-platform fields.
 *
 * @param int $post_id Product post ID.
 * @return bool
 */
function tnt_current_user_can_manage_product( $post_id = 0 ) {
    $post_id = absint( $post_id );

    if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
        return false;
    }

    return current_user_can( 'manage_options' ) || current_user_can( 'manage_toolntip_products' );
}

/**
 * Add Product administration meta boxes.
 *
 * @return void
 */
function tnt_add_product_admin_meta_boxes() {
    add_meta_box(
        'tnt-product-facts',
        __( 'Product Facts', 'toolntip-core' ),
        'tnt_render_product_facts_meta_box',
        'tnt_product',
        'normal',
        'high'
    );

    add_meta_box(
        'tnt-product-relationships',
        __( 'Relationships', 'toolntip-core' ),
        'tnt_render_product_relationships_meta_box',
        'tnt_product',
        'normal',
        'default'
    );

    add_meta_box(
        'tnt-product-capabilities',
        __( 'Key Capabilities', 'toolntip-core' ),
        'tnt_render_product_capabilities_meta_box',
        'tnt_product',
        'normal',
        'default'
    );

    add_meta_box(
        'tnt-product-editions-releases',
        __( 'Editions & Releases', 'toolntip-core' ),
        'tnt_render_product_editions_releases_meta_box',
        'tnt_product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes_tnt_product', 'tnt_add_product_admin_meta_boxes' );

/**
 * Render Product Facts.
 *
 * @param WP_Post $post Product post.
 * @return void
 */
function tnt_render_product_facts_meta_box( $post ) {
    wp_nonce_field( 'tnt_save_product_admin', 'tnt_product_admin_nonce' );

    $statuses = tnt_get_product_statuses();
    $status   = tnt_get_product_status( $post );

    $fields = array(
        '_tnt_product_positioning'        => __( 'Short Positioning', 'toolntip-core' ),
        '_tnt_product_category'           => __( 'Category', 'toolntip-core' ),
        '_tnt_product_deployment'         => __( 'Deployment', 'toolntip-core' ),
        '_tnt_product_technology'         => __( 'Technology', 'toolntip-core' ),
        '_tnt_product_supported_platform' => __( 'Supported Platform', 'toolntip-core' ),
    );
    ?>
    <div class="tnt-product-admin-grid">
        <p class="tnt-product-admin-field">
            <label for="tnt-product-status"><strong><?php esc_html_e( 'Product Status', 'toolntip-core' ); ?></strong></label>
            <select id="tnt-product-status" name="tnt_product_status">
                <?php foreach ( $statuses as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php foreach ( $fields as $meta_key => $label ) : ?>
            <p class="tnt-product-admin-field">
                <label for="<?php echo esc_attr( $meta_key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
                <input class="widefat" type="text" id="<?php echo esc_attr( $meta_key ); ?>" name="tnt_product_meta[<?php echo esc_attr( $meta_key ); ?>]" value="<?php echo esc_attr( get_post_meta( $post->ID, $meta_key, true ) ); ?>">
            </p>
        <?php endforeach; ?>
    </div>

    <p class="tnt-product-admin-field">
        <label for="tnt-product-system-requirements"><strong><?php esc_html_e( 'System Requirements', 'toolntip-core' ); ?></strong></label>
        <textarea class="widefat" rows="6" id="tnt-product-system-requirements" name="tnt_product_system_requirements"><?php echo esc_textarea( get_post_meta( $post->ID, '_tnt_product_system_requirements', true ) ); ?></textarea>
    </p>
    <?php
}

/**
 * Render Product relationship controls.
 *
 * @param WP_Post $post Product post.
 * @return void
 */
function tnt_render_product_relationships_meta_box( $post ) {
    $selected_tool_ids = tnt_get_product_tool_ids( $post->ID, false );
    $tools = get_posts(
        array(
            'post_type'      => 'tool',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    $selected_resource_ids = tnt_get_product_resource_ids( $post->ID, false );
    $resources = get_posts(
        array(
            'post_type'      => 'resource',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
    ?>
    <div class="tnt-product-admin-field">
        <strong><?php esc_html_e( 'Associated Tools', 'toolntip-core' ); ?></strong>
        <p class="description"><?php esc_html_e( 'Add companion Tools and use Move Up / Move Down to control public display order. A Tool can belong to only one Product.', 'toolntip-core' ); ?></p>
        <div class="tnt-product-tool-picker">
            <select id="tnt-product-tool-selector">
                <option value=""><?php esc_html_e( 'Select a Tool…', 'toolntip-core' ); ?></option>
                <?php foreach ( $tools as $tool ) : ?>
                    <?php
                    $owner    = tnt_get_product_for_tool( $tool->ID, false );
                    $disabled = $owner && absint( $owner->ID ) !== absint( $post->ID );
                    ?>
                    <option value="<?php echo esc_attr( $tool->ID ); ?>" data-title="<?php echo esc_attr( get_the_title( $tool ) ); ?>" <?php disabled( $disabled ); ?>>
                        <?php echo esc_html( get_the_title( $tool ) . ( $disabled ? ' — ' . __( 'already linked', 'toolntip-core' ) : '' ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button" id="tnt-product-add-tool"><?php esc_html_e( 'Add Tool', 'toolntip-core' ); ?></button>
        </div>

        <ol class="tnt-product-sortable-list" id="tnt-product-tool-list">
            <?php foreach ( $selected_tool_ids as $tool_id ) : ?>
                <?php $tool = get_post( $tool_id ); ?>
                <?php if ( $tool && 'tool' === $tool->post_type ) : ?>
                    <li class="tnt-product-related-row" data-id="<?php echo esc_attr( $tool_id ); ?>">
                        <span class="tnt-product-related-title"><?php echo esc_html( get_the_title( $tool ) ); ?></span>
                        <input type="hidden" name="tnt_product_tool_ids[]" value="<?php echo esc_attr( $tool_id ); ?>">
                        <span class="tnt-product-row-actions">
                            <button type="button" class="button-link tnt-move-up"><?php esc_html_e( 'Move Up', 'toolntip-core' ); ?></button>
                            <button type="button" class="button-link tnt-move-down"><?php esc_html_e( 'Move Down', 'toolntip-core' ); ?></button>
                            <button type="button" class="button-link-delete tnt-remove-row"><?php esc_html_e( 'Remove', 'toolntip-core' ); ?></button>
                        </span>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>

    <div class="tnt-product-admin-field">
        <strong><?php esc_html_e( 'Related Resources', 'toolntip-core' ); ?></strong>
        <p class="description"><?php esc_html_e( 'Add Resources and use Move Up / Move Down to control public display order.', 'toolntip-core' ); ?></p>
        <div class="tnt-product-resource-picker">
            <select id="tnt-product-resource-selector">
                <option value=""><?php esc_html_e( 'Select a Resource…', 'toolntip-core' ); ?></option>
                <?php foreach ( $resources as $resource ) : ?>
                    <option value="<?php echo esc_attr( $resource->ID ); ?>" data-title="<?php echo esc_attr( get_the_title( $resource ) ); ?>"><?php echo esc_html( get_the_title( $resource ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button" id="tnt-product-add-resource"><?php esc_html_e( 'Add Resource', 'toolntip-core' ); ?></button>
        </div>

        <ol class="tnt-product-sortable-list" id="tnt-product-resource-list">
            <?php foreach ( $selected_resource_ids as $resource_id ) : ?>
                <?php $resource = get_post( $resource_id ); ?>
                <?php if ( $resource && 'resource' === $resource->post_type ) : ?>
                    <li class="tnt-product-related-row" data-id="<?php echo esc_attr( $resource_id ); ?>">
                        <span class="tnt-product-related-title"><?php echo esc_html( get_the_title( $resource ) ); ?></span>
                        <input type="hidden" name="tnt_product_resource_ids[]" value="<?php echo esc_attr( $resource_id ); ?>">
                        <span class="tnt-product-row-actions">
                            <button type="button" class="button-link tnt-move-up"><?php esc_html_e( 'Move Up', 'toolntip-core' ); ?></button>
                            <button type="button" class="button-link tnt-move-down"><?php esc_html_e( 'Move Down', 'toolntip-core' ); ?></button>
                            <button type="button" class="button-link-delete tnt-remove-row"><?php esc_html_e( 'Remove', 'toolntip-core' ); ?></button>
                        </span>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php
}

/**
 * Render capability editor.
 *
 * @param WP_Post $post Product post.
 * @return void
 */
function tnt_render_product_capabilities_meta_box( $post ) {
    $capabilities = tnt_get_product_capabilities( $post );
    $icons        = tnt_get_product_capability_icons();
    ?>
    <p class="description"><?php esc_html_e( 'Each capability requires a title and description. Icon is optional.', 'toolntip-core' ); ?></p>
    <div id="tnt-product-capabilities-list">
        <?php foreach ( $capabilities as $index => $capability ) : ?>
            <?php tnt_render_product_capability_admin_row( $index, $capability, $icons ); ?>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button" id="tnt-product-add-capability"><?php esc_html_e( 'Add Capability', 'toolntip-core' ); ?></button>

    <script type="text/html" id="tmpl-tnt-product-capability-row">
        <?php
        tnt_render_product_capability_admin_row(
            '__INDEX__',
            array( 'title' => '', 'description' => '', 'icon' => '', 'order' => 0 ),
            $icons
        );
        ?>
    </script>
    <?php
}

/**
 * Render one capability editor row.
 *
 * @param int|string $index Row index.
 * @param array      $capability Capability values.
 * @param array      $icons Icon options.
 * @return void
 */
function tnt_render_product_capability_admin_row( $index, $capability, $icons ) {
    $name_prefix = 'tnt_product_capabilities[' . $index . ']';
    ?>
    <div class="tnt-product-capability-row">
        <div class="tnt-product-capability-row__main">
            <p>
                <label><strong><?php esc_html_e( 'Title', 'toolntip-core' ); ?></strong></label>
                <input class="widefat" type="text" name="<?php echo esc_attr( $name_prefix ); ?>[title]" value="<?php echo esc_attr( $capability['title'] ?? '' ); ?>">
            </p>
            <p>
                <label><strong><?php esc_html_e( 'Description', 'toolntip-core' ); ?></strong></label>
                <textarea class="widefat" rows="3" name="<?php echo esc_attr( $name_prefix ); ?>[description]"><?php echo esc_textarea( $capability['description'] ?? '' ); ?></textarea>
            </p>
            <p>
                <label><strong><?php esc_html_e( 'Icon', 'toolntip-core' ); ?></strong></label>
                <select name="<?php echo esc_attr( $name_prefix ); ?>[icon]">
                    <option value=""><?php esc_html_e( 'No icon', 'toolntip-core' ); ?></option>
                    <?php foreach ( $icons as $icon_key => $icon_label ) : ?>
                        <option value="<?php echo esc_attr( $icon_key ); ?>" <?php selected( $capability['icon'] ?? '', $icon_key ); ?>><?php echo esc_html( $icon_label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="tnt-capability-order" type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[order]" value="<?php echo esc_attr( absint( $capability['order'] ?? 0 ) ); ?>">
            </p>
        </div>
        <div class="tnt-product-row-actions">
            <button type="button" class="button tnt-move-up"><?php esc_html_e( 'Move Up', 'toolntip-core' ); ?></button>
            <button type="button" class="button tnt-move-down"><?php esc_html_e( 'Move Down', 'toolntip-core' ); ?></button>
            <button type="button" class="button-link-delete tnt-remove-row"><?php esc_html_e( 'Remove', 'toolntip-core' ); ?></button>
        </div>
    </div>
    <?php
}

/**
 * Render Edition / Release read-only operational summary.
 *
 * @param WP_Post $post Product post.
 * @return void
 */
function tnt_render_product_editions_releases_meta_box( $post ) {
    if ( ! tnt_product_platform_ready() ) {
        echo '<p>' . esc_html__( 'Product database is currently unavailable.', 'toolntip-core' ) . '</p>';
        return;
    }

    $editions = tnt_get_product_editions( $post->ID, true );

    if ( tnt_current_user_can_manage_product( $post->ID ) ) {
        echo '<p><a class="button button-secondary" href="' . esc_url( tnt_product_release_admin_url( array( 'product_id' => $post->ID ) ) ) . '">' . esc_html__( 'Manage Editions & Releases', 'toolntip-core' ) . '</a></p>';
    }

    if ( empty( $editions ) ) {
        echo '<p>' . esc_html__( 'No Editions exist yet. Edition and Release management will appear in the governed Release administration workflow.', 'toolntip-core' ) . '</p>';
        return;
    }

    echo '<div class="tnt-product-edition-summary">';
    foreach ( $editions as $edition ) {
        $releases  = tnt_get_edition_releases( $edition->id );
        $published = array_filter( $releases, static function ( $release ) { return 'published' === $release->status; } );
        $working   = array_filter( $releases, static function ( $release ) { return in_array( $release->status, array( 'draft', 'ready' ), true ); } );
        ?>
        <div class="tnt-product-edition-summary__item">
            <strong><?php echo esc_html( $edition->name ); ?></strong>
            <span class="tnt-product-admin-badge"><?php echo esc_html( ucfirst( $edition->status ) ); ?></span>
            <?php if ( '' !== trim( (string) $edition->pricing_label ) ) : ?>
                <span><?php echo esc_html( $edition->pricing_label ); ?></span>
            <?php endif; ?>
            <div class="description">
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: published count, 2: Draft/Ready count. */
                        __( 'Published Releases: %1$d · Draft/Ready Releases: %2$d', 'toolntip-core' ),
                        count( $published ),
                        count( $working )
                    )
                );
                ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

/**
 * Save Product administration fields.
 *
 * @param int $post_id Product post ID.
 * @return void
 */
function tnt_save_product_admin_fields( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) || 'tnt_product' !== get_post_type( $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['tnt_product_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tnt_product_admin_nonce'] ) ), 'tnt_save_product_admin' ) ) {
        return;
    }

    if ( ! tnt_current_user_can_manage_product( $post_id ) ) {
        return;
    }

    $status = isset( $_POST['tnt_product_status'] ) ? tnt_sanitize_product_status( wp_unslash( $_POST['tnt_product_status'] ) ) : 'development';
    update_post_meta( $post_id, '_tnt_product_status', $status );

    $text_fields = array(
        '_tnt_product_positioning',
        '_tnt_product_category',
        '_tnt_product_deployment',
        '_tnt_product_technology',
        '_tnt_product_supported_platform',
    );
    $submitted_meta = isset( $_POST['tnt_product_meta'] ) && is_array( $_POST['tnt_product_meta'] ) ? wp_unslash( $_POST['tnt_product_meta'] ) : array();

    foreach ( $text_fields as $meta_key ) {
        $value = isset( $submitted_meta[ $meta_key ] ) ? tnt_sanitize_product_text( $submitted_meta[ $meta_key ] ) : '';
        update_post_meta( $post_id, $meta_key, $value );
    }

    $requirements = isset( $_POST['tnt_product_system_requirements'] ) ? tnt_sanitize_product_textarea( wp_unslash( $_POST['tnt_product_system_requirements'] ) ) : '';
    update_post_meta( $post_id, '_tnt_product_system_requirements', $requirements );

    $tool_ids = isset( $_POST['tnt_product_tool_ids'] ) && is_array( $_POST['tnt_product_tool_ids'] )
        ? array_map( 'absint', wp_unslash( $_POST['tnt_product_tool_ids'] ) )
        : array();
    $tool_result = tnt_set_product_tools( $post_id, $tool_ids );
    if ( is_wp_error( $tool_result ) ) {
        tnt_set_product_admin_notice( $post_id, $tool_result->get_error_message(), 'error' );
    }

    $resource_ids = isset( $_POST['tnt_product_resource_ids'] ) && is_array( $_POST['tnt_product_resource_ids'] )
        ? array_map( 'absint', wp_unslash( $_POST['tnt_product_resource_ids'] ) )
        : array();
    $resource_result = tnt_set_product_resources( $post_id, $resource_ids );
    if ( is_wp_error( $resource_result ) ) {
        tnt_set_product_admin_notice( $post_id, $resource_result->get_error_message(), 'error' );
    }

    $capabilities = isset( $_POST['tnt_product_capabilities'] ) && is_array( $_POST['tnt_product_capabilities'] )
        ? wp_unslash( $_POST['tnt_product_capabilities'] )
        : array();
    $capability_result = tnt_set_product_capabilities( $post_id, $capabilities );
    if ( is_wp_error( $capability_result ) ) {
        tnt_set_product_admin_notice( $post_id, $capability_result->get_error_message(), 'error' );
    }
}
add_action( 'save_post_tnt_product', 'tnt_save_product_admin_fields', 20 );

/**
 * Queue an admin notice across the post-save redirect.
 *
 * @param int    $product_id Product ID.
 * @param string $message Message.
 * @param string $type Notice type.
 * @return void
 */
function tnt_set_product_admin_notice( $product_id, $message, $type = 'error' ) {
    $key = 'tnt_product_admin_notice_' . absint( get_current_user_id() ) . '_' . absint( $product_id );
    set_transient(
        $key,
        array(
            'message' => sanitize_text_field( $message ),
            'type'    => in_array( $type, array( 'error', 'warning', 'success', 'info' ), true ) ? $type : 'error',
        ),
        MINUTE_IN_SECONDS
    );
}

/**
 * Render queued Product admin notice.
 *
 * @return void
 */
function tnt_render_product_admin_notice() {
    global $pagenow;

    if ( 'post.php' !== $pagenow || empty( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    $product_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! $product_id || 'tnt_product' !== get_post_type( $product_id ) ) {
        return;
    }

    $key = 'tnt_product_admin_notice_' . absint( get_current_user_id() ) . '_' . $product_id;
    $notice = get_transient( $key );
    if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
        return;
    }

    delete_transient( $key );
    $type = isset( $notice['type'] ) ? sanitize_key( $notice['type'] ) : 'error';
    echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
}
add_action( 'admin_notices', 'tnt_render_product_admin_notice' );

/**
 * Enqueue Product admin assets only on Product edit screens.
 *
 * @param string $hook_suffix Current admin hook.
 * @return void
 */
function tnt_enqueue_product_admin_assets( $hook_suffix ) {
    if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'tnt_product' !== $screen->post_type ) {
        return;
    }

    wp_enqueue_style(
        'tnt-product-admin',
        TNT_CORE_URL . 'assets/css/product-admin.css',
        array(),
        TNT_CORE_VERSION
    );

    wp_enqueue_script(
        'tnt-product-admin',
        TNT_CORE_URL . 'assets/js/product-admin.js',
        array(),
        TNT_CORE_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'tnt_enqueue_product_admin_assets' );
