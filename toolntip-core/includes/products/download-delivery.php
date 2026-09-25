<?php
/**
 * ToolNTip governed Product download delivery service.
 *
 * Resolves stable ToolNTip download URLs to authenticated, Published Releases,
 * selects an eligible storage replica, records the redirect event and sends the
 * browser directly to the provider-owned destination. Artifact bytes never pass
 * through WordPress.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register governed Product download routes.
 *
 * @return void
 */
function tnt_register_product_download_rewrite_rules() {
    add_rewrite_rule(
        '^products/([^/]+)/download/([^/]+)/([^/]+)/?$',
        'index.php?tnt_product_download=1&tnt_product_slug=$matches[1]&tnt_edition_key=$matches[2]&tnt_release_version=$matches[3]',
        'top'
    );
}
add_action( 'init', 'tnt_register_product_download_rewrite_rules', 12 );

/**
 * Register internal download query vars.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function tnt_register_product_download_query_vars( $vars ) {
    $vars[] = 'tnt_product_download';
    $vars[] = 'tnt_product_slug';
    $vars[] = 'tnt_edition_key';
    $vars[] = 'tnt_release_version';
    return array_values( array_unique( $vars ) );
}
add_filter( 'query_vars', 'tnt_register_product_download_query_vars' );

/**
 * Whether the current request targets the governed Product download endpoint.
 *
 * @return bool
 */
function tnt_is_product_download_request() {
    return '1' === (string) get_query_var( 'tnt_product_download' );
}

/**
 * Build the stable ToolNTip download URL for a Release.
 *
 * @param object|int $release Release row or ID.
 * @param bool       $latest Whether to emit the Edition latest route.
 * @return string
 */
function tnt_get_product_release_download_url( $release, $latest = false ) {
    if ( ! is_object( $release ) ) {
        $release = tnt_get_product_release( $release );
    }
    if ( ! $release ) {
        return '';
    }

    $edition = tnt_get_product_edition( $release->edition_id );
    if ( ! $edition ) {
        return '';
    }

    $product = get_post( $edition->product_id );
    if ( ! $product || 'tnt_product' !== $product->post_type ) {
        return '';
    }

    $version = $latest ? 'latest' : (string) $release->version;

    return home_url(
        user_trailingslashit(
            'products/' . rawurlencode( $product->post_name ) .
            '/download/' . rawurlencode( $edition->edition_key ) .
            '/' . rawurlencode( $version )
        )
    );
}

/**
 * Resolve a public Product by slug.
 *
 * @param string $slug Product slug.
 * @return WP_Post|null
 */
function tnt_resolve_public_product_for_download( $slug ) {
    $slug = sanitize_title( (string) $slug );
    if ( '' === $slug ) {
        return null;
    }

    $product = get_page_by_path( $slug, OBJECT, 'tnt_product' );
    if ( ! $product || 'publish' !== $product->post_status ) {
        return null;
    }

    return $product;
}

/**
 * Return the latest Published Release for an Edition.
 *
 * Release Date is authoritative when present. Publication time and ID provide
 * deterministic fallbacks/tie-breakers without lexically sorting version text.
 *
 * @param int $edition_id Edition ID.
 * @return object|null
 */
function tnt_get_latest_published_product_release( $edition_id ) {
    global $wpdb;

    $edition_id = absint( $edition_id );
    if ( ! $edition_id || ! tnt_product_platform_ready() ) {
        return null;
    }

    $tables = tnt_get_product_table_names();

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$tables['releases']} WHERE edition_id = %d AND status = %s ORDER BY release_date DESC, published_at DESC, id DESC LIMIT 1",
            $edition_id,
            'published'
        )
    );
}

/**
 * Resolve Product, Edition and Published Release from public route identifiers.
 *
 * @param string $product_slug Product slug.
 * @param string $edition_key Edition key.
 * @param string $version Exact version or latest.
 * @return array|WP_Error
 */
function tnt_resolve_product_download_target( $product_slug, $edition_key, $version ) {
    $product = tnt_resolve_public_product_for_download( $product_slug );
    if ( ! $product ) {
        return new WP_Error( 'tnt_download_product_not_found', __( 'Product not found.', 'toolntip-core' ) );
    }

    $edition_key = tnt_sanitize_product_edition_key( $edition_key );
    $edition     = $edition_key ? tnt_get_product_edition_by_key( $product->ID, $edition_key ) : null;
    if ( ! $edition ) {
        return new WP_Error( 'tnt_download_edition_not_found', __( 'Product Edition not found.', 'toolntip-core' ) );
    }

    $version = trim( sanitize_text_field( rawurldecode( (string) $version ) ) );
    if ( '' === $version ) {
        return new WP_Error( 'tnt_download_release_not_found', __( 'Product Release not found.', 'toolntip-core' ) );
    }

    if ( 'latest' === strtolower( $version ) ) {
        $release = tnt_get_latest_published_product_release( $edition->id );
    } else {
        $release = tnt_get_product_release_by_version( $edition->id, $version );
    }

    if ( ! $release || 'published' !== $release->status ) {
        return new WP_Error( 'tnt_download_release_not_found', __( 'Product Release not found.', 'toolntip-core' ) );
    }

    return array(
        'product' => $product,
        'edition' => $edition,
        'release' => $release,
    );
}

/**
 * Central Product Release download authorization boundary.
 *
 * Core 1.2.0 requires an existing registered user. Future account/licensing
 * layers may narrow authorization through the filter without changing routing.
 *
 * @param int        $user_id User ID.
 * @param object|int $release Release row or ID.
 * @return bool
 */
function tnt_can_download_product_release( $user_id, $release ) {
    $user_id = absint( $user_id );
    if ( ! $user_id || ! get_userdata( $user_id ) ) {
        return false;
    }

    if ( ! is_object( $release ) ) {
        $release = tnt_get_product_release( $release );
    }
    if ( ! $release || 'published' !== $release->status ) {
        return false;
    }

    return (bool) apply_filters( 'tnt_can_download_product_release', true, $user_id, $release );
}

/**
 * Return the provider-health cache TTL.
 *
 * @param object $location Release location.
 * @return int Seconds.
 */
function tnt_get_product_provider_health_cache_ttl( $location ) {
    $ttl = (int) apply_filters( 'tnt_product_provider_health_cache_ttl', 300, $location );
    return max( 0, $ttl );
}

/**
 * Clear the short-lived live delivery-health cache for one Release location.
 *
 * Admin routing changes and explicit provider verification call this so the
 * next governed download evaluates the current replica state immediately.
 *
 * @param int $location_id Release location ID.
 * @return void
 */
function tnt_clear_product_release_location_health_cache( $location_id ) {
    $location_id = absint( $location_id );
    if ( $location_id ) {
        delete_transient( 'tnt_prlh_' . $location_id );
    }
}

/**
 * Normalize provider health into a delivery eligibility record.
 *
 * @param array|WP_Error $result Provider health result.
 * @return array
 */
function tnt_normalize_product_provider_health( $result ) {
    if ( is_wp_error( $result ) ) {
        return array(
            'healthy' => false,
            'status'  => sanitize_key( $result->get_error_code() ?: 'error' ),
        );
    }

    $result = is_array( $result ) ? $result : array();
    $status = isset( $result['status'] ) ? sanitize_key( $result['status'] ) : '';

    if ( array_key_exists( 'healthy', $result ) ) {
        $healthy = (bool) $result['healthy'];
    } else {
        $healthy = in_array( $status, array( 'healthy', 'ok', 'available' ), true );
    }

    if ( '' === $status ) {
        $status = $healthy ? 'healthy' : 'unhealthy';
    }

    return array(
        'healthy' => $healthy,
        'status'  => $status,
    );
}

/**
 * Return cached/live health for one Release location.
 *
 * @param object $location Release location.
 * @param bool   $force Whether to bypass cache.
 * @return array
 */
function tnt_get_product_release_location_health( $location, $force = false ) {
    if ( ! $location || empty( $location->id ) ) {
        return array( 'healthy' => false, 'status' => 'invalid_location' );
    }

    $cache_key = 'tnt_prlh_' . absint( $location->id );
    if ( ! $force ) {
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) && isset( $cached['healthy'], $cached['status'] ) ) {
            return $cached;
        }
    }

    if ( ! tnt_get_product_provider_adapter( $location->provider ) ) {
        $health = array( 'healthy' => false, 'status' => 'provider_not_registered' );
    } elseif ( ! tnt_product_provider_is_configured( $location->provider ) ) {
        $health = array( 'healthy' => false, 'status' => 'provider_not_configured' );
    } else {
        $result = tnt_product_provider_health_check(
            $location->provider,
            $location->provider_file_id,
            array( 'location_id' => absint( $location->id ), 'release_id' => absint( $location->release_id ) )
        );
        $health = tnt_normalize_product_provider_health( $result );
    }

    $ttl = tnt_get_product_provider_health_cache_ttl( $location );
    if ( $ttl > 0 ) {
        set_transient( $cache_key, $health, $ttl );
    }

    return $health;
}

/**
 * Mark a location temporarily unhealthy after a live delivery failure.
 *
 * @param object $location Release location.
 * @param string $status Failure status/code.
 * @return void
 */
function tnt_mark_product_release_location_temporarily_unhealthy( $location, $status ) {
    if ( ! $location || empty( $location->id ) ) {
        return;
    }

    $ttl = tnt_get_product_provider_health_cache_ttl( $location );
    if ( $ttl < 1 ) {
        return;
    }

    set_transient(
        'tnt_prlh_' . absint( $location->id ),
        array(
            'healthy' => false,
            'status'  => sanitize_key( $status ?: 'delivery_failed' ),
        ),
        $ttl
    );
}

/**
 * Build a weighted random attempt order for one priority group.
 *
 * @param array $locations Eligible location rows.
 * @return array
 */
function tnt_weight_product_release_locations( $locations ) {
    $remaining = array_values( $locations );
    $ordered   = array();

    while ( ! empty( $remaining ) ) {
        $total = 0;
        foreach ( $remaining as $location ) {
            $total += max( 1, absint( $location->weight ) );
        }

        $pick    = wp_rand( 1, max( 1, $total ) );
        $running = 0;
        $chosen  = 0;

        foreach ( $remaining as $index => $location ) {
            $running += max( 1, absint( $location->weight ) );
            if ( $pick <= $running ) {
                $chosen = $index;
                break;
            }
        }

        $ordered[] = $remaining[ $chosen ];
        array_splice( $remaining, $chosen, 1 );
    }

    return $ordered;
}

/**
 * Return eligible Release locations in health-aware priority/weight order.
 *
 * @param int $release_id Release ID.
 * @return array
 */
function tnt_get_product_download_location_attempt_order( $release_id ) {
    $locations = tnt_get_product_release_locations( $release_id, true );
    if ( empty( $locations ) ) {
        return array();
    }

    $groups = array();
    foreach ( $locations as $location ) {
        if ( ! tnt_get_product_provider_adapter( $location->provider ) ) {
            continue;
        }

        $health = tnt_get_product_release_location_health( $location );
        if ( empty( $health['healthy'] ) ) {
            continue;
        }

        $priority = max( 1, absint( $location->priority ) );
        if ( ! isset( $groups[ $priority ] ) ) {
            $groups[ $priority ] = array();
        }
        $groups[ $priority ][] = $location;
    }

    if ( empty( $groups ) ) {
        return array();
    }

    ksort( $groups, SORT_NUMERIC );
    $ordered = array();
    foreach ( $groups as $group ) {
        $ordered = array_merge( $ordered, tnt_weight_product_release_locations( $group ) );
    }

    return $ordered;
}

/**
 * Resolve a provider destination with same-request failover.
 *
 * @param object $release Release row.
 * @param int    $user_id Authenticated user ID.
 * @return array|WP_Error
 */
function tnt_resolve_product_download_destination( $release, $user_id ) {
    $locations = tnt_get_product_download_location_attempt_order( $release->id );
    if ( empty( $locations ) ) {
        return new WP_Error( 'tnt_download_no_healthy_provider', __( 'Download is temporarily unavailable.', 'toolntip-core' ) );
    }

    foreach ( $locations as $location ) {
        $destination = tnt_product_provider_get_download_destination(
            $location->provider,
            $location->provider_file_id,
            array(
                'user_id'     => absint( $user_id ),
                'release_id'  => absint( $release->id ),
                'location_id' => absint( $location->id ),
            )
        );

        if ( is_wp_error( $destination ) ) {
            tnt_mark_product_release_location_temporarily_unhealthy( $location, $destination->get_error_code() );
            continue;
        }

        if ( empty( $destination['url'] ) || ! wp_http_validate_url( $destination['url'] ) ) {
            tnt_mark_product_release_location_temporarily_unhealthy( $location, 'invalid_destination' );
            continue;
        }

        return array(
            'location'    => $location,
            'destination' => $destination,
        );
    }

    return new WP_Error( 'tnt_download_provider_failed', __( 'Download is temporarily unavailable.', 'toolntip-core' ) );
}

/**
 * Send a generic download endpoint error.
 *
 * @param int    $status HTTP status.
 * @param string $message Public message.
 * @return void
 */
function tnt_product_download_error_response( $status, $message ) {
    $status = absint( $status );

    status_header( $status );
    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow', true );

    // Keep governed download failures inside the established ToolNTip public shell.
    wp_enqueue_style( 'tnt-product-public' );

    if ( 404 === $status ) {
        $title = __( 'Product Release Unavailable', 'toolntip-core' );
        $message = __( 'This product release is unavailable or no longer available for download.', 'toolntip-core' );
    } elseif ( 403 === $status ) {
        $title = __( 'Download Not Authorized', 'toolntip-core' );
    } else {
        $title = __( 'Download Temporarily Unavailable', 'toolntip-core' );
    }

    get_header();
    ?>
    <main id="primary" class="site-main tnt-product-download-error">
        <div class="tnt-product-shell">
            <section class="tnt-product-error-state" role="status" aria-labelledby="tnt-product-error-title">
                <div class="tnt-product-error-state__eyebrow"><?php esc_html_e( 'Product Download', 'toolntip-core' ); ?></div>
                <h1 id="tnt-product-error-title"><?php echo esc_html( $title ); ?></h1>
                <p><?php echo esc_html( $message ); ?></p>
                <p class="tnt-product-error-state__actions">
                    <a class="tnt-product-button" href="<?php echo esc_url( get_post_type_archive_link( 'tnt_product' ) ); ?>"><?php esc_html_e( 'Back to Products', 'toolntip-core' ); ?></a>
                </p>
            </section>
        </div>
    </main>
    <?php
    get_footer();
    exit;
}

/**
 * Handle governed Product download requests.
 *
 * @return void
 */
function tnt_handle_product_download_request() {
    if ( ! tnt_is_product_download_request() ) {
        return;
    }

    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow', true );

    $target = tnt_resolve_product_download_target(
        get_query_var( 'tnt_product_slug' ),
        get_query_var( 'tnt_edition_key' ),
        get_query_var( 'tnt_release_version' )
    );

    if ( is_wp_error( $target ) ) {
        tnt_product_download_error_response( 404, __( 'This Product Release is not available.', 'toolntip-core' ) );
    }

    if ( ! is_user_logged_in() ) {
        $current_url = tnt_get_product_release_download_url(
            $target['release'],
            'latest' === strtolower( (string) get_query_var( 'tnt_release_version' ) )
        );
        wp_safe_redirect( wp_login_url( $current_url ) );
        exit;
    }

    $user_id = get_current_user_id();
    if ( ! tnt_can_download_product_release( $user_id, $target['release'] ) ) {
        tnt_product_download_error_response( 403, __( 'You are not authorized to download this Product Release.', 'toolntip-core' ) );
    }

    $resolved = tnt_resolve_product_download_destination( $target['release'], $user_id );
    if ( is_wp_error( $resolved ) ) {
        tnt_product_download_error_response( 503, __( 'Download temporarily unavailable. Please try again later.', 'toolntip-core' ) );
    }

    $event_id = tnt_record_product_download_event(
        array(
            'user_id'      => $user_id,
            'release_id'   => absint( $target['release']->id ),
            'location_id'  => absint( $resolved['location']->id ),
            'event_status' => 'redirected',
        )
    );

    if ( is_wp_error( $event_id ) ) {
        tnt_product_download_error_response( 503, __( 'Download temporarily unavailable. Please try again later.', 'toolntip-core' ) );
    }

    /**
     * The destination is generated by a registered provider adapter after
     * authentication/authorization. It is intentionally an external redirect;
     * wp_safe_redirect() would reject legitimate provider hosts.
     */
    wp_redirect( esc_url_raw( $resolved['destination']['url'] ), 302, 'ToolNTip Core' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
    exit;
}
add_action( 'template_redirect', 'tnt_handle_product_download_request', 1 );
