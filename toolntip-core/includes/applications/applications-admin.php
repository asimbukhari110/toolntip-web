<?php
/**
 * ToolNTip Applications Manager.
 *
 * Governed inventory for installed package-backed applications.
 * Draft Page provisioning, explicit Tool linkage and governed installed-version activation are observable here.
 *
 * @package ToolntipCore
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tnt_applications_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=tool',
        __( 'ToolNTip Applications', 'toolntip-core' ),
        __( 'Applications', 'toolntip-core' ),
        'manage_options',
        'tnt-applications',
        'tnt_render_applications_admin_page'
    );
}
add_action( 'admin_menu', 'tnt_applications_admin_menu' );

function tnt_get_application_admin_runtime_status( $application_id ) {
    $statuses = function_exists( 'tnt_get_application_package_runtime_statuses' ) ? tnt_get_application_package_runtime_statuses() : array();
    if ( isset( $statuses[ $application_id ] ) && is_array( $statuses[ $application_id ] ) ) {
        return $statuses[ $application_id ];
    }
    return array( 'registered' => false, 'version' => '', 'reason' => '' );
}

function tnt_render_applications_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to view ToolNTip applications.', 'toolntip-core' ) );
    }
    $apps = function_exists( 'tnt_get_installed_application_packages' ) ? tnt_get_installed_application_packages( true ) : array();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'ToolNTip Applications', 'toolntip-core' ); ?></h1>
        <p><?php esc_html_e( 'Governed inventory of validated applications installed through ToolNTip Application Packages. Installed versions remain side-by-side; changing the active version is an explicit administrator action.', 'toolntip-core' ); ?></p>
        <?php if ( isset( $_GET['tnt_lifecycle'] ) && 'uninstalled' === sanitize_key( wp_unslash( $_GET['tnt_lifecycle'] ) ) ) : ?>
            <div class="notice notice-success inline is-dismissible"><p><?php echo esc_html( sprintf( __( 'Application %s was uninstalled. Its Tool and editorial content were preserved; its runtime Page was returned to Draft.', 'toolntip-core' ), isset( $_GET['tnt_app'] ) ? sanitize_key( wp_unslash( $_GET['tnt_app'] ) ) : '' ) ); ?><?php if ( isset( $_GET['tnt_cleanup'] ) && 'pending' === sanitize_key( wp_unslash( $_GET['tnt_cleanup'] ) ) ) : ?> <?php esc_html_e( 'Residual quarantined files will be removed by governed housekeeping.', 'toolntip-core' ); ?><?php endif; ?></p></div>
        <?php elseif ( isset( $_GET['tnt_lifecycle'] ) && 'activated' === sanitize_key( wp_unslash( $_GET['tnt_lifecycle'] ) ) ) : ?>
            <div class="notice notice-success inline is-dismissible"><p><?php echo esc_html( sprintf( __( 'Application %1$s is now using version %2$s.', 'toolntip-core' ), isset( $_GET['tnt_app'] ) ? sanitize_key( wp_unslash( $_GET['tnt_app'] ) ) : '', isset( $_GET['tnt_version'] ) ? sanitize_text_field( wp_unslash( $_GET['tnt_version'] ) ) : '' ) ); ?></p></div>
        <?php elseif ( isset( $_GET['tnt_lifecycle'] ) && 'error' === sanitize_key( wp_unslash( $_GET['tnt_lifecycle'] ) ) ) : ?>
            <div class="notice notice-error inline is-dismissible"><p><?php echo esc_html( isset( $_GET['tnt_message'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['tnt_message'] ) ) ) : __( 'The application version could not be activated.', 'toolntip-core' ) ); ?></p></div>
        <?php endif; ?>
        <p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tool&page=tnt-package-installer' ) ); ?>"><?php esc_html_e( 'Open Application Packages', 'toolntip-core' ); ?></a></p>
        <?php
        $confirm_id = isset( $_GET['tnt_confirm_uninstall'] ) ? sanitize_key( wp_unslash( $_GET['tnt_confirm_uninstall'] ) ) : '';
        if ( $confirm_id && isset( $apps[ $confirm_id ] ) && wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'tnt_confirm_uninstall_' . $confirm_id ) ) :
            $confirm_app = $apps[ $confirm_id ];
        ?>
            <div id="tnt-uninstall-confirmation" class="notice notice-warning inline" style="margin:18px 0;padding:12px 16px;border-left-color:#d63638">
                <h2 style="margin-top:0"><?php echo esc_html( sprintf( __( 'Uninstall %s?', 'toolntip-core' ), $confirm_app['name'] ) ); ?></h2>
                <p><?php esc_html_e( 'This removes all installed package versions and disables the runtime. The Tool, runtime Page, Page → Tool relationship, and related editorial content are preserved. The runtime Page will be returned to Draft.', 'toolntip-core' ); ?></p>
                <p><strong><?php esc_html_e( 'Application Version Rollback cannot undo an uninstall. Reinstall the package to restore the runtime.', 'toolntip-core' ); ?></strong></p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
                    <input type="hidden" name="action" value="tnt_uninstall_application_package">
                    <input type="hidden" name="application_id" value="<?php echo esc_attr( $confirm_id ); ?>">
                    <?php wp_nonce_field( 'tnt_uninstall_application_package_' . $confirm_id ); ?>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Confirm Uninstall', 'toolntip-core' ); ?></button>
                </form>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tool&page=tnt-applications' ) ); ?>"><?php esc_html_e( 'Cancel', 'toolntip-core' ); ?></a>
            </div>
        <?php endif; ?>
        <?php if ( empty( $apps ) ) : ?>
            <div class="notice notice-info inline"><p><?php esc_html_e( 'No valid installed applications were discovered.', 'toolntip-core' ); ?></p></div>
        <?php else : ?>
            <table class="widefat striped" style="max-width:1200px">
                <thead><tr>
                    <th><?php esc_html_e( 'Application', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'ID', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Installed Versions', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Active Version', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Version Actions', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Runtime', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Layout', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Capabilities', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Runtime Page', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Linked Tool', 'toolntip-core' ); ?></th>
                    <th><?php esc_html_e( 'Application Action', 'toolntip-core' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $apps as $app ) :
                    $status = tnt_get_application_admin_runtime_status( $app['id'] );
                    $active_version = $app['active_version'];
                    $record = $active_version && isset( $app['versions'][ $active_version ] ) ? $app['versions'][ $active_version ] : end( $app['versions'] );
                    $manifest = isset( $record['manifest'] ) ? $record['manifest'] : array();
                    $layout = isset( $manifest['runtime']['default_layout'] ) ? $manifest['runtime']['default_layout'] : '';
                    $caps = isset( $manifest['capabilities'] ) && is_array( $manifest['capabilities'] ) ? $manifest['capabilities'] : array();
                    $page_title = isset( $manifest['page']['title'] ) ? $manifest['page']['title'] : '';
                    $page_slug = isset( $manifest['page']['slug'] ) ? $manifest['page']['slug'] : '';
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $app['name'] ); ?></strong></td>
                        <td><code><?php echo esc_html( $app['id'] ); ?></code></td>
                        <td><?php echo esc_html( implode( ', ', array_keys( $app['versions'] ) ) ); ?></td>
                        <td><?php echo $active_version ? esc_html( $active_version ) : esc_html__( 'Not active', 'toolntip-core' ); ?></td>
                        <td><?php foreach ( array_keys( $app['versions'] ) as $installed_version ) : ?>
                            <?php if ( $installed_version === $active_version ) : ?>
                                <strong><?php echo esc_html( $installed_version ); ?> · <?php esc_html_e( 'Active', 'toolntip-core' ); ?></strong><br>
                            <?php else :
                                $is_rollback = $active_version && version_compare( $installed_version, $active_version, '<' );
                                ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:0 6px 6px 0">
                                    <input type="hidden" name="action" value="tnt_activate_application_package_version">
                                    <input type="hidden" name="application_id" value="<?php echo esc_attr( $app['id'] ); ?>">
                                    <input type="hidden" name="version" value="<?php echo esc_attr( $installed_version ); ?>">
                                    <?php wp_nonce_field( 'tnt_activate_application_package_version_' . $app['id'] . '_' . $installed_version ); ?>
                                    <button type="submit" class="button button-small"><?php echo esc_html( $is_rollback ? sprintf( __( 'Rollback to %s', 'toolntip-core' ), $installed_version ) : sprintf( __( 'Activate %s', 'toolntip-core' ), $installed_version ) ); ?></button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?></td>
                        <td><?php
                            if ( ! empty( $status['registered'] ) ) { esc_html_e( 'Registered', 'toolntip-core' ); }
                            elseif ( ! empty( $status['reason'] ) ) { echo esc_html( sprintf( __( 'Unavailable (%s)', 'toolntip-core' ), $status['reason'] ) ); }
                            else { esc_html_e( 'Not registered', 'toolntip-core' ); }
                        ?></td>
                        <td><?php echo esc_html( $layout ); ?></td>
                        <td><?php echo empty( $caps ) ? '&mdash;' : esc_html( implode( ', ', $caps ) ); ?></td>
                        <td><?php
                            $page_state = function_exists( 'tnt_get_application_package_page_status' ) ? tnt_get_application_package_page_status( $app['id'] ) : array( 'state' => 'missing' );
                            if ( 'provisioned' === $page_state['state'] ) {
                                echo esc_html( $page_title );
                                if ( $page_slug ) { echo '<br><code>/' . esc_html( $page_slug ) . '/</code>'; }
                                echo '<br><strong>' . esc_html__( 'Provisioned', 'toolntip-core' ) . '</strong> · ' . esc_html( ucfirst( (string) $page_state['post_status'] ) );
                                echo ' · <a href="' . esc_url( get_edit_post_link( $page_state['page_id'] ) ) . '">' . esc_html__( 'Edit Page', 'toolntip-core' ) . '</a>';
                            } elseif ( 'collision' === $page_state['state'] ) {
                                echo '<strong>' . esc_html__( 'Not provisioned', 'toolntip-core' ) . '</strong><br>' . esc_html( $page_state['message'] );
                                if ( $page_slug ) { echo '<br><code>/' . esc_html( $page_slug ) . '/</code>'; }
                            } else {
                                echo esc_html__( 'Not provisioned', 'toolntip-core' );
                            }
                        ?></td>
                        <td><?php
                            $linkage = function_exists( 'tnt_get_application_package_tool_linkage' ) ? tnt_get_application_package_tool_linkage( $app['id'] ) : array( 'state' => 'unlinked' );
                            if ( 'linked' === $linkage['state'] && $linkage['tool'] instanceof WP_Post ) {
                                echo '<strong>' . esc_html( get_the_title( $linkage['tool'] ) ) . '</strong>';
                                echo '<br><code>/tool/' . esc_html( $linkage['tool']->post_name ) . '/</code>';
                                echo '<br><a href="' . esc_url( get_edit_post_link( $linkage['tool_id'] ) ) . '">' . esc_html__( 'Edit Tool', 'toolntip-core' ) . '</a>';
                            } elseif ( 'page_missing' === $linkage['state'] ) {
                                esc_html_e( 'Runtime Page unavailable', 'toolntip-core' );
                            } else {
                                echo '<strong>' . esc_html__( 'Not linked', 'toolntip-core' ) . '</strong>';
                                if ( ! empty( $page_state['page_id'] ) ) {
                                    echo '<br><a href="' . esc_url( get_edit_post_link( $page_state['page_id'] ) ) . '">' . esc_html__( 'Link a Tool on the Page', 'toolntip-core' ) . '</a>';
                                }
                            }
                        ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'post_type' => 'tool', 'page' => 'tnt-applications', 'tnt_confirm_uninstall' => $app['id'] ), admin_url( 'edit.php' ) ), 'tnt_confirm_uninstall_' . $app['id'] ) ) . '#tnt-uninstall-confirmation'; ?>"><?php esc_html_e( 'Uninstall', 'toolntip-core' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p class="description"><?php esc_html_e( 'Draft Page provisioning is automatic and idempotent. Tool linkage reuses the existing Page → Tool relationship. Version activation and rollback switch only the selected installed package version. Uninstall removes package versions while preserving the Tool and editorial content and returning the runtime Page to Draft.', 'toolntip-core' ); ?></p>
    </div>
    <?php
}
