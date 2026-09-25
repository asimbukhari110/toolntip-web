<?php
/**
 * Product storage-provider configuration administration.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_current_user_can_manage_product_storage() {
    return current_user_can( 'manage_options' ) || current_user_can( 'manage_toolntip_product_storage' );
}

function tnt_product_storage_admin_url( $args = array() ) {
    return add_query_arg( array_merge( array( 'post_type' => 'tnt_product', 'page' => 'tnt-product-storage' ), $args ), admin_url( 'edit.php' ) );
}

function tnt_add_product_storage_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=tnt_product',
        __( 'Product Storage', 'toolntip-core' ),
        __( 'Product Storage', 'toolntip-core' ),
        'manage_options',
        'tnt-product-storage',
        'tnt_render_product_storage_admin_page'
    );
}
add_action( 'admin_menu', 'tnt_add_product_storage_admin_menu', 35 );

function tnt_product_storage_admin_notice( $type, $message ) {
    return add_query_arg( array( 'tnt_storage_notice_type' => sanitize_key( $type ), 'tnt_storage_notice' => rawurlencode( $message ) ), tnt_product_storage_admin_url() );
}

function tnt_product_storage_provider_label( $provider ) {
    return 'google_drive' === $provider ? __( 'Google Drive', 'toolntip-core' ) : __( 'OneDrive / Microsoft Graph', 'toolntip-core' );
}

function tnt_handle_product_storage_save() {
    if ( empty( $_POST['tnt_product_storage_action'] ) ) { return; }
    if ( ! tnt_current_user_can_manage_product_storage() ) { wp_die( esc_html__( 'You are not allowed to manage Product storage.', 'toolntip-core' ) ); }
    check_admin_referer( 'tnt_product_storage', 'tnt_product_storage_nonce' );
    $action = sanitize_key( wp_unslash( $_POST['tnt_product_storage_action'] ) );
    $provider = sanitize_key( wp_unslash( $_POST['provider'] ?? '' ) );
    if ( ! in_array( $provider, array( 'google_drive', 'onedrive' ), true ) ) { return; }

    if ( 'save' === $action ) {
        $data = array(
            'client_id' => wp_unslash( $_POST['client_id'] ?? '' ),
            'client_secret' => wp_unslash( $_POST['client_secret'] ?? '' ),
        );
        if ( 'onedrive' === $provider ) { $data['tenant'] = wp_unslash( $_POST['tenant'] ?? 'common' ); }
        $result = tnt_save_product_provider_config( $provider, $data );
        $url = is_wp_error( $result ) ? tnt_product_storage_admin_notice( 'error', $result->get_error_message() ) : tnt_product_storage_admin_notice( 'success', sprintf( __( '%s configuration saved successfully.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) );
        wp_safe_redirect( $url ); exit;
    }
    if ( 'disconnect' === $action ) {
        tnt_clear_product_provider_tokens( $provider );
        wp_safe_redirect( tnt_product_storage_admin_notice( 'success', sprintf( __( '%s OAuth tokens disconnected. Client configuration was preserved.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) ) ); exit;
    }
}
add_action( 'admin_init', 'tnt_handle_product_storage_save' );

function tnt_product_provider_authorization_url( $provider ) {
    $config = tnt_get_product_provider_config( $provider, true );
    if ( empty( $config['client_id'] ) || empty( $config['client_secret'] ) ) { return new WP_Error( 'tnt_provider_client_missing', __( 'Save the OAuth client ID and client secret before connecting.', 'toolntip-core' ) ); }
    $state = wp_generate_password( 48, false, false );
    set_transient( 'tnt_provider_oauth_' . hash( 'sha256', $state ), array( 'user_id' => get_current_user_id(), 'provider' => $provider ), 10 * MINUTE_IN_SECONDS );
    $redirect_uri = tnt_product_provider_redirect_uri( $provider );
    if ( 'google_drive' === $provider ) {
        $query = http_build_query(
            array(
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirect_uri,
                'response_type' => 'code',
                'scope' => 'https://www.googleapis.com/auth/drive.readonly',
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
                'state' => $state,
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }
    $tenant = rawurlencode( $config['tenant'] ?: 'common' );
    $query = http_build_query(
        array(
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => 'offline_access Files.Read',
            'state' => $state,
        ),
        '',
        '&',
        PHP_QUERY_RFC3986
    );
    return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?" . $query;
}

function tnt_handle_product_storage_connect() {
    if ( empty( $_GET['tnt_storage_connect'] ) ) { return; }
    if ( ! tnt_current_user_can_manage_product_storage() ) { wp_die( esc_html__( 'You are not allowed to manage Product storage.', 'toolntip-core' ) ); }
    $provider = sanitize_key( wp_unslash( $_GET['tnt_storage_connect'] ) );
    check_admin_referer( 'tnt_product_storage_connect_' . $provider );
    $url = tnt_product_provider_authorization_url( $provider );
    if ( is_wp_error( $url ) ) { wp_safe_redirect( tnt_product_storage_admin_notice( 'error', $url->get_error_message() ) ); exit; }
    wp_redirect( $url ); exit; // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- trusted OAuth provider URL.
}
add_action( 'admin_init', 'tnt_handle_product_storage_connect' );

function tnt_handle_product_storage_oauth_callback() {
    $state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
    $record = $state ? get_transient( 'tnt_provider_oauth_' . hash( 'sha256', $state ) ) : false;

    // Google identifies the callback explicitly in the redirect URI. OneDrive uses a
    // query-free redirect URI for personal-account compatibility, so identify it from
    // the signed-in administrator's short-lived server-side OAuth state instead.
    if ( ! empty( $_GET['tnt_provider_oauth'] ) ) {
        $provider = sanitize_key( wp_unslash( $_GET['tnt_provider_oauth'] ) );
    } elseif ( is_array( $record ) && 'onedrive' === ( $record['provider'] ?? '' ) && ( isset( $_GET['code'] ) || isset( $_GET['error'] ) ) ) {
        $provider = 'onedrive';
    } else {
        return;
    }

    if ( ! tnt_current_user_can_manage_product_storage() ) { wp_die( esc_html__( 'You are not allowed to manage Product storage.', 'toolntip-core' ) ); }
    if ( ! is_array( $record ) || absint( $record['user_id'] ?? 0 ) !== get_current_user_id() || ( $record['provider'] ?? '' ) !== $provider ) {
        wp_safe_redirect( tnt_product_storage_admin_notice( 'error', sprintf( __( '%s OAuth state validation failed. Please try connecting again.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) ) ); exit;
    }
    delete_transient( 'tnt_provider_oauth_' . hash( 'sha256', $state ) );
    if ( ! empty( $_GET['error'] ) ) { wp_safe_redirect( tnt_product_storage_admin_notice( 'error', sprintf( __( '%s authorization was not completed.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) ) ); exit; }
    $code = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
    if ( '' === $code ) { wp_safe_redirect( tnt_product_storage_admin_notice( 'error', sprintf( __( '%s did not return an authorization code.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) ) ); exit; }
    $config = tnt_get_product_provider_config( $provider, true );
    $body = array(
        'client_id' => $config['client_id'], 'client_secret' => $config['client_secret'], 'code' => $code,
        'redirect_uri' => tnt_product_provider_redirect_uri( $provider ), 'grant_type' => 'authorization_code',
    );
    if ( 'onedrive' === $provider ) { $body['scope'] = 'offline_access Files.Read'; }
    $response = wp_remote_post( tnt_product_provider_token_endpoint( $provider, $config ), array( 'timeout' => 20, 'body' => $body ) );
    $data = is_wp_error( $response ) ? array() : json_decode( wp_remote_retrieve_body( $response ), true );
    $code_http = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
    if ( $code_http < 200 || $code_http >= 300 || empty( $data['access_token'] ) || empty( $data['refresh_token'] ) ) {
        wp_safe_redirect( tnt_product_storage_admin_notice( 'error', sprintf( __( '%s token exchange failed. Verify the OAuth client configuration and redirect URI.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) ) ); exit;
    }
    $saved = tnt_store_product_provider_tokens( $provider, $data );
    if ( is_wp_error( $saved ) ) { wp_safe_redirect( tnt_product_storage_admin_notice( 'error', $saved->get_error_message() ) ); exit; }
    wp_safe_redirect( tnt_product_storage_admin_notice( 'success', sprintf( __( '%s connected successfully.', 'toolntip-core' ), tnt_product_storage_provider_label( $provider ) ) ) ); exit;
}
add_action( 'admin_init', 'tnt_handle_product_storage_oauth_callback', 20 );

function tnt_render_product_storage_provider_card( $provider, $label ) {
    $config = tnt_get_product_provider_config( $provider, false );
    $connected = tnt_product_provider_is_live_configured( $provider );
    $redirect_uri = tnt_product_provider_redirect_uri( $provider );
    $connect_url = wp_nonce_url( add_query_arg( 'tnt_storage_connect', $provider, tnt_product_storage_admin_url() ), 'tnt_product_storage_connect_' . $provider );
    ?>
    <div class="card" style="max-width:900px;margin:16px 0;padding:8px 20px 20px;">
        <h2><?php echo esc_html( $label ); ?></h2>
        <p><strong><?php esc_html_e( 'Connection:', 'toolntip-core' ); ?></strong> <?php echo $connected ? esc_html__( 'Connected', 'toolntip-core' ) : esc_html__( 'Not connected', 'toolntip-core' ); ?></p>
        <p><strong><?php esc_html_e( 'OAuth redirect URI:', 'toolntip-core' ); ?></strong><br><code style="word-break:break-all"><?php echo esc_html( $redirect_uri ); ?></code></p>
        <form method="post">
            <?php wp_nonce_field( 'tnt_product_storage', 'tnt_product_storage_nonce' ); ?>
            <input type="hidden" name="tnt_product_storage_action" value="save">
            <input type="hidden" name="provider" value="<?php echo esc_attr( $provider ); ?>">
            <table class="form-table"><tbody>
            <?php if ( 'onedrive' === $provider ) : ?>
                <tr><th><label for="<?php echo esc_attr( $provider ); ?>-tenant"><?php esc_html_e( 'Tenant', 'toolntip-core' ); ?></label></th><td><input class="regular-text" id="<?php echo esc_attr( $provider ); ?>-tenant" name="tenant" value="<?php echo esc_attr( $config['tenant'] ?: 'common' ); ?>"><p class="description"><?php esc_html_e( 'Use common for multi-tenant/personal sign-in, or your tenant ID/domain to restrict sign-in.', 'toolntip-core' ); ?></p></td></tr>
            <?php endif; ?>
            <tr><th><label for="<?php echo esc_attr( $provider ); ?>-client-id"><?php esc_html_e( 'OAuth Client ID', 'toolntip-core' ); ?></label></th><td><input class="large-text" id="<?php echo esc_attr( $provider ); ?>-client-id" name="client_id" value="<?php echo esc_attr( $config['client_id'] ); ?>" autocomplete="off"></td></tr>
            <tr><th><label for="<?php echo esc_attr( $provider ); ?>-client-secret"><?php esc_html_e( 'OAuth Client Secret', 'toolntip-core' ); ?></label></th><td><input class="large-text" type="password" id="<?php echo esc_attr( $provider ); ?>-client-secret" name="client_secret" value="" autocomplete="new-password" placeholder="<?php echo $config['client_secret'] ? esc_attr__( 'Stored — leave blank to keep current secret', 'toolntip-core' ) : ''; ?>"><p class="description"><?php esc_html_e( 'Secrets and OAuth tokens are encrypted before database storage and are never written to Release, audit, or download records.', 'toolntip-core' ); ?></p></td></tr>
            </tbody></table>
            <?php submit_button( __( 'Save Configuration', 'toolntip-core' ), 'secondary', 'submit', false ); ?>
        </form>
        <p style="margin-top:16px;">
            <a class="button button-primary" href="<?php echo esc_url( $connect_url ); ?>"><?php echo $connected ? esc_html__( 'Reconnect', 'toolntip-core' ) : esc_html__( 'Connect', 'toolntip-core' ); ?></a>
        </p>
        <?php if ( $connected ) : ?>
        <form method="post" style="margin-top:10px;">
            <?php wp_nonce_field( 'tnt_product_storage', 'tnt_product_storage_nonce' ); ?>
            <input type="hidden" name="tnt_product_storage_action" value="disconnect"><input type="hidden" name="provider" value="<?php echo esc_attr( $provider ); ?>">
            <?php submit_button( __( 'Disconnect OAuth Tokens', 'toolntip-core' ), 'delete', 'submit', false, array( 'onclick' => "return confirm('Disconnect this provider? Release references will be preserved.');" ) ); ?>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

function tnt_render_product_storage_admin_page() {
    if ( ! tnt_current_user_can_manage_product_storage() ) { wp_die( esc_html__( 'You are not allowed to manage Product storage.', 'toolntip-core' ) ); }
    $notice = isset( $_GET['tnt_storage_notice'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['tnt_storage_notice'] ) ) ) : '';
    $notice_type = isset( $_GET['tnt_storage_notice_type'] ) ? sanitize_key( wp_unslash( $_GET['tnt_storage_notice_type'] ) ) : 'success';
    ?>
    <div class="wrap"><h1><?php esc_html_e( 'Product Storage', 'toolntip-core' ); ?></h1>
    <p><?php esc_html_e( 'Configure the central OAuth connections used to verify and deliver external Product Release artifacts. WordPress remains the control plane and never stores provider credentials on individual Releases.', 'toolntip-core' ); ?></p>
    <?php if ( $notice ) : ?><div class="notice <?php echo 'error' === $notice_type ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
    <?php tnt_render_product_storage_provider_card( 'google_drive', __( 'Google Drive', 'toolntip-core' ) ); ?>
    <p class="description" style="max-width:900px;"><?php esc_html_e( 'Google Drive uses the Drive API readonly scope. Browser delivery relies on the file webContentLink; this is not a short-lived signed URL, so use a sharing configuration appropriate for your distribution policy.', 'toolntip-core' ); ?></p>
    <?php tnt_render_product_storage_provider_card( 'onedrive', __( 'OneDrive / Microsoft Graph', 'toolntip-core' ) ); ?>
    <p class="description" style="max-width:900px;"><?php esc_html_e( 'OneDrive uses delegated Files.Read plus offline_access. Microsoft Graph supplies a short-lived preauthenticated download URL for browser delivery.', 'toolntip-core' ); ?></p>
    </div>
    <?php
}
