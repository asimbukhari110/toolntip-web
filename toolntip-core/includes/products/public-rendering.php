<?php
/**
 * ToolNTip Product public presentation helpers.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return a human-readable Product status label.
 *
 * @param string $status Product status key.
 * @return string
 */
function tnt_get_product_status_label( $status ) {
    $statuses = tnt_get_product_statuses();
    return isset( $statuses[ $status ] ) ? $statuses[ $status ] : ucfirst( (string) $status );
}

/**
 * Return a human-readable Edition status label.
 *
 * @param string $status Edition status key.
 * @return string
 */
function tnt_get_product_edition_status_label( $status ) {
    $labels = array(
        'planned'      => __( 'Planned', 'toolntip-core' ),
        'available'    => __( 'Available', 'toolntip-core' ),
        'discontinued' => __( 'Discontinued', 'toolntip-core' ),
    );

    return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( (string) $status );
}

/**
 * Normalize one public Product release.
 *
 * @param object $release Release row.
 * @param object $edition Edition row.
 * @return array<string,mixed>
 */
function tnt_get_public_product_release_view( $release, $edition ) {
    if ( ! $release || ! $edition || 'published' !== (string) $release->status ) {
        return array();
    }

    $filename = (string) $release->artifact_filename;
    $ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    $url      = tnt_get_product_release_download_url( $release );

    return array(
        'id'                      => absint( $release->id ),
        'edition_id'              => absint( $edition->id ),
        'edition_key'             => (string) $edition->edition_key,
        'edition_name'            => (string) $edition->name,
        'version'                 => (string) $release->version,
        'release_date'            => (string) $release->release_date,
        'platform'                => (string) $release->platform,
        'architecture'            => (string) $release->architecture,
        'artifact_filename'       => $filename,
        'artifact_type'           => $ext ? strtoupper( $ext ) : '',
        'artifact_size'           => isset( $release->artifact_size ) ? absint( $release->artifact_size ) : 0,
        'artifact_size_display'   => ! empty( $release->artifact_size ) ? size_format( absint( $release->artifact_size ), 2 ) : '',
        'artifact_sha256'         => strtolower( (string) $release->artifact_sha256 ),
        'release_notes'           => (string) $release->release_notes,
        'system_requirements'     => (string) $release->system_requirements,
        'license_reference'       => (string) $release->license_reference,
        'documentation_reference' => (string) $release->documentation_reference,
        'download_url'            => $url,
        'download_label'          => $ext ? sprintf( __( 'Download %s', 'toolntip-core' ), strtoupper( $ext ) ) : __( 'Download', 'toolntip-core' ),
    );
}

/**
 * Build the normalized Product public view model.
 *
 * @param WP_Post|int $product Product post or ID.
 * @return array<string,mixed>|null
 */
function tnt_get_product_public_view( $product ) {
    if ( ! $product instanceof WP_Post ) {
        $product = get_post( absint( $product ) );
    }

    if ( ! $product || 'tnt_product' !== $product->post_type ) {
        return null;
    }

    $product_id = absint( $product->ID );
    $status     = tnt_get_product_status( $product );
    $facts      = array(
        'positioning'        => trim( (string) get_post_meta( $product_id, '_tnt_product_positioning', true ) ),
        'category'           => trim( (string) get_post_meta( $product_id, '_tnt_product_category', true ) ),
        'deployment'         => trim( (string) get_post_meta( $product_id, '_tnt_product_deployment', true ) ),
        'technology'         => trim( (string) get_post_meta( $product_id, '_tnt_product_technology', true ) ),
        'supported_platform' => trim( (string) get_post_meta( $product_id, '_tnt_product_supported_platform', true ) ),
        'system_requirements'=> trim( (string) get_post_meta( $product_id, '_tnt_product_system_requirements', true ) ),
    );

    $editions      = array();
    $public_releases = array();

    if ( tnt_product_platform_ready() ) {
        foreach ( tnt_get_product_editions( $product_id, true ) as $edition ) {
            $releases = array();
            foreach ( tnt_get_edition_releases( $edition->id, 'published' ) as $release ) {
                $release_view = tnt_get_public_product_release_view( $release, $edition );
                if ( $release_view ) {
                    $releases[]       = $release_view;
                    $public_releases[] = $release_view;
                }
            }

            $editions[] = array(
                'id'                => absint( $edition->id ),
                'key'               => (string) $edition->edition_key,
                'name'              => (string) $edition->name,
                'status'            => (string) $edition->status,
                'status_label'      => tnt_get_product_edition_status_label( $edition->status ),
                'description'       => (string) $edition->short_description,
                'pricing_label'     => (string) $edition->pricing_label,
                'published_releases'=> $releases,
                'latest_release'    => ! empty( $releases ) ? $releases[0] : array(),
            );
        }
    }

    $resource_ids = tnt_get_product_resource_ids( $product_id, true, 6 );
    $resources    = array();
    foreach ( $resource_ids as $resource_id ) {
        if ( function_exists( 'tnt_get_resource_card_data' ) ) {
            $data = tnt_get_resource_card_data( $resource_id );
            if ( $data ) {
                $resources[] = $data;
            }
        }
    }

    $tools      = tnt_get_tools_for_product( $product_id, true, 6 );
    $tool_cards = array();

    // Public Product supporting content must never expose non-published Tools.
    if ( function_exists( 'tnt_get_tool_card_data' ) ) {
        foreach ( $tools as $tool ) {
            $tool_card = tnt_get_tool_card_data( $tool );
            if ( $tool_card ) {
                $tool_cards[] = $tool_card;
            }
        }
    }

    $thumbnail_id = get_post_thumbnail_id( $product_id );

    return array(
        'id'               => $product_id,
        'post'             => $product,
        'name'             => get_the_title( $product ),
        'permalink'        => get_permalink( $product ),
        'excerpt'          => get_the_excerpt( $product ),
        'content'          => apply_filters( 'the_content', $product->post_content ),
        'featured_image'   => $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '',
        'featured_image_id'=> $thumbnail_id,
        'status'           => $status,
        'status_label'     => tnt_get_product_status_label( $status ),
        'facts'            => $facts,
        'capabilities'     => tnt_get_product_capabilities( $product_id ),
        'editions'         => $editions,
        'public_releases'  => $public_releases,
        'resources'        => $resources,
        'tools'            => $tools,
        'tool_cards'       => $tool_cards,
        'tool'             => ! empty( $tools ) ? $tools[0] : null,
        'tool_card'        => ! empty( $tool_cards ) ? $tool_cards[0] : array(),
    );
}

/**
 * Render one canonical Product card.
 *
 * @param WP_Post|int $product Product.
 * @return string
 */
function tnt_render_product_card( $product ) {
    $view = tnt_get_product_public_view( $product );
    if ( ! $view ) {
        return '';
    }

    $edition_summary = '';
    if ( ! empty( $view['editions'] ) ) {
        $edition = $view['editions'][0];
        $parts   = array_filter( array( $edition['status_label'], $edition['pricing_label'] ) );
        $edition_summary = trim( $edition['name'] . ( $parts ? ' — ' . implode( ' • ', $parts ) : '' ) );
    }

    ob_start();
    ?>
    <article class="tnt-product-card tnt-product-status--<?php echo esc_attr( $view['status'] ); ?>">
        <div class="tnt-product-card__body">
            <div class="tnt-product-card__header<?php echo $view['featured_image_id'] ? ' tnt-product-card__header--with-visual' : ''; ?>">
                <?php if ( $view['featured_image_id'] ) : ?>
                    <a class="tnt-product-card__visual" href="<?php echo esc_url( $view['permalink'] ); ?>" aria-hidden="true" tabindex="-1">
                        <?php echo wp_get_attachment_image( $view['featured_image_id'], 'thumbnail', false, array( 'loading' => 'lazy' ) ); ?>
                    </a>
                <?php endif; ?>
                <div class="tnt-product-card__identity">
                    <?php if ( $view['facts']['category'] ) : ?>
                        <div class="tnt-product-card__category"><?php echo esc_html( $view['facts']['category'] ); ?></div>
                    <?php endif; ?>
                    <h2 class="tnt-product-card__title"><a href="<?php echo esc_url( $view['permalink'] ); ?>"><?php echo esc_html( $view['name'] ); ?></a></h2>
                </div>
            </div>
            <?php if ( $view['facts']['positioning'] ) : ?>
                <p class="tnt-product-card__positioning"><?php echo esc_html( $view['facts']['positioning'] ); ?></p>
            <?php elseif ( $view['excerpt'] ) : ?>
                <p class="tnt-product-card__positioning"><?php echo esc_html( $view['excerpt'] ); ?></p>
            <?php endif; ?>
            <div class="tnt-product-card__meta">
                <span class="tnt-product-badge tnt-product-status--<?php echo esc_attr( $view['status'] ); ?>"><?php echo esc_html( $view['status_label'] ); ?></span>
                <?php if ( $edition_summary ) : ?><span class="tnt-product-card__edition"><?php echo esc_html( $edition_summary ); ?></span><?php endif; ?>
            </div>
            <div class="tnt-product-card__actions"><a class="tnt-product-button" href="<?php echo esc_url( $view['permalink'] ); ?>"><?php esc_html_e( 'View Product', 'toolntip-core' ); ?></a></div>
        </div>
    </article>
    <?php
    return trim( ob_get_clean() );
}

/**
 * Render Product cards from a Product query.
 *
 * @param array<string,mixed> $args Query arguments.
 * @return string
 */
function tnt_render_product_collection( $args = array() ) {
    $args = wp_parse_args(
        $args,
        array(
            'post_type'           => 'tnt_product',
            'post_status'         => 'publish',
            'posts_per_page'      => -1,
            'orderby'             => 'menu_order title',
            'order'               => 'ASC',
            'ignore_sticky_posts' => true,
        )
    );

    $query = new WP_Query( $args );
    ob_start();
    if ( $query->have_posts() ) {
        echo '<div class="tnt-product-grid">';
        foreach ( $query->posts as $product ) {
            echo tnt_render_product_card( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
    } else {
        echo '<div class="tnt-product-empty"><p>' . esc_html__( 'ToolNTip products will appear here as they become available.', 'toolntip-core' ) . '</p></div>';
    }
    wp_reset_postdata();
    return trim( ob_get_clean() );
}

/**
 * Build login-aware download action data.
 *
 * @param array<string,mixed> $release Release view.
 * @return array<string,string>
 */
function tnt_get_product_release_public_action( $release ) {
    $download_url = isset( $release['download_url'] ) ? (string) $release['download_url'] : '';
    if ( ! $download_url ) {
        return array();
    }

    if ( is_user_logged_in() ) {
        return array(
            'url'   => $download_url,
            'label' => isset( $release['download_label'] ) ? (string) $release['download_label'] : __( 'Download', 'toolntip-core' ),
        );
    }

    return array(
        'url'   => wp_login_url( $download_url ),
        'label' => __( 'Sign In to Download', 'toolntip-core' ),
    );
}

/**
 * Render the complete single Product composition.
 *
 * @param WP_Post|int $product Product.
 * @return string
 */
function tnt_render_product_detail( $product ) {
    $view = tnt_get_product_public_view( $product );
    if ( ! $view ) {
        return '';
    }

    $facts = array_filter(
        array(
            __( 'Deployment', 'toolntip-core' )         => $view['facts']['deployment'],
            __( 'Technology', 'toolntip-core' )         => $view['facts']['technology'],
            __( 'Supported Platform', 'toolntip-core' ) => $view['facts']['supported_platform'],
        )
    );

    ob_start();
    ?>
    <article class="tnt-product-detail tnt-product-status--<?php echo esc_attr( $view['status'] ); ?>">
        <header class="tnt-product-hero<?php echo $view['featured_image_id'] ? ' tnt-product-hero--with-visual' : ''; ?>">
            <div class="tnt-product-hero__content">
                <?php if ( $view['facts']['category'] ) : ?><div class="tnt-product-eyebrow"><?php echo esc_html( $view['facts']['category'] ); ?></div><?php endif; ?>
                <h1><?php echo esc_html( $view['name'] ); ?></h1>
                <span class="tnt-product-badge tnt-product-status--<?php echo esc_attr( $view['status'] ); ?>"><?php echo esc_html( $view['status_label'] ); ?></span>
                <?php if ( $view['facts']['positioning'] ) : ?><p class="tnt-product-lead"><?php echo esc_html( $view['facts']['positioning'] ); ?></p><?php elseif ( $view['excerpt'] ) : ?><p class="tnt-product-lead"><?php echo esc_html( $view['excerpt'] ); ?></p><?php endif; ?>
                <?php if ( ! empty( $view['public_releases'] ) ) : ?><p><a class="tnt-product-button" href="#download"><?php esc_html_e( 'View Downloads', 'toolntip-core' ); ?></a></p><?php endif; ?>
            </div>
            <?php if ( $view['featured_image_id'] ) : ?><div class="tnt-product-hero__visual"><?php echo wp_get_attachment_image( $view['featured_image_id'], 'large' ); ?></div><?php endif; ?>
        </header>

        <?php if ( trim( wp_strip_all_tags( $view['content'] ) ) ) : ?>
            <section class="tnt-product-section" id="overview"><h2><?php esc_html_e( 'Overview', 'toolntip-core' ); ?></h2><div class="tnt-product-prose"><?php echo $view['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></section>
        <?php endif; ?>

        <?php if ( $view['capabilities'] ) : ?>
            <section class="tnt-product-section" id="capabilities"><h2><?php esc_html_e( 'Key Capabilities', 'toolntip-core' ); ?></h2><div class="tnt-product-capability-grid">
            <?php foreach ( $view['capabilities'] as $capability ) : ?><article class="tnt-product-capability"><?php if ( ! empty( $capability['icon'] ) ) : ?><?php echo tnt_render_product_capability_icon( $capability['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?><h3><?php echo esc_html( $capability['title'] ); ?></h3><?php if ( $capability['description'] ) : ?><p><?php echo esc_html( $capability['description'] ); ?></p><?php endif; ?></article><?php endforeach; ?>
            </div></section>
        <?php endif; ?>

        <?php if ( $facts || $view['facts']['system_requirements'] ) : ?>
            <section class="tnt-product-section" id="platform"><h2><?php esc_html_e( 'Platform & Deployment', 'toolntip-core' ); ?></h2>
                <?php if ( $facts ) : ?><dl class="tnt-product-facts"><?php foreach ( $facts as $label => $value ) : ?><div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
                <?php if ( $view['facts']['system_requirements'] ) : ?><div class="tnt-product-requirements"><h3><?php esc_html_e( 'System Requirements', 'toolntip-core' ); ?></h3><p><?php echo nl2br( esc_html( $view['facts']['system_requirements'] ) ); ?></p></div><?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ( $view['editions'] ) : ?>
            <section class="tnt-product-section" id="editions"><h2><?php esc_html_e( 'Editions & Availability', 'toolntip-core' ); ?></h2><div class="tnt-product-edition-grid<?php echo 1 === count( $view['editions'] ) ? ' tnt-product-edition-grid--single' : ''; ?>">
                <?php foreach ( $view['editions'] as $edition ) : ?><article class="tnt-product-edition tnt-edition-status--<?php echo esc_attr( $edition['status'] ); ?>"><div class="tnt-product-edition__meta"><span class="tnt-product-badge"><?php echo esc_html( $edition['status_label'] ); ?></span><?php if ( $edition['pricing_label'] ) : ?><span class="tnt-product-badge tnt-product-badge--neutral"><?php echo esc_html( $edition['pricing_label'] ); ?></span><?php endif; ?></div><div class="tnt-product-edition__heading"><h3><?php echo esc_html( $edition['name'] ); ?></h3></div><?php if ( $edition['description'] ) : ?><p class="tnt-product-edition__description"><?php echo esc_html( $edition['description'] ); ?></p><?php endif; ?><?php if ( $edition['latest_release'] ) : ?><p class="tnt-product-edition__release"><?php echo esc_html( sprintf( __( 'Latest release: %s', 'toolntip-core' ), $edition['latest_release']['version'] ) ); ?></p><?php endif; ?></article><?php endforeach; ?>
            </div></section>
        <?php endif; ?>

        <?php if ( $view['public_releases'] ) : ?>
            <section class="tnt-product-section" id="download"><h2><?php esc_html_e( 'Release & Download', 'toolntip-core' ); ?></h2><div class="tnt-product-release-list">
                <?php foreach ( $view['public_releases'] as $release ) : $action = tnt_get_product_release_public_action( $release ); ?>
                    <article class="tnt-product-release">
                        <div class="tnt-product-release__heading"><h3><?php echo esc_html( $release['edition_name'] . ' ' . $release['version'] ); ?></h3><?php if ( $release['release_date'] ) : ?><time datetime="<?php echo esc_attr( $release['release_date'] ); ?>"><?php echo esc_html( $release['release_date'] ); ?></time><?php endif; ?></div>
                        <dl class="tnt-product-release__facts">
                            <?php foreach ( array( __( 'Platform', 'toolntip-core' ) => $release['platform'], __( 'Architecture', 'toolntip-core' ) => $release['architecture'], __( 'Package', 'toolntip-core' ) => $release['artifact_type'], __( 'Filename', 'toolntip-core' ) => $release['artifact_filename'], __( 'File size', 'toolntip-core' ) => $release['artifact_size_display'] ) as $label => $value ) : if ( ! $value ) { continue; } ?><div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?>
                        </dl>
                        <?php if ( $release['artifact_sha256'] ) : ?><div class="tnt-product-checksum"><strong><?php esc_html_e( 'SHA-256', 'toolntip-core' ); ?></strong><code><?php echo esc_html( $release['artifact_sha256'] ); ?></code><button type="button" class="tnt-product-copy" data-copy="<?php echo esc_attr( $release['artifact_sha256'] ); ?>"><?php esc_html_e( 'Copy', 'toolntip-core' ); ?></button></div><?php endif; ?>
                        <?php if ( $release['release_notes'] ) : ?><div class="tnt-product-release__notes"><h4><?php esc_html_e( 'Release Notes', 'toolntip-core' ); ?></h4><?php echo wp_kses_post( wpautop( $release['release_notes'] ) ); ?></div><?php endif; ?>
                        <?php if ( $release['system_requirements'] ) : ?><div><h4><?php esc_html_e( 'Release Requirements', 'toolntip-core' ); ?></h4><?php echo wp_kses_post( wpautop( $release['system_requirements'] ) ); ?></div><?php endif; ?>
                        <?php if ( $release['license_reference'] ) : ?><p><strong><?php esc_html_e( 'License:', 'toolntip-core' ); ?></strong> <?php echo esc_html( $release['license_reference'] ); ?></p><?php endif; ?>
                        <?php if ( $release['documentation_reference'] ) : ?><p><a href="<?php echo esc_url( $release['documentation_reference'] ); ?>"><?php esc_html_e( 'Documentation', 'toolntip-core' ); ?></a></p><?php endif; ?>
                        <?php if ( $action ) : ?><p><a class="tnt-product-button" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div></section>
        <?php endif; ?>

        <?php
        $tool_cards       = ! empty( $view['tool_cards'] ) && is_array( $view['tool_cards'] ) ? $view['tool_cards'] : array();
        $supporting_count = count( $view['resources'] ) + count( $tool_cards );
        if ( $supporting_count ) :
            $supporting_heading = ! empty( $tool_cards ) && ! empty( $view['resources'] )
                ? __( 'Resources & Tools', 'toolntip-core' )
                : ( ! empty( $view['resources'] ) ? __( 'Resources', 'toolntip-core' ) : __( 'Related Tools', 'toolntip-core' ) );
            ?>
            <section class="tnt-product-section tnt-product-supporting" id="resources">
                <h2><?php echo esc_html( $supporting_heading ); ?></h2>
                <div class="tnt-product-supporting-grid tnt-product-supporting-grid--count-<?php echo esc_attr( min( 3, $supporting_count ) ); ?>">
                    <?php foreach ( $tool_cards as $tool_card ) : ?>
                        <?php tnt_render( 'tool-card', $tool_card ); ?>
                    <?php endforeach; ?>
                    <?php foreach ( $view['resources'] as $resource ) : ?>
                        <?php tnt_render( 'resource-card', $resource ); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </article>
    <?php
    return trim( ob_get_clean() );
}
