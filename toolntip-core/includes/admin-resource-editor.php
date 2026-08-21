<?php
/**
 * Resource editorial foundation.
 *
 * Owns Resource Type publication enforcement, taxonomy admin ergonomics and
 * Resource relationship editing. ACF Pro is used only as an enhanced editor
 * surface when available; canonical relationship metadata remains ToolNTip
 * Core-owned.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Present canonical tool_category as "Resource Topics" on Resource admin screens.
 *
 * The taxonomy remains tool_category internally and retains Tool Categories
 * terminology on Tool screens.
 */
function tnt_resource_contextual_category_labels() {

    if ( ! is_admin() ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

    if ( ! $screen || 'resource' !== $screen->post_type ) {
        return;
    }

    $taxonomy = get_taxonomy( 'tool_category' );

    if ( ! $taxonomy || ! isset( $taxonomy->labels ) ) {
        return;
    }

    $taxonomy->label                         = __( 'Resource Topics', 'toolntip-core' );
    $taxonomy->labels->name                 = __( 'Resource Topics', 'toolntip-core' );
    $taxonomy->labels->singular_name        = __( 'Resource Topic', 'toolntip-core' );
    $taxonomy->labels->menu_name            = __( 'Resource Topics', 'toolntip-core' );
    $taxonomy->labels->all_items            = __( 'All Resource Topics', 'toolntip-core' );
    $taxonomy->labels->search_items         = __( 'Search Resource Topics', 'toolntip-core' );
    $taxonomy->labels->edit_item            = __( 'Edit Resource Topic', 'toolntip-core' );
    $taxonomy->labels->update_item          = __( 'Update Resource Topic', 'toolntip-core' );
    $taxonomy->labels->add_new_item         = __( 'Add New Resource Topic', 'toolntip-core' );
    $taxonomy->labels->new_item_name        = __( 'New Resource Topic', 'toolntip-core' );
    $taxonomy->labels->parent_item          = __( 'Parent Resource Topic', 'toolntip-core' );
    $taxonomy->labels->parent_item_colon    = __( 'Parent Resource Topic:', 'toolntip-core' );
}
add_action( 'current_screen', 'tnt_resource_contextual_category_labels', 20 );


/**
 * Provide Resource-facing labels for the shared canonical category taxonomy.
 *
 * Gutenberg builds taxonomy panels from REST schema, so current_screen is too
 * late to rename the panel reliably. This filter changes only labels returned
 * while editing Resources; the taxonomy machine name remains tool_category.
 */
function tnt_resource_tool_category_labels( $labels ) {

    $post_type = '';

    if ( isset( $_GET['post'] ) ) {
        $post = get_post( absint( $_GET['post'] ) );
        if ( $post instanceof WP_Post ) {
            $post_type = $post->post_type;
        }
    } elseif ( isset( $_GET['post_type'] ) ) {
        $post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
    }

    if ( 'resource' !== $post_type ) {
        return $labels;
    }

    $labels->name              = __( 'Resource Topics', 'toolntip-core' );
    $labels->singular_name     = __( 'Resource Topic', 'toolntip-core' );
    $labels->menu_name         = __( 'Resource Topics', 'toolntip-core' );
    $labels->all_items         = __( 'All Resource Topics', 'toolntip-core' );
    $labels->edit_item         = __( 'Edit Resource Topic', 'toolntip-core' );
    $labels->view_item         = __( 'View Resource Topic', 'toolntip-core' );
    $labels->update_item       = __( 'Update Resource Topic', 'toolntip-core' );
    $labels->add_new_item      = __( 'Add New Resource Topic', 'toolntip-core' );
    $labels->new_item_name     = __( 'New Resource Topic Name', 'toolntip-core' );
    $labels->search_items      = __( 'Search Resource Topics', 'toolntip-core' );
    $labels->parent_item       = __( 'Parent Resource Topic', 'toolntip-core' );
    $labels->parent_item_colon = __( 'Parent Resource Topic:', 'toolntip-core' );

    return $labels;
}
add_filter( 'taxonomy_labels_tool_category', 'tnt_resource_tool_category_labels', 20 );


/**
 * Register canonical Resource relationship metadata.
 */
function tnt_register_resource_relationship_meta() {

    register_post_meta(
        'resource',
        'tnt_related_tool_ids',
        array(
            'type'              => 'array',
            'single'            => true,
            'default'           => array(),
            'sanitize_callback' => 'tnt_sanitize_related_tool_ids',
            'show_in_rest'      => false,
            'auth_callback'     => static function () {
                return current_user_can( 'edit_posts' );
            },
        )
    );

    register_post_meta(
        'resource',
        'tnt_related_resource_ids',
        array(
            'type'              => 'array',
            'single'            => true,
            'default'           => array(),
            'sanitize_callback' => 'tnt_sanitize_related_resource_ids',
            'show_in_rest'      => false,
            'auth_callback'     => static function () {
                return current_user_can( 'edit_posts' );
            },
        )
    );
}
add_action( 'init', 'tnt_register_resource_relationship_meta' );

/**
 * Hide the default hierarchical Resource Type meta box in the classic editor.
 * ToolNTip supplies a single-selection editor instead.
 */
function tnt_remove_default_resource_type_meta_box() {
    remove_meta_box( 'resource_typediv', 'resource', 'side' );
}
add_action( 'add_meta_boxes_resource', 'tnt_remove_default_resource_type_meta_box', 100 );

/**
 * Register ACF-powered Resource editorial fields when ACF Pro is available.
 */
function tnt_register_resource_acf_editor_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_tnt_resource_editorial',
            'title'    => __( 'ToolNTip Resource Editorial', 'toolntip-core' ),
            'fields'   => array(
                array(
                    'key'           => 'field_tnt_related_tools',
                    'label'         => __( 'Related Tools', 'toolntip-core' ),
                    'name'          => 'tnt_related_tool_ids',
                    'type'          => 'relationship',
                    'instructions'  => __( 'Tools are ranked by ToolNTip relevance score. Review the score, then add, remove and order the final editorial selection.', 'toolntip-core' ),
                    'post_type'     => array( 'tool' ),
                    'post_status'   => array( 'publish' ),
                    'filters'       => array( 'search' ),
                    'elements'      => array( 'featured_image' ),
                    'return_format' => 'id',
                ),
                array(
                    'key'           => 'field_tnt_related_resources',
                    'label'         => __( 'Related Resources', 'toolntip-core' ),
                    'name'          => 'tnt_related_resource_ids',
                    'type'          => 'relationship',
                    'instructions'  => __( 'Search and order the published Resources that readers should visit next.', 'toolntip-core' ),
                    'post_type'     => array( 'resource' ),
                    'post_status'   => array( 'publish' ),
                    'filters'       => array( 'search', 'taxonomy' ),
                    'elements'      => array( 'featured_image' ),
                    'return_format' => 'id',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'resource',
                    ),
                ),
            ),
            'position' => 'normal',
            'style'    => 'default',
            'active'   => true,
        )
    );
}
add_action( 'acf/init', 'tnt_register_resource_acf_editor_fields' );

/**
 * Restrict ACF Related Tool choices to published Tools.
 *
 * @param array $args ACF relationship query arguments.
 * @return array
 */
function tnt_filter_acf_related_tools_query( $args, $field, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

    $resource_id = absint( $post_id );

    /*
     * WEB-007.4 / 4.2A-M1 candidate contract.
     *
     * The relationship selector must expose every published Tool. Resource
     * taxonomy state is never a candidate filter. Build the allowlist directly
     * from the posts table so no shared-taxonomy query state or third-party
     * query filter can accidentally collapse the candidate set.
     */
    global $wpdb;

    $published_tool_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = %s
             ORDER BY post_title ASC, ID ASC",
            'tool',
            'publish'
        )
    );

    $published_tool_ids = array_values( array_filter( array_map( 'absint', $published_tool_ids ) ) );

    /*
     * Apply ToolNTip relevance ordering only after the complete published
     * candidate universe has been established.
     */
    $ordered_ids = array();

    if ( $resource_id > 0 && function_exists( 'tnt_get_ranked_resource_tool_candidate_ids' ) ) {
        $ranked_ids = array_values(
            array_filter(
                array_map(
                    'absint',
                    tnt_get_ranked_resource_tool_candidate_ids( $resource_id )
                )
            )
        );

        // Keep only published Tool IDs, preserving scorer order.
        $ordered_ids = array_values( array_intersect( $ranked_ids, $published_tool_ids ) );
    }

    /*
     * Any published Tool omitted by the scorer is appended rather than hidden.
     * This guarantees that 0%/unscorable candidates remain editorially
     * selectable.
     */
    foreach ( $published_tool_ids as $tool_id ) {
        if ( ! in_array( $tool_id, $ordered_ids, true ) ) {
            $ordered_ids[] = $tool_id;
        }
    }

    $args['post_type']        = array( 'tool' );
    $args['post_status']      = array( 'publish' );
    $args['suppress_filters'] = true;

    unset(
        $args['tax_query'],
        $args['meta_query'],
        $args['meta_key'],
        $args['author'],
        $args['author__in'],
        $args['author__not_in'],
        $args['post_parent'],
        $args['post_parent__in'],
        $args['post_parent__not_in']
    );

    if ( ! empty( $ordered_ids ) ) {
        $args['post__in'] = $ordered_ids;
        $args['orderby']  = 'post__in';
        unset( $args['order'] );
    } else {
        /*
         * No published Tools genuinely exist. Use an impossible ID so ACF
         * cannot reinterpret an empty post__in array as "no restriction".
         */
        $args['post__in'] = array( 0 );
        $args['orderby']  = 'title';
        $args['order']    = 'ASC';
    }

    return $args;
}
add_filter( 'acf/fields/relationship/query/key=field_tnt_related_tools', 'tnt_filter_acf_related_tools_query', 99, 3 );

/**
 * Append the derived Resource -> Tool relevance score to ACF relationship
 * candidate labels. Score visibility is editorial-only and is never stored
 * as canonical relationship metadata.
 *
 * @param string $text    Existing ACF result label.
 * @param WP_Post $post   Candidate Tool.
 * @param array   $field  ACF field configuration.
 * @param mixed   $post_id Current Resource ID.
 * @return string
 */
function tnt_format_acf_related_tool_score( $text, $post, $field, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

    $resource_id = absint( $post_id );

    if (
        $resource_id <= 0
        || ! $post instanceof WP_Post
        || 'tool' !== $post->post_type
        || ! function_exists( 'tnt_get_resource_tool_editor_score' )
    ) {
        return $text;
    }

    $score = tnt_get_resource_tool_editor_score( $resource_id, $post->ID );

    return sprintf(
        '%1$s — %2$d%% match',
        $text,
        $score
    );
}
add_filter( 'acf/fields/relationship/result/key=field_tnt_related_tools', 'tnt_format_acf_related_tool_score', 10, 4 );

/**
 * Restrict ACF Related Resource choices and exclude the current Resource.
 *
 * @param array $args ACF relationship query arguments.
 * @param array $field ACF field configuration.
 * @param mixed $post_id Current ACF post ID.
 * @return array
 */
function tnt_filter_acf_related_resources_query( $args, $field, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

    $resource_id = absint( $post_id );

    $args['post_status'] = array( 'publish' );

    if ( $resource_id > 0 ) {
        $args['post__not_in'] = array( $resource_id );
    }

    /*
     * Rank published Resource candidates using ToolNTip's frozen Resource ->
     * Resource relevance contract. ACF search/taxonomy filtering may further
     * narrow this ranked candidate set.
     */
    if ( $resource_id > 0 && function_exists( 'tnt_get_ranked_resource_resource_candidate_ids' ) ) {

        $ranked_ids = tnt_get_ranked_resource_resource_candidate_ids( $resource_id );

        if ( ! empty( $ranked_ids ) ) {
            $args['post__in'] = $ranked_ids;
            $args['orderby']  = 'post__in';
            unset( $args['order'] );
            return $args;
        }
    }

    $args['orderby'] = 'title';
    $args['order']   = 'ASC';

    return $args;
}
add_filter( 'acf/fields/relationship/query/key=field_tnt_related_resources', 'tnt_filter_acf_related_resources_query', 10, 3 );

/**
 * Append derived Resource -> Resource relevance score to ACF candidate labels.
 *
 * Scores are editorial-only derived intelligence and are not persisted as
 * canonical relationship metadata.
 *
 * @param string  $text    Existing ACF result label.
 * @param WP_Post $post    Candidate Resource.
 * @param array   $field   ACF field configuration.
 * @param mixed   $post_id Current Resource ID.
 * @return string
 */
function tnt_format_acf_related_resource_score( $text, $post, $field, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

    $resource_id = absint( $post_id );

    if (
        $resource_id <= 0
        || ! $post instanceof WP_Post
        || 'resource' !== $post->post_type
        || ! function_exists( 'tnt_get_resource_resource_editor_score' )
    ) {
        return $text;
    }

    $score = tnt_get_resource_resource_editor_score( $resource_id, $post->ID );

    return sprintf(
        '%1$s — %2$d%% match',
        $text,
        $score
    );
}
add_filter( 'acf/fields/relationship/result/key=field_tnt_related_resources', 'tnt_format_acf_related_resource_score', 10, 4 );

/**
 * Normalize ACF Related Tool values before storage.
 *
 * @param mixed $value ACF field value.
 * @return int[]
 */
function tnt_normalize_acf_related_tools_value( $value ) {
    return tnt_normalize_resource_relationship_ids( $value, 'tool' );
}
add_filter( 'acf/update_value/key=field_tnt_related_tools', 'tnt_normalize_acf_related_tools_value' );

/**
 * Normalize ACF Related Resource values before storage.
 *
 * @param mixed $value ACF field value.
 * @param mixed $post_id Current ACF post ID.
 * @return int[]
 */
function tnt_normalize_acf_related_resources_value( $value, $post_id ) {
    return tnt_normalize_resource_relationship_ids( $value, 'resource', absint( $post_id ) );
}
add_filter( 'acf/update_value/key=field_tnt_related_resources', 'tnt_normalize_acf_related_resources_value', 10, 2 );

/**
 * Add Core fallback Resource editor boxes when ACF is unavailable.
 */
function tnt_add_resource_fallback_meta_boxes() {

    // Resource Type is always Core-owned so taxonomy terms remain the only
    // source of truth. ACF is deliberately not used for this classification.
    add_meta_box(
        'tnt-resource-type-single',
        __( 'Resource Type', 'toolntip-core' ),
        'tnt_render_resource_type_fallback_meta_box',
        'resource',
        'side',
        'high'
    );

    // ACF Pro supplies the enhanced relationship UI when available.
    if ( function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    add_meta_box(
        'tnt-resource-relationships',
        __( 'ToolNTip Resource Relationships', 'toolntip-core' ),
        'tnt_render_resource_relationships_fallback_meta_box',
        'resource',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes_resource', 'tnt_add_resource_fallback_meta_boxes', 110 );

/**
 * Render fallback Resource Type single-select radio box.
 *
 * @param WP_Post $post Current Resource.
 */
function tnt_render_resource_type_fallback_meta_box( $post ) {

    $selected = wp_get_object_terms( $post->ID, 'resource_type', array( 'fields' => 'ids' ) );
    $selected = ( ! is_wp_error( $selected ) && ! empty( $selected ) ) ? absint( reset( $selected ) ) : 0;
    $types    = get_terms(
        array(
            'taxonomy'   => 'resource_type',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    wp_nonce_field( 'tnt_save_resource_editorial', 'tnt_resource_editorial_nonce' );
    ?>
    <p class="description"><?php esc_html_e( 'Choose exactly one Resource Type before publishing.', 'toolntip-core' ); ?></p>
    <p>
        <label>
            <input type="radio" name="tnt_resource_type_id" value="0" <?php checked( 0, $selected ); ?>>
            <?php esc_html_e( '— Not selected —', 'toolntip-core' ); ?>
        </label>
    </p>
    <?php if ( ! is_wp_error( $types ) ) : ?>
        <?php foreach ( $types as $type ) : ?>
            <p>
                <label>
                    <input type="radio" name="tnt_resource_type_id" value="<?php echo esc_attr( $type->term_id ); ?>" <?php checked( $selected, $type->term_id ); ?>>
                    <?php echo esc_html( $type->name ); ?>
                </label>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
}

/**
 * Render one fallback ordered relationship editor.
 *
 * @param string $kind        Relationship kind: tool|resource.
 * @param int[]  $selected_ids Selected ordered IDs.
 * @param int    $current_id  Current Resource ID.
 */
function tnt_render_resource_relationship_fallback_editor( $kind, $selected_ids, $current_id ) {

    $is_tool       = 'tool' === $kind;
    $post_type     = $is_tool ? 'tool' : 'resource';
    $input_name    = $is_tool ? 'tnt_related_tool_ids' : 'tnt_related_resource_ids';
    $wrapper_id    = $is_tool ? 'tnt-related-tools-editor' : 'tnt-related-resources-editor';
    $candidate_id  = $wrapper_id . '-candidate';
    $add_button_id = $wrapper_id . '-add';

    $query_args = array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    if ( ! $is_tool && $current_id > 0 ) {
        $query_args['post__not_in'] = array( $current_id );
    }

    $available_posts = get_posts( $query_args );
    ?>
    <div class="tnt-resource-relationship-editor" id="<?php echo esc_attr( $wrapper_id ); ?>" data-input-name="<?php echo esc_attr( $input_name ); ?>[]">
        <p>
            <select id="<?php echo esc_attr( $candidate_id ); ?>">
                <option value="0"><?php esc_html_e( 'Select an item…', 'toolntip-core' ); ?></option>
                <?php foreach ( $available_posts as $available_post ) : ?>
                    <option value="<?php echo esc_attr( $available_post->ID ); ?>"><?php echo esc_html( get_the_title( $available_post ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button" id="<?php echo esc_attr( $add_button_id ); ?>"><?php esc_html_e( 'Add', 'toolntip-core' ); ?></button>
        </p>
        <ol class="tnt-resource-relationship-selected">
            <?php foreach ( $selected_ids as $selected_id ) : ?>
                <li data-id="<?php echo esc_attr( $selected_id ); ?>">
                    <span><?php echo esc_html( get_the_title( $selected_id ) ); ?></span>
                    <input type="hidden" name="<?php echo esc_attr( $input_name ); ?>[]" value="<?php echo esc_attr( $selected_id ); ?>">
                    <button type="button" class="button-link tnt-move-up" aria-label="<?php esc_attr_e( 'Move up', 'toolntip-core' ); ?>">↑</button>
                    <button type="button" class="button-link tnt-move-down" aria-label="<?php esc_attr_e( 'Move down', 'toolntip-core' ); ?>">↓</button>
                    <button type="button" class="button-link-delete tnt-remove-related" aria-label="<?php esc_attr_e( 'Remove', 'toolntip-core' ); ?>"><?php esc_html_e( 'Remove', 'toolntip-core' ); ?></button>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php
}

/**
 * Render fallback relationship meta box.
 *
 * @param WP_Post $post Current Resource.
 */
function tnt_render_resource_relationships_fallback_meta_box( $post ) {

    wp_nonce_field( 'tnt_save_resource_editorial', 'tnt_resource_editorial_nonce' );

    echo '<h3>' . esc_html__( 'Related Tools', 'toolntip-core' ) . '</h3>';
    echo '<p class="description">' . esc_html__( 'Choose published Tools and order them editorially.', 'toolntip-core' ) . '</p>';
    tnt_render_resource_relationship_fallback_editor(
        'tool',
        tnt_get_resource_related_tool_ids( $post->ID ),
        $post->ID
    );

    echo '<hr><h3>' . esc_html__( 'Related Resources', 'toolntip-core' ) . '</h3>';
    echo '<p class="description">' . esc_html__( 'Choose published Resources and order them editorially. The current Resource cannot relate to itself.', 'toolntip-core' ) . '</p>';
    tnt_render_resource_relationship_fallback_editor(
        'resource',
        tnt_get_resource_related_resource_ids( $post->ID ),
        $post->ID
    );
}

/**
 * Save Core fallback Resource editorial fields.
 *
 * @param int $post_id Resource ID.
 */
function tnt_save_resource_editorial_fields( $post_id ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['tnt_resource_editorial_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tnt_resource_editorial_nonce'] ) ), 'tnt_save_resource_editorial' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    /*
     * Resource Type is submitted by ToolNTip's custom single-select control.
     *
     * Gutenberg can save native taxonomy panels (for example Resource Topic
     * or Resource Tags) through requests that do not include this custom
     * field. Absence therefore means "leave Resource Type unchanged", not
     * "clear Resource Type".
     */
    if ( isset( $_POST['tnt_resource_type_id'] ) ) {

        $type_id = absint( wp_unslash( $_POST['tnt_resource_type_id'] ) );
        $post    = get_post( $post_id );

        $existing_type_ids = wp_get_object_terms(
            $post_id,
            'resource_type',
            array( 'fields' => 'ids' )
        );

        if ( is_wp_error( $existing_type_ids ) ) {
            $existing_type_ids = array();
        }

        $existing_type_ids = array_values( array_map( 'absint', $existing_type_ids ) );

        if ( $type_id > 0 && term_exists( $type_id, 'resource_type' ) ) {

            wp_set_object_terms( $post_id, array( $type_id ), 'resource_type', false );

        } elseif (
            $post
            && in_array( $post->post_status, array( 'publish', 'future' ), true )
            && 1 === count( $existing_type_ids )
        ) {

            /*
             * A published/scheduled Resource already has a valid Type.
             * Reject an attempt to remove it by preserving the last valid
             * classification and notifying the editor.
             */
            $user_id = get_current_user_id();

            if ( $user_id > 0 ) {
                set_transient(
                    'tnt_resource_type_preserved_notice_' . $user_id,
                    $post_id,
                    MINUTE_IN_SECONDS
                );
            }

        } else {

            /*
             * Drafts may intentionally remain unclassified. New publication
             * attempts without a valid Type are handled by the publication
             * enforcement guard below.
             */
            wp_set_object_terms( $post_id, array(), 'resource_type', false );
        }
    }

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        $related_tools = isset( $_POST['tnt_related_tool_ids'] ) ? (array) wp_unslash( $_POST['tnt_related_tool_ids'] ) : array();
        $related_tools = tnt_normalize_resource_relationship_ids( $related_tools, 'tool' );
        update_post_meta( $post_id, 'tnt_related_tool_ids', $related_tools );

        $related_resources = isset( $_POST['tnt_related_resource_ids'] ) ? (array) wp_unslash( $_POST['tnt_related_resource_ids'] ) : array();
        $related_resources = tnt_normalize_resource_relationship_ids( $related_resources, 'resource', $post_id );
        update_post_meta( $post_id, 'tnt_related_resource_ids', $related_resources );
    }
}
add_action( 'save_post_resource', 'tnt_save_resource_editorial_fields', 50 );

/**
 * Ensure canonical stored relationship metadata remains valid after any editor save.
 *
 * @param int $post_id Resource ID.
 */
function tnt_validate_resource_relationship_storage( $post_id ) {

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    update_post_meta(
        $post_id,
        'tnt_related_tool_ids',
        tnt_get_resource_related_tool_ids( $post_id )
    );

    update_post_meta(
        $post_id,
        'tnt_related_resource_ids',
        tnt_get_resource_related_resource_ids( $post_id )
    );
}
add_action( 'save_post_resource', 'tnt_validate_resource_relationship_storage', 90 );

/**
 * Synchronize derived Resource relationship indexes after canonical storage.
 *
 * Canonical relationship metadata remains authoritative. The derived scalar
 * indexes are rebuilt only after Resource relationship values have completed
 * their normal save-time normalization and validation.
 *
 * WEB-007.4 / 4.4C-A / A6.3.2
 *
 * @param int     $post_id Resource post ID.
 * @param WP_Post $post    Resource post object.
 * @param bool    $update  Whether this is an existing post update.
 * @return void
 */
function tnt_sync_resource_relationship_index_after_save(
    $post_id,
    $post,
    $update
) {

    unset( $update );

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return;
    }

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    if ( 'resource' !== $post->post_type ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! function_exists( 'tnt_rebuild_resource_relationship_index' ) ) {
        return;
    }

    tnt_rebuild_resource_relationship_index( $post_id );
}

add_action(
    'save_post_resource',
    'tnt_sync_resource_relationship_index_after_save',
    100,
    3
);

/**
 * Enforce exactly one Resource Type for published/future Resources.
 *
 * Invalid publication is safely reverted to Draft without deleting editorial
 * content. Draft Resources may remain unclassified.
 *
 * @param int $post_id Resource ID.
 */
function tnt_enforce_resource_type_for_publication( $post_id ) {

    static $updating = false;

    $post_id = absint( $post_id );

    if ( $post_id <= 0 || $updating || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $post = get_post( $post_id );

    if ( ! $post || 'resource' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
        return;
    }

    $type_ids = wp_get_object_terms( $post_id, 'resource_type', array( 'fields' => 'ids' ) );

    if ( ! is_wp_error( $type_ids ) && 1 === count( $type_ids ) ) {
        return;
    }

    $updating = true;

    wp_update_post(
        array(
            'ID'          => $post_id,
            'post_status' => 'draft',
        )
    );

    $updating = false;

    $user_id = get_current_user_id();
    if ( $user_id > 0 ) {
        set_transient( 'tnt_resource_type_notice_' . $user_id, $post_id, MINUTE_IN_SECONDS );
    }
}
add_action( 'save_post_resource', 'tnt_enforce_resource_type_for_publication', 120 );
add_action( 'acf/save_post', 'tnt_enforce_resource_type_for_publication', 30 );

/**
 * Display Resource Type publication enforcement notice.
 */
function tnt_resource_type_publication_notice() {

    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }

    $transient_key = 'tnt_resource_type_notice_' . $user_id;
    $post_id       = absint( get_transient( $transient_key ) );

    if ( $post_id <= 0 ) {
        return;
    }

    delete_transient( $transient_key );
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'This Resource was returned to Draft. Select exactly one Resource Type before publishing.', 'toolntip-core' ); ?></p>
    </div>
    <?php
}
add_action( 'admin_notices', 'tnt_resource_type_publication_notice' );

/**
 * Display notice when an invalid attempt to remove Resource Type from an
 * already-published/scheduled Resource was rejected.
 */
function tnt_resource_type_preserved_notice() {

    $user_id = get_current_user_id();

    if ( $user_id <= 0 ) {
        return;
    }

    $transient_key = 'tnt_resource_type_preserved_notice_' . $user_id;
    $post_id       = absint( get_transient( $transient_key ) );

    if ( $post_id <= 0 ) {
        return;
    }

    delete_transient( $transient_key );
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'Resource Type is required for published Resources. The previous valid Resource Type was preserved.', 'toolntip-core' ); ?></p>
    </div>
    <?php
}
add_action( 'admin_notices', 'tnt_resource_type_preserved_notice' );

/**
 * Add Resource Type, Topic, and Tag filters to the Resource list table.
 *
 * @param string $post_type Current list-table post type.
 */
function tnt_resource_admin_taxonomy_filters( $post_type ) {

    if ( 'resource' !== $post_type ) {
        return;
    }

    foreach ( array( 'resource_type', 'tool_category', 'resource_tag' ) as $taxonomy ) {
        $taxonomy_object = get_taxonomy( $taxonomy );

        if ( ! $taxonomy_object ) {
            continue;
        }

        $selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        wp_dropdown_categories(
            array(
                'show_option_all' => sprintf( __( 'All %s', 'toolntip-core' ), $taxonomy_object->label ),
                'taxonomy'        => $taxonomy,
                'name'            => $taxonomy,
                'orderby'         => 'name',
                'selected'        => $selected,
                'hierarchical'    => is_taxonomy_hierarchical( $taxonomy ),
                'hide_empty'      => false,
                'value_field'     => 'slug',
            )
        );
    }
}
add_action( 'restrict_manage_posts', 'tnt_resource_admin_taxonomy_filters' );

/**
 * Hide the default Gutenberg Resource Type taxonomy panel because ToolNTip
 * provides a true single-selection editor via ACF/fallback meta box.
 */
function tnt_enqueue_resource_editor_admin_script() {

    $screen = get_current_screen();

    if ( ! $screen || 'resource' !== $screen->post_type || ! $screen->is_block_editor() ) {
        return;
    }

    wp_enqueue_script( 'wp-edit-post' );
    wp_add_inline_script(
        'wp-edit-post',
        "wp.domReady(function(){if(wp.data&&wp.data.dispatch('core/edit-post')){try{wp.data.dispatch('core/edit-post').removeEditorPanel('taxonomy-panel-resource_type');}catch(e){}}});"
    );
}
add_action( 'admin_enqueue_scripts', 'tnt_enqueue_resource_editor_admin_script' );

/**
 * Enqueue fallback relationship editor interactions when ACF is unavailable.
 */
function tnt_enqueue_resource_fallback_relationship_script() {

    if ( function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $screen = get_current_screen();

    if ( ! $screen || 'resource' !== $screen->post_type ) {
        return;
    }

    wp_register_script( 'tnt-resource-fallback-editor', false, array(), TNT_CORE_VERSION, true );
    wp_enqueue_script( 'tnt-resource-fallback-editor' );
    wp_add_inline_script(
        'tnt-resource-fallback-editor',
        <<<'JS'
(function(){
    function setupEditor(editor){
        var select=editor.querySelector('select');
        var add=editor.querySelector('.button');
        var list=editor.querySelector('.tnt-resource-relationship-selected');
        if(!select||!add||!list){return;}
        add.addEventListener('click',function(){
            var option=select.options[select.selectedIndex];
            if(!option||!option.value||option.value==='0'||list.querySelector('[data-id="'+option.value+'"]')){return;}
            var li=document.createElement('li');
            li.setAttribute('data-id',option.value);
            li.innerHTML='<span></span> <input type="hidden"> <button type="button" class="button-link tnt-move-up" aria-label="Move up">↑</button> <button type="button" class="button-link tnt-move-down" aria-label="Move down">↓</button> <button type="button" class="button-link-delete tnt-remove-related">Remove</button>';
            li.querySelector('span').textContent=option.textContent;
            var input=li.querySelector('input');
            input.name=editor.getAttribute('data-input-name');
            input.value=option.value;
            list.appendChild(li);
        });
        list.addEventListener('click',function(event){
            var button=event.target.closest('button');
            var li=event.target.closest('li');
            if(!button||!li){return;}
            if(button.classList.contains('tnt-remove-related')){li.remove();}
            if(button.classList.contains('tnt-move-up')&&li.previousElementSibling){list.insertBefore(li,li.previousElementSibling);}
            if(button.classList.contains('tnt-move-down')&&li.nextElementSibling){list.insertBefore(li.nextElementSibling,li);}
        });
    }
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.tnt-resource-relationship-editor').forEach(setupEditor);
    });
})();
JS
    );
}
add_action( 'admin_enqueue_scripts', 'tnt_enqueue_resource_fallback_relationship_script' );
