<?php
/**
 * Core-owned Tool editor controls.
 *
 * Legacy Tool Details controls are shown only when ACF is unavailable so an
 * ACF-active transition build does not duplicate the existing editorial UI.
 * Internal Application Configuration is always Core-owned.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Core-owned Tool meta boxes.
 *
 * @return void
 */
function tnt_register_tool_editor_meta_boxes() {
    add_meta_box(
        'tnt-tool-application',
        __( 'Internal Application Configuration', 'toolntip-core' ),
        'tnt_render_tool_application_meta_box',
        'tool',
        'normal',
        'high'
    );

    if ( function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    add_meta_box(
        'tnt-tool-details',
        __( 'Tool Details', 'toolntip-core' ),
        'tnt_render_tool_details_meta_box',
        'tool',
        'normal',
        'high'
    );

    add_meta_box(
        'tnt-tool-structured-content',
        __( 'Tool Structured Content', 'toolntip-core' ),
        'tnt_render_tool_structured_meta_box',
        'tool',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes_tool', 'tnt_register_tool_editor_meta_boxes' );

/**
 * Render an input row.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param string $label   Label.
 * @param string $type    Input type.
 * @param string $help    Help text.
 * @return void
 */
function tnt_tool_editor_input( $post_id, $key, $label, $type = 'text', $help = '' ) {
    $value = get_post_meta( $post_id, $key, true );
    if ( is_array( $value ) ) {
        $value = implode( ', ', array_map( 'strval', $value ) );
    }
    ?>
    <p>
        <label for="tnt-tool-<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
        <input class="widefat" id="tnt-tool-<?php echo esc_attr( $key ); ?>" name="tnt_tool_meta[<?php echo esc_attr( $key ); ?>]" type="<?php echo esc_attr( $type ); ?>" <?php echo 'number' === $type ? 'step="0.1"' : ''; ?> value="<?php echo esc_attr( (string) $value ); ?>">
        <?php if ( '' !== $help ) : ?>
            <span class="description"><?php echo esc_html( $help ); ?></span>
        <?php endif; ?>
    </p>
    <?php
}

/**
 * Render application configuration.
 *
 * @param WP_Post $post Tool post.
 * @return void
 */
function tnt_render_tool_application_meta_box( $post ) {
    wp_nonce_field( 'tnt_save_tool_meta', 'tnt_tool_meta_nonce' );

    $enabled    = tnt_get_tool_meta_bool( $post, 'internal_application' );
    $runtime_id = sanitize_key( (string) tnt_get_tool_meta( $post, 'runtime_module', '' ) );
    $layout     = sanitize_key( (string) tnt_get_tool_meta( $post, 'workspace_layout', '' ) );
    ?>
    <p>
        <label>
            <input type="checkbox" name="tnt_tool_application[internal_application]" value="1" <?php checked( $enabled ); ?>>
            <strong><?php esc_html_e( 'Internal Application', 'toolntip-core' ); ?></strong>
        </label><br>
        <span class="description"><?php esc_html_e( 'Enable only when this Tool should run through a registered ToolNTip internal application runtime.', 'toolntip-core' ); ?></span>
    </p>
    <p>
        <label for="tnt-runtime-module"><strong><?php esc_html_e( 'Runtime Module', 'toolntip-core' ); ?></strong></label><br>
        <select class="widefat" id="tnt-runtime-module" name="tnt_tool_application[runtime_module]">
            <option value=""><?php esc_html_e( '— Select registered runtime —', 'toolntip-core' ); ?></option>
            <?php foreach ( tnt_get_application_runtimes() as $id => $runtime ) : ?>
                <?php if ( is_array( $runtime ) && ! empty( $runtime['label'] ) ) : ?>
                    <option value="<?php echo esc_attr( sanitize_key( $id ) ); ?>" <?php selected( $runtime_id, sanitize_key( $id ) ); ?>><?php echo esc_html( $runtime['label'] ); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="tnt-workspace-layout"><strong><?php esc_html_e( 'Workspace Layout', 'toolntip-core' ); ?></strong></label><br>
        <select class="widefat" id="tnt-workspace-layout" name="tnt_tool_application[workspace_layout]">
            <option value=""><?php esc_html_e( 'Runtime default', 'toolntip-core' ); ?></option>
            <?php foreach ( tnt_get_application_workspace_layouts() as $choice ) : ?>
                <option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $layout, $choice ); ?>><?php echo esc_html( ucwords( str_replace( '-', ' ', $choice ) ) ); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

/**
 * Render legacy Tool Details using WordPress-native controls.
 *
 * @param WP_Post $post Tool post.
 * @return void
 */
function tnt_render_tool_details_meta_box( $post ) {
    wp_nonce_field( 'tnt_save_tool_meta', 'tnt_tool_meta_nonce' );

    tnt_tool_editor_input( $post->ID, 'tool_tagline', __( 'Tool Tagline', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'tool_slug', __( 'Tool Slug', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'developer', __( 'Developer', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'pricing', __( 'Pricing', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'tool_type', __( 'Tool Type', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'platform', __( 'Platform(s)', 'toolntip-core' ), 'text', __( 'Comma-separated values, for example: Web, Windows, macOS.', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'editor_rating', __( 'Editor Rating', 'toolntip-core' ), 'number' );
    tnt_tool_editor_input( $post->ID, 'review_count', __( 'Review Count', 'toolntip-core' ), 'number' );
    tnt_tool_editor_input( $post->ID, 'last_verified', __( 'Last Verified', 'toolntip-core' ), 'text', __( 'Stored as YYYYMMDD to preserve the existing Tool data contract.', 'toolntip-core' ) );
    tnt_tool_editor_input( $post->ID, 'use_tool_url', __( 'Use Tool URL', 'toolntip-core' ), 'url' );
    tnt_tool_editor_input( $post->ID, 'official_website', __( 'Official Website', 'toolntip-core' ), 'url' );
    tnt_tool_editor_input( $post->ID, 'affiliate_url', __( 'Affiliate URL', 'toolntip-core' ), 'url' );
    tnt_tool_editor_input( $post->ID, 'demo_video', __( 'Demo Video URL', 'toolntip-core' ), 'url' );
    tnt_render_tool_media_field( $post->ID, 'tool_logo', __( 'Tool Logo', 'toolntip-core' ), false );
    tnt_render_tool_media_field( $post->ID, 'screenshots', __( 'Screenshots', 'toolntip-core' ), true );

    $about = (string) get_post_meta( $post->ID, 'about_this_tool', true );
    ?>
    <p><label><input type="checkbox" name="tnt_tool_meta[verified]" value="1" <?php checked( tnt_get_tool_meta_bool( $post, 'verified' ) ); ?>> <?php esc_html_e( 'Verified', 'toolntip-core' ); ?></label></p>
    <p><label><input type="checkbox" name="tnt_tool_meta[featured_tool]" value="1" <?php checked( tnt_get_tool_meta_bool( $post, 'featured_tool' ) ); ?>> <?php esc_html_e( 'Featured Tool', 'toolntip-core' ); ?></label></p>
    <p><strong><?php esc_html_e( 'About This Tool', 'toolntip-core' ); ?></strong></p>
    <?php
    wp_editor(
        $about,
        'tnt_about_this_tool',
        array(
            'textarea_name' => 'tnt_tool_meta[about_this_tool]',
            'textarea_rows' => 10,
            'media_buttons' => true,
        )
    );
}

/**
 * Render a Core-owned media selector while preserving attachment-ID storage.
 *
 * @param int    $post_id  Post ID.
 * @param string $key      Meta key.
 * @param string $label    Label.
 * @param bool   $multiple Whether multiple images may be selected.
 * @return void
 */
function tnt_render_tool_media_field( $post_id, $key, $label, $multiple = false ) {
    $raw = get_post_meta( $post_id, $key, true );
    $ids = $multiple ? (array) $raw : array( $raw );
    $ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
    ?>
    <div class="tnt-tool-media-field" data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
        <p><strong><?php echo esc_html( $label ); ?></strong></p>
        <div class="tnt-tool-media-preview">
            <?php foreach ( $ids as $attachment_id ) : ?>
                <span class="tnt-tool-media-item" data-id="<?php echo esc_attr( $attachment_id ); ?>"><?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?></span>
            <?php endforeach; ?>
        </div>
        <input class="tnt-tool-media-ids" type="hidden" name="tnt_tool_meta[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
        <p>
            <button type="button" class="button tnt-tool-media-select"><?php echo esc_html( $multiple ? __( 'Select screenshots', 'toolntip-core' ) : __( 'Select tool logo', 'toolntip-core' ) ); ?></button>
            <button type="button" class="button-link-delete tnt-tool-media-clear" <?php echo empty( $ids ) ? 'hidden' : ''; ?>><?php esc_html_e( 'Clear', 'toolntip-core' ); ?></button>
        </p>
    </div>
    <?php
}

/**
 * Render one FAQ editor row.
 *
 * @param int|string $index Row index or template token.
 * @param array      $faq   FAQ values.
 * @return void
 */
function tnt_render_tool_faq_row( $index, $faq ) {
    $question = isset( $faq['question'] ) ? (string) $faq['question'] : '';
    $answer   = isset( $faq['answer'] ) ? (string) $faq['answer'] : '';

    /*
     * FAQ answers are sanitized with wp_kses_post() on save. WordPress
     * normalizes literal special characters (notably &) to HTML entities
     * during that sanitization. Decode those entities for the textarea
     * editing surface before esc_textarea() performs context escaping.
     *
     * This keeps the stored HTML-safe canonical value stable while avoiding
     * visible entity accumulation such as "&amp;" -> "&amp;amp;" across
     * unchanged edit/save cycles. Safe markup is still re-sanitized on save.
     */
    $answer = wp_specialchars_decode( $answer, ENT_QUOTES );
    ?>
    <div class="tnt-tool-faq-row">
        <p><label><strong><?php esc_html_e( 'Question', 'toolntip-core' ); ?></strong><br>
            <input class="widefat" type="text" name="tnt_tool_faqs[<?php echo esc_attr( $index ); ?>][question]" value="<?php echo esc_attr( $question ); ?>">
        </label></p>
        <p><label><strong><?php esc_html_e( 'Answer', 'toolntip-core' ); ?></strong><br>
            <textarea class="widefat" rows="5" name="tnt_tool_faqs[<?php echo esc_attr( $index ); ?>][answer]"><?php echo esc_textarea( $answer ); ?></textarea>
        </label></p>
        <p><button type="button" class="button-link-delete tnt-tool-faq-remove"><?php esc_html_e( 'Remove FAQ', 'toolntip-core' ); ?></button></p>
    </div>
    <?php
}

/**
 * Render list-based structured content.
 *
 * @param WP_Post $post Tool post.
 * @return void
 */
function tnt_render_tool_structured_meta_box( $post ) {
    $features = tnt_get_tool_repeater_values( $post, 'features', 'feature' );
    $pros     = tnt_get_tool_repeater_values( $post, 'pros', 'pro' );
    $cons     = tnt_get_tool_repeater_values( $post, 'cons', 'con' );
    $faqs     = tnt_get_tool_faq_rows( $post );
    ?>
    <p><strong><?php esc_html_e( 'Features', 'toolntip-core' ); ?></strong><br>
        <textarea class="widefat" rows="8" name="tnt_tool_lists[features]"><?php echo esc_textarea( implode( "\n", $features ) ); ?></textarea>
        <span class="description"><?php esc_html_e( 'One feature per line.', 'toolntip-core' ); ?></span>
    </p>
    <p><strong><?php esc_html_e( 'Pros', 'toolntip-core' ); ?></strong><br>
        <textarea class="widefat" rows="8" name="tnt_tool_lists[pros]"><?php echo esc_textarea( implode( "\n", $pros ) ); ?></textarea>
        <span class="description"><?php esc_html_e( 'One pro per line.', 'toolntip-core' ); ?></span>
    </p>
    <p><strong><?php esc_html_e( 'Cons', 'toolntip-core' ); ?></strong><br>
        <textarea class="widefat" rows="8" name="tnt_tool_lists[cons]"><?php echo esc_textarea( implode( "\n", $cons ) ); ?></textarea>
        <span class="description"><?php esc_html_e( 'One con per line.', 'toolntip-core' ); ?></span>
    </p>
    <div class="tnt-tool-faq-editor">
        <p><strong><?php esc_html_e( 'FAQs', 'toolntip-core' ); ?></strong><br>
            <span class="description"><?php esc_html_e( 'Edit each question and answer independently. Core preserves the existing FAQ storage contract.', 'toolntip-core' ); ?></span>
        </p>
        <div class="tnt-tool-faq-rows">
            <?php foreach ( $faqs as $index => $faq ) : ?>
                <?php tnt_render_tool_faq_row( $index, $faq ); ?>
            <?php endforeach; ?>
        </div>
        <p><button type="button" class="button tnt-tool-faq-add"><?php esc_html_e( 'Add FAQ', 'toolntip-core' ); ?></button></p>
        <script type="text/html" id="tmpl-tnt-tool-faq-row"><?php tnt_render_tool_faq_row( '__INDEX__', array( 'question' => '', 'answer' => '' ) ); ?></script>
    </div>
    <?php
}

/**
 * Save a legacy repeater storage shape while retaining ACF reference rows.
 *
 * @param int    $post_id   Post ID.
 * @param string $group_key Group key.
 * @param string $item_key  Child key suffix.
 * @param array  $values    Values.
 * @return void
 */
function tnt_save_tool_repeater_values( $post_id, $group_key, $item_key, $values ) {
    $old_count = max( 0, (int) get_post_meta( $post_id, $group_key, true ) );
    $values    = array_values( array_filter( array_map( 'trim', $values ), static function ( $value ) { return '' !== $value; } ) );

    update_post_meta( $post_id, $group_key, count( $values ) );

    foreach ( $values as $index => $value ) {
        update_post_meta( $post_id, $group_key . '_' . $index . '_' . $item_key, sanitize_text_field( $value ) );
    }

    for ( $index = count( $values ); $index < $old_count; $index++ ) {
        delete_post_meta( $post_id, $group_key . '_' . $index . '_' . $item_key );
    }
}

/**
 * Save Tool editor data.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @return void
 */
function tnt_save_tool_editor_meta( $post_id, $post ) {
    if ( ! $post instanceof WP_Post || 'tool' !== $post->post_type ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['tnt_tool_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tnt_tool_meta_nonce'] ) ), 'tnt_save_tool_meta' ) ) {
        return;
    }

    $application = isset( $_POST['tnt_tool_application'] ) && is_array( $_POST['tnt_tool_application'] )
        ? wp_unslash( $_POST['tnt_tool_application'] )
        : array();

    $internal = isset( $application['internal_application'] ) && '1' === (string) $application['internal_application'];
    update_post_meta( $post_id, 'internal_application', $internal ? '1' : '0' );

    $runtime_id = isset( $application['runtime_module'] ) ? sanitize_key( (string) $application['runtime_module'] ) : '';
    if ( '' !== $runtime_id && ! tnt_application_runtime_exists( $runtime_id ) ) {
        $runtime_id = '';
    }
    update_post_meta( $post_id, 'runtime_module', $runtime_id );

    $layout = isset( $application['workspace_layout'] ) ? sanitize_key( (string) $application['workspace_layout'] ) : '';
    if ( '' !== $layout && ! in_array( $layout, tnt_get_application_workspace_layouts(), true ) ) {
        $layout = '';
    }
    if ( '' !== $layout && '' !== $runtime_id ) {
        $runtime = tnt_get_application_runtime( $runtime_id );
        if ( null === $runtime || empty( $runtime['supported_layouts'] ) || ! in_array( $layout, $runtime['supported_layouts'], true ) ) {
            $layout = '';
        }
    }
    update_post_meta( $post_id, 'workspace_layout', $layout );

    // When ACF remains active during transition, its existing Tool Details UI
    // remains authoritative and Core deliberately does not process duplicate
    // legacy field inputs.
    if ( function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $meta = isset( $_POST['tnt_tool_meta'] ) && is_array( $_POST['tnt_tool_meta'] )
        ? wp_unslash( $_POST['tnt_tool_meta'] )
        : array();

    $text_fields = array( 'tool_tagline', 'tool_slug', 'developer', 'pricing', 'tool_type', 'last_verified' );
    foreach ( $text_fields as $key ) {
        update_post_meta( $post_id, $key, isset( $meta[ $key ] ) ? sanitize_text_field( (string) $meta[ $key ] ) : '' );
    }

    $url_fields = array( 'use_tool_url', 'official_website', 'affiliate_url', 'demo_video' );
    foreach ( $url_fields as $key ) {
        update_post_meta( $post_id, $key, isset( $meta[ $key ] ) ? esc_url_raw( (string) $meta[ $key ] ) : '' );
    }

    $platforms = isset( $meta['platform'] ) ? explode( ',', (string) $meta['platform'] ) : array();
    $platforms = array_values( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', $platforms ) ) ) );
    update_post_meta( $post_id, 'platform', $platforms );

    $rating = isset( $meta['editor_rating'] ) ? (float) $meta['editor_rating'] : 0.0;
    $rating = max( 0, min( 5, $rating ) );
    update_post_meta( $post_id, 'editor_rating', (string) $rating );
    update_post_meta( $post_id, 'review_count', isset( $meta['review_count'] ) ? absint( $meta['review_count'] ) : 0 );
    update_post_meta( $post_id, 'tool_logo', isset( $meta['tool_logo'] ) ? absint( $meta['tool_logo'] ) : 0 );

    $screenshot_ids = isset( $meta['screenshots'] ) ? preg_split( '/\s*,\s*/', (string) $meta['screenshots'] ) : array();
    $screenshot_ids = array_values( array_filter( array_map( 'absint', (array) $screenshot_ids ) ) );
    update_post_meta( $post_id, 'screenshots', $screenshot_ids );

    update_post_meta( $post_id, 'verified', isset( $meta['verified'] ) && '1' === (string) $meta['verified'] ? '1' : '0' );
    update_post_meta( $post_id, 'featured_tool', isset( $meta['featured_tool'] ) && '1' === (string) $meta['featured_tool'] ? '1' : '0' );
    update_post_meta( $post_id, 'about_this_tool', isset( $meta['about_this_tool'] ) ? wp_kses_post( (string) $meta['about_this_tool'] ) : '' );

    $lists = isset( $_POST['tnt_tool_lists'] ) && is_array( $_POST['tnt_tool_lists'] )
        ? wp_unslash( $_POST['tnt_tool_lists'] )
        : array();

    foreach ( array( 'features' => 'feature', 'pros' => 'pro', 'cons' => 'con' ) as $group => $item ) {
        $raw    = isset( $lists[ $group ] ) ? (string) $lists[ $group ] : '';
        $values = preg_split( '/\r\n|\r|\n/', $raw );
        tnt_save_tool_repeater_values( $post_id, $group, $item, (array) $values );
    }

    $old_faq_count = max( 0, (int) get_post_meta( $post_id, 'faqs', true ) );
    $faq_rows      = isset( $_POST['tnt_tool_faqs'] ) && is_array( $_POST['tnt_tool_faqs'] )
        ? wp_unslash( $_POST['tnt_tool_faqs'] )
        : array();
    $faqs          = array();

    foreach ( $faq_rows as $faq_row ) {
        if ( ! is_array( $faq_row ) ) {
            continue;
        }
        $question = isset( $faq_row['question'] ) ? sanitize_text_field( (string) $faq_row['question'] ) : '';
        $answer   = isset( $faq_row['answer'] ) ? wp_kses_post( (string) $faq_row['answer'] ) : '';
        if ( '' === trim( $question ) || '' === trim( wp_strip_all_tags( $answer ) ) ) {
            continue;
        }
        $faqs[] = array( 'question' => $question, 'answer' => $answer );
    }

    update_post_meta( $post_id, 'faqs', count( $faqs ) );
    foreach ( $faqs as $index => $faq ) {
        update_post_meta( $post_id, 'faqs_' . $index . '_question', $faq['question'] );
        update_post_meta( $post_id, 'faqs_' . $index . '_answer', $faq['answer'] );
    }
    for ( $index = count( $faqs ); $index < $old_faq_count; $index++ ) {
        delete_post_meta( $post_id, 'faqs_' . $index . '_question' );
        delete_post_meta( $post_id, 'faqs_' . $index . '_answer' );
    }
}

/**
 * Load native Tool editor enhancements only when ACF is not providing them.
 *
 * @param string $hook_suffix Current admin hook.
 * @return void
 */
function tnt_enqueue_tool_editor_assets( $hook_suffix ) {
    if ( function_exists( 'acf_add_local_field_group' ) || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || 'tool' !== $screen->post_type ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script( 'jquery' );
    $script = <<<'JS'
(function($){
    function renumberFaqs(){
        $('.tnt-tool-faq-row').each(function(i){
            $(this).find('[name]').each(function(){
                this.name = this.name.replace(/tnt_tool_faqs\[[^\]]+\]/, 'tnt_tool_faqs[' + i + ']');
            });
        });
    }
    $(document).on('click', '.tnt-tool-faq-add', function(){
        var html = $('#tmpl-tnt-tool-faq-row').html().replace(/__INDEX__/g, $('.tnt-tool-faq-row').length);
        $('.tnt-tool-faq-rows').append(html);
    });
    $(document).on('click', '.tnt-tool-faq-remove', function(){
        $(this).closest('.tnt-tool-faq-row').remove();
        renumberFaqs();
    });
    $(document).on('click', '.tnt-tool-media-select', function(){
        var field = $(this).closest('.tnt-tool-media-field'), multiple = field.data('multiple') === 1,
            frame = wp.media({title: multiple ? 'Select screenshots' : 'Select tool logo', button:{text:'Use selected media'}, multiple:multiple});
        frame.on('select', function(){
            var selection = frame.state().get('selection').toJSON(), ids=[], preview='';
            $.each(selection, function(_, item){
                ids.push(item.id);
                var src = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
                preview += '<span class="tnt-tool-media-item" data-id="' + item.id + '"><img src="' + src + '" alt=""></span>';
            });
            field.find('.tnt-tool-media-ids').val(ids.join(','));
            field.find('.tnt-tool-media-preview').html(preview);
            field.find('.tnt-tool-media-clear').prop('hidden', ids.length === 0);
        });
        frame.open();
    });
    $(document).on('click', '.tnt-tool-media-clear', function(){
        var field=$(this).closest('.tnt-tool-media-field');
        field.find('.tnt-tool-media-ids').val(''); field.find('.tnt-tool-media-preview').empty(); $(this).prop('hidden', true);
    });
})(jQuery);
JS;
    wp_add_inline_script( 'jquery', $script );
    wp_register_style( 'tnt-tool-editor', false, array(), TNT_CORE_VERSION );
    wp_enqueue_style( 'tnt-tool-editor' );
    wp_add_inline_style( 'tnt-tool-editor', '.tnt-tool-faq-row{border:1px solid #dcdcde;padding:12px 14px;margin:0 0 12px;background:#fff}.tnt-tool-media-preview{display:flex;gap:8px;flex-wrap:wrap}.tnt-tool-media-item img{width:96px;height:96px;object-fit:cover;border:1px solid #dcdcde;background:#f6f7f7}.tnt-tool-media-field{margin:16px 0}' );
}
add_action( 'admin_enqueue_scripts', 'tnt_enqueue_tool_editor_assets' );

add_action( 'save_post_tool', 'tnt_save_tool_editor_meta', 10, 2 );
