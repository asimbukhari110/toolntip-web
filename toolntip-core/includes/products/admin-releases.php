<?php
/**
 * Governed Product Edition / Release administration.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_current_user_can_manage_releases() {
    return current_user_can( 'manage_options' ) || current_user_can( 'manage_toolntip_releases' );
}
function tnt_current_user_can_publish_releases() {
    return current_user_can( 'manage_options' ) || current_user_can( 'publish_toolntip_releases' );
}
function tnt_current_user_can_withdraw_releases() {
    return current_user_can( 'manage_options' ) || current_user_can( 'withdraw_toolntip_releases' );
}

function tnt_add_product_release_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=tnt_product',
        __( 'Editions & Releases', 'toolntip-core' ),
        __( 'Editions & Releases', 'toolntip-core' ),
        'manage_options',
        'tnt-product-releases',
        'tnt_render_product_release_admin_page'
    );
}
add_action( 'admin_menu', 'tnt_add_product_release_admin_menu', 30 );

function tnt_product_release_admin_url( $args = array() ) {
    return add_query_arg( array_merge( array( 'post_type' => 'tnt_product', 'page' => 'tnt-product-releases' ), $args ), admin_url( 'edit.php' ) );
}

function tnt_release_admin_redirect( $args = array() ) {
    wp_safe_redirect( tnt_product_release_admin_url( $args ) );
    exit;
}

function tnt_release_admin_error_message( $error ) {
    if ( is_wp_error( $error ) ) { return $error->get_error_message(); }
    return __( 'The requested operation could not be completed.', 'toolntip-core' );
}

function tnt_release_admin_notice_message( $notice_key ) {
    $map = array(
        'edition_created'   => __( 'Edition created successfully.', 'toolntip-core' ),
        'edition_updated'   => __( 'Edition updated successfully.', 'toolntip-core' ),
        'edition_deleted'   => __( 'Edition deleted successfully.', 'toolntip-core' ),
        'release_created'   => __( 'Draft Release created successfully.', 'toolntip-core' ),
        'release_saved'     => __( 'Release metadata saved successfully.', 'toolntip-core' ),
        'release_deleted'   => __( 'Release deleted successfully.', 'toolntip-core' ),
        'release_ready'     => __( 'Release validated and moved to Ready.', 'toolntip-core' ),
        'release_published' => __( 'Release published successfully.', 'toolntip-core' ),
        'release_withdrawn' => __( 'Release withdrawn successfully.', 'toolntip-core' ),
    );
    $notice_key = sanitize_key( $notice_key );
    return isset( $map[ $notice_key ] ) ? $map[ $notice_key ] : __( 'Release administration action completed successfully.', 'toolntip-core' );
}

function tnt_handle_product_release_admin_actions() {
    if ( ! is_admin() || empty( $_POST['tnt_release_admin_action'] ) ) { return; }
    if ( ! tnt_current_user_can_manage_releases() ) { wp_die( esc_html__( 'You are not allowed to manage Product Releases.', 'toolntip-core' ) ); }
    check_admin_referer( 'tnt_product_release_admin', 'tnt_product_release_nonce' );

    $action     = sanitize_key( wp_unslash( $_POST['tnt_release_admin_action'] ) );
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    $edition_id = isset( $_POST['edition_id'] ) ? absint( $_POST['edition_id'] ) : 0;
    $release_id = isset( $_POST['release_id'] ) ? absint( $_POST['release_id'] ) : 0;
    $result     = null;
    $notice     = '';

    if ( 'save_edition' === $action ) {
        $edition_existing = $edition_id ? tnt_get_product_edition( $edition_id ) : null;
        $result = tnt_save_product_edition( array(
            'product_id'        => $product_id,
            'edition_key'       => isset( $_POST['edition_key'] ) ? wp_unslash( $_POST['edition_key'] ) : '',
            'name'              => isset( $_POST['edition_name'] ) ? wp_unslash( $_POST['edition_name'] ) : '',
            'status'            => isset( $_POST['edition_status'] ) ? wp_unslash( $_POST['edition_status'] ) : 'planned',
            'short_description' => isset( $_POST['short_description'] ) ? wp_unslash( $_POST['short_description'] ) : '',
            'pricing_label'     => isset( $_POST['pricing_label'] ) ? wp_unslash( $_POST['pricing_label'] ) : '',
            'display_order'     => isset( $_POST['display_order'] ) ? absint( $_POST['display_order'] ) : 10,
        ), $edition_id );
        if ( ! is_wp_error( $result ) ) { $edition_id = absint( $result ); $notice = $edition_existing ? 'edition_updated' : 'edition_created'; }
    } elseif ( 'delete_edition' === $action ) {
        $result = tnt_delete_product_edition( $edition_id );
        if ( true === $result ) { $edition_id = 0; $notice = 'edition_deleted'; }
    } elseif ( 'save_release' === $action ) {
        $existing = $release_id ? tnt_get_product_release( $release_id ) : null;
        $data = array(
            'edition_id'              => $edition_id,
            'version'                 => isset( $_POST['version'] ) ? wp_unslash( $_POST['version'] ) : '',
            'release_date'            => isset( $_POST['release_date'] ) ? wp_unslash( $_POST['release_date'] ) : '',
            'status'                  => $existing ? $existing->status : 'draft',
            'platform'                => isset( $_POST['platform'] ) ? wp_unslash( $_POST['platform'] ) : '',
            'architecture'            => isset( $_POST['architecture'] ) ? wp_unslash( $_POST['architecture'] ) : '',
            'artifact_filename'       => isset( $_POST['artifact_filename'] ) ? wp_unslash( $_POST['artifact_filename'] ) : '',
            'artifact_size'           => isset( $_POST['artifact_size'] ) ? wp_unslash( $_POST['artifact_size'] ) : '',
            'artifact_sha256'         => isset( $_POST['artifact_sha256'] ) ? wp_unslash( $_POST['artifact_sha256'] ) : '',
            'release_notes'           => isset( $_POST['release_notes'] ) ? wp_unslash( $_POST['release_notes'] ) : '',
            'system_requirements'     => isset( $_POST['system_requirements'] ) ? wp_unslash( $_POST['system_requirements'] ) : '',
            'license_reference'       => isset( $_POST['license_reference'] ) ? wp_unslash( $_POST['license_reference'] ) : '',
            'documentation_reference' => isset( $_POST['documentation_reference'] ) ? wp_unslash( $_POST['documentation_reference'] ) : '',
        );
        if ( $existing && in_array( $existing->status, array( 'published', 'withdrawn' ), true ) ) {
            $result = tnt_update_product_release_description( $release_id, $data, get_current_user_id() );
        } else {
            $result = tnt_save_product_release( $data, $release_id );
        }
        if ( ! is_wp_error( $result ) ) { $release_id = absint( $result ); $notice = $existing ? 'release_saved' : 'release_created'; }
    } elseif ( 'delete_release' === $action ) {
        $result = tnt_delete_product_release( $release_id );
        if ( true === $result ) { $release_id = 0; $notice = 'release_deleted'; }
    } elseif ( 'validate_release' === $action ) {
        $result = tnt_mark_product_release_ready( $release_id, get_current_user_id() );
        if ( ! is_wp_error( $result ) ) { $notice = 'release_ready'; }
    } elseif ( 'publish_release' === $action ) {
        if ( ! tnt_current_user_can_publish_releases() ) { wp_die( esc_html__( 'You are not allowed to publish Product Releases.', 'toolntip-core' ) ); }
        $result = tnt_publish_product_release( $release_id, get_current_user_id() );
        if ( ! is_wp_error( $result ) ) { $notice = 'release_published'; }
    } elseif ( 'withdraw_release' === $action ) {
        if ( ! tnt_current_user_can_withdraw_releases() ) { wp_die( esc_html__( 'You are not allowed to withdraw Product Releases.', 'toolntip-core' ) ); }
        $result = tnt_withdraw_product_release( $release_id, get_current_user_id() );
        if ( ! is_wp_error( $result ) ) { $notice = 'release_withdrawn'; }
    } else {
        return;
    }

    $args = array( 'product_id' => $product_id );
    if ( $edition_id ) { $args['edition_id'] = $edition_id; }
    if ( $release_id ) { $args['release_id'] = $release_id; }
    if ( is_wp_error( $result ) ) { $args['tnt_error'] = rawurlencode( tnt_release_admin_error_message( $result ) ); }
    else { $args['tnt_notice'] = $notice ? $notice : 'completed'; }
    tnt_release_admin_redirect( $args );
}
add_action( 'admin_init', 'tnt_handle_product_release_admin_actions' );

function tnt_render_product_release_admin_page() {
    if ( ! tnt_current_user_can_manage_releases() ) { wp_die( esc_html__( 'You are not allowed to manage Product Releases.', 'toolntip-core' ) ); }
    $product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
    $edition_id = isset( $_GET['edition_id'] ) ? absint( $_GET['edition_id'] ) : 0;
    $release_id = isset( $_GET['release_id'] ) ? absint( $_GET['release_id'] ) : 0;
    $products = get_posts( array( 'post_type' => 'tnt_product', 'post_status' => array( 'publish','draft','pending','private' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    $edition = $edition_id ? tnt_get_product_edition( $edition_id ) : null;
    $release = $release_id ? tnt_get_product_release( $release_id ) : null;
    if ( $edition && ! $product_id ) { $product_id = absint( $edition->product_id ); }
    if ( $release && ! $edition ) { $edition = tnt_get_product_edition( $release->edition_id ); $edition_id = absint( $release->edition_id ); if ( $edition ) { $product_id = absint( $edition->product_id ); } }
    $editions = $product_id ? tnt_get_product_editions( $product_id, true ) : array();
    ?>
    <div class="wrap"><h1><?php esc_html_e( 'Editions & Releases', 'toolntip-core' ); ?></h1>
    <p><?php esc_html_e( 'Govern Product editions and the Draft → Ready → Published → Withdrawn release lifecycle. Storage replicas are managed separately.', 'toolntip-core' ); ?></p>
    <?php if ( ! empty( $_GET['tnt_error'] ) ) : ?><div class="notice notice-error"><p><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['tnt_error'] ) ) ) ); ?></p></div><?php endif; ?>
    <?php if ( ! empty( $_GET['tnt_notice'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( tnt_release_admin_notice_message( sanitize_key( wp_unslash( $_GET['tnt_notice'] ) ) ) ); ?></p></div>
    <?php endif; ?>
    <form method="get"><input type="hidden" name="post_type" value="tnt_product"><input type="hidden" name="page" value="tnt-product-releases">
    <label for="tnt-product-select"><strong><?php esc_html_e( 'Product', 'toolntip-core' ); ?></strong></label> <select id="tnt-product-select" name="product_id"><option value="0"><?php esc_html_e( 'Select a Product…', 'toolntip-core' ); ?></option><?php foreach ( $products as $product ) : ?><option value="<?php echo esc_attr( $product->ID ); ?>" <?php selected( $product_id, $product->ID ); ?>><?php echo esc_html( $product->post_title ); ?></option><?php endforeach; ?></select> <?php submit_button( __( 'Open', 'toolntip-core' ), 'secondary', '', false ); ?></form>
    <?php if ( $product_id ) : ?>
    <hr><h2><?php esc_html_e( 'Editions', 'toolntip-core' ); ?></h2>
    <?php if ( $editions ) : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Edition', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Key', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Status', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Pricing', 'toolntip-core' ); ?></th><th><?php esc_html_e( 'Releases', 'toolntip-core' ); ?></th></tr></thead><tbody><?php foreach ( $editions as $row ) : ?><tr><td><a href="<?php echo esc_url( tnt_product_release_admin_url( array( 'product_id'=>$product_id,'edition_id'=>$row->id ) ) ); ?>"><strong><?php echo esc_html( $row->name ); ?></strong></a></td><td><?php echo esc_html( $row->edition_key ); ?></td><td><?php echo esc_html( ucfirst( $row->status ) ); ?></td><td><?php echo esc_html( $row->pricing_label ); ?></td><td><?php echo esc_html( count( tnt_get_edition_releases( $row->id ) ) ); ?></td></tr><?php endforeach; ?></tbody></table><?php else : ?><p><?php esc_html_e( 'No Editions exist for this Product.', 'toolntip-core' ); ?></p><?php endif; ?>
    <h3><?php echo $edition ? esc_html__( 'Edit Edition', 'toolntip-core' ) : esc_html__( 'Add Edition', 'toolntip-core' ); ?></h3>
    <form method="post"><?php wp_nonce_field( 'tnt_product_release_admin', 'tnt_product_release_nonce' ); ?><input type="hidden" name="tnt_release_admin_action" value="save_edition"><input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>"><input type="hidden" name="edition_id" value="<?php echo esc_attr( $edition_id ); ?>">
    <table class="form-table"><tr><th><label><?php esc_html_e( 'Name', 'toolntip-core' ); ?></label></th><td><input class="regular-text" required name="edition_name" value="<?php echo esc_attr( $edition ? $edition->name : '' ); ?>"></td></tr><tr><th><?php esc_html_e( 'Key', 'toolntip-core' ); ?></th><td><input class="regular-text" required name="edition_key" value="<?php echo esc_attr( $edition ? $edition->edition_key : '' ); ?>"><p class="description"><?php esc_html_e( 'Stable Product-scoped key, e.g. community.', 'toolntip-core' ); ?></p></td></tr><tr><th><?php esc_html_e( 'Status', 'toolntip-core' ); ?></th><td><select name="edition_status"><?php foreach ( tnt_get_product_edition_statuses() as $s ) : ?><option value="<?php echo esc_attr($s); ?>" <?php selected( $edition ? $edition->status : 'planned', $s ); ?>><?php echo esc_html( ucfirst($s) ); ?></option><?php endforeach; ?></select></td></tr><tr><th><?php esc_html_e( 'Description', 'toolntip-core' ); ?></th><td><textarea class="large-text" rows="3" name="short_description"><?php echo esc_textarea( $edition ? $edition->short_description : '' ); ?></textarea></td></tr><tr><th><?php esc_html_e( 'Pricing label', 'toolntip-core' ); ?></th><td><input class="regular-text" name="pricing_label" value="<?php echo esc_attr( $edition ? $edition->pricing_label : '' ); ?>"></td></tr><tr><th><?php esc_html_e( 'Display order', 'toolntip-core' ); ?></th><td><input type="number" min="0" name="display_order" value="<?php echo esc_attr( $edition ? $edition->display_order : 10 ); ?>"></td></tr></table><?php submit_button( $edition ? __( 'Update Edition', 'toolntip-core' ) : __( 'Add Edition', 'toolntip-core' ) ); ?></form>
    <?php if ( $edition ) : ?><form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this Edition? Editions with Releases cannot be deleted.', 'toolntip-core' ) ); ?>');"><?php wp_nonce_field( 'tnt_product_release_admin', 'tnt_product_release_nonce' ); ?><input type="hidden" name="tnt_release_admin_action" value="delete_edition"><input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>"><input type="hidden" name="edition_id" value="<?php echo esc_attr($edition_id); ?>"><?php submit_button( __( 'Delete Edition', 'toolntip-core' ), 'delete', '', false ); ?></form><?php endif; ?>
    <?php if ( $edition ) : tnt_render_release_admin_section( $product_id, $edition, $release ); endif; ?>
    <?php endif; ?></div><?php
}

function tnt_render_release_admin_section( $product_id, $edition, $release = null ) {
    $releases = tnt_get_edition_releases( $edition->id );
    ?><hr><h2><?php echo esc_html( sprintf( __( 'Releases — %s', 'toolntip-core' ), $edition->name ) ); ?></h2>
    <?php if ( $releases ) : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e('Version','toolntip-core'); ?></th><th><?php esc_html_e('Status','toolntip-core'); ?></th><th><?php esc_html_e('Platform','toolntip-core'); ?></th><th><?php esc_html_e('Artifact','toolntip-core'); ?></th><th><?php esc_html_e('Replicas','toolntip-core'); ?></th></tr></thead><tbody><?php foreach ( $releases as $row ) : ?><tr><td><a href="<?php echo esc_url( tnt_product_release_admin_url( array('product_id'=>$product_id,'edition_id'=>$edition->id,'release_id'=>$row->id) ) ); ?>"><strong><?php echo esc_html($row->version); ?></strong></a></td><td><?php echo esc_html(ucfirst($row->status)); ?></td><td><?php echo esc_html($row->platform); ?></td><td><?php echo esc_html($row->artifact_filename); ?></td><td><span data-tnt-replica-count="<?php echo esc_attr( $row->id ); ?>"><?php echo esc_html(count(tnt_get_product_release_locations($row->id,false))); ?></span></td></tr><?php endforeach; ?></tbody></table><?php else : ?><p><?php esc_html_e('No Releases exist for this Edition.','toolntip-core'); ?></p><?php endif; ?>
    <h3><?php echo $release ? esc_html__( 'Edit Release', 'toolntip-core' ) : esc_html__( 'Add Draft Release', 'toolntip-core' ); ?></h3>
    <?php if ( $release ) : ?><p><strong><?php esc_html_e('Lifecycle status:','toolntip-core'); ?></strong> <?php echo esc_html( ucfirst($release->status) ); ?> &nbsp; <strong><?php esc_html_e('Enabled replicas:','toolntip-core'); ?></strong> <span data-tnt-enabled-replica-count="<?php echo esc_attr( $release->id ); ?>"><?php echo esc_html(count(tnt_get_product_release_locations($release->id,true))); ?></span></p><?php endif; ?>
    <form method="post"><?php wp_nonce_field('tnt_product_release_admin','tnt_product_release_nonce'); ?><input type="hidden" name="tnt_release_admin_action" value="save_release"><input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>"><input type="hidden" name="edition_id" value="<?php echo esc_attr($edition->id); ?>"><input type="hidden" name="release_id" value="<?php echo esc_attr($release ? $release->id : 0); ?>">
    <?php if ( $release ) : ?>
        <div style="margin:12px 0 4px;">
            <?php submit_button( __( 'Save Release', 'toolntip-core' ), 'secondary', 'submit', false ); ?>
            <span class="description" style="margin-left:8px;"><?php esc_html_e( 'Saves Release metadata only. Storage replicas are saved separately below.', 'toolntip-core' ); ?></span>
        </div>
    <?php endif; ?>
    <table class="form-table"><?php
    $fields = array('version'=>'Version','release_date'=>'Release date (YYYY-MM-DD)','platform'=>'Platform','architecture'=>'Architecture','artifact_filename'=>'Artifact filename (.zip/.rar/.iso)','artifact_size'=>'Artifact size (bytes)','artifact_sha256'=>'Artifact SHA-256','documentation_reference'=>'Documentation URL');
    foreach($fields as $key=>$label): $readonly = $release && in_array($release->status,array('published','withdrawn'),true) && in_array($key,array('version','platform','architecture','artifact_filename','artifact_size','artifact_sha256'),true); ?><tr><th><?php echo esc_html__($label,'toolntip-core'); ?></th><td><input class="large-text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($release ? $release->{$key} : ''); ?>" <?php echo $readonly ? 'readonly' : ''; ?>></td></tr><?php endforeach; ?>
    <tr><th><?php esc_html_e('Release notes','toolntip-core'); ?></th><td><textarea class="large-text" rows="4" name="release_notes"><?php echo esc_textarea($release ? $release->release_notes : ''); ?></textarea></td></tr><tr><th><?php esc_html_e('System requirements','toolntip-core'); ?></th><td><textarea class="large-text" rows="4" name="system_requirements"><?php echo esc_textarea($release ? $release->system_requirements : ''); ?></textarea></td></tr><tr><th><?php esc_html_e('License reference','toolntip-core'); ?></th><td><textarea class="large-text" rows="3" name="license_reference"><?php echo esc_textarea($release ? $release->license_reference : ''); ?></textarea></td></tr></table>
    <?php submit_button($release ? __('Save Release','toolntip-core') : __('Create Draft Release','toolntip-core')); ?>
    <?php if ( $release ) : ?><p class="description"><?php esc_html_e( 'This button saves Release metadata only. Use Add/Update Storage Replica in the separate Storage Replicas section for provider references and routing policy.', 'toolntip-core' ); ?></p><?php endif; ?>
    </form>
    <?php if ( $release ) : ?><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <?php if ( 'draft' === $release->status ) : tnt_release_admin_action_form('validate_release',__('Validate → Ready','toolntip-core'),$product_id,$edition->id,$release->id,'secondary'); endif; ?>
    <?php if ( 'ready' === $release->status && tnt_current_user_can_publish_releases() ) : tnt_release_admin_action_form('publish_release',__('Publish Release','toolntip-core'),$product_id,$edition->id,$release->id,'primary'); endif; ?>
    <?php if ( 'published' === $release->status && tnt_current_user_can_withdraw_releases() ) : tnt_release_admin_action_form('withdraw_release',__('Withdraw Release','toolntip-core'),$product_id,$edition->id,$release->id,'secondary'); endif; ?>
    <?php if ( in_array($release->status,array('draft','ready'),true) ) : tnt_release_admin_action_form('delete_release',__('Delete Release','toolntip-core'),$product_id,$edition->id,$release->id,'delete',true); endif; ?>
    </div><p class="description"><?php esc_html_e('A Release cannot become Ready until it has at least one enabled, valid storage location.','toolntip-core'); ?></p>
    <?php if ( function_exists( 'tnt_render_release_location_admin_section' ) ) { tnt_render_release_location_admin_section( $product_id, $edition, $release ); } ?>
    <?php endif;
}

function tnt_release_admin_action_form($action,$label,$product_id,$edition_id,$release_id,$class='secondary',$confirm=false){ ?><form method="post" style="display:inline" <?php if($confirm): ?>onsubmit="return confirm('<?php echo esc_js(__('Delete this Draft/Ready Release?','toolntip-core')); ?>');"<?php endif; ?>><?php wp_nonce_field('tnt_product_release_admin','tnt_product_release_nonce'); ?><input type="hidden" name="tnt_release_admin_action" value="<?php echo esc_attr($action); ?>"><input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>"><input type="hidden" name="edition_id" value="<?php echo esc_attr($edition_id); ?>"><input type="hidden" name="release_id" value="<?php echo esc_attr($release_id); ?>"><?php submit_button($label,$class,'',false); ?></form><?php }
