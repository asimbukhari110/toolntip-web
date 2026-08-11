<?php
/**
 * Promotional Placement Helper.
 *
 * Provides provider-independent promotional placement data so templates remain
 * decoupled from future sponsorship, affiliate, or advertising integrations.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get a normalized promotional placement.
 *
 * Current rollout uses a first-party house promotion only. Future providers
 * can be resolved here without changing Hero presentation markup.
 *
 * Intended provider priority:
 * 1. Direct sponsor.
 * 2. Affiliate promotion.
 * 3. First-party campaign.
 * 4. House promotion.
 * 5. Empty placement.
 *
 * @param string        $placement Placement identifier.
 * @param array|WP_Post $tool      Current Tool data or post.
 *
 * @return array
 */
function tnt_get_promo_placement( $placement, $tool = array() ) {

    if ( 'tool-detail-hero' !== $placement ) {
        return array();
    }

    return array(
        'type'        => 'house',
        'label'       => __( 'Featured', 'toolntip-core' ),
        'eyebrow'     => __( 'ToolNTip', 'toolntip-core' ),
        'title'       => __( 'Discover More Developer Tools', 'toolntip-core' ),
        'description' => __( 'Explore curated tools to improve your development workflow.', 'toolntip-core' ),
        'cta_label'   => __( 'Explore Tools', 'toolntip-core' ),
        'url'         => home_url( '/tools/' ),
        'external'    => false,
    );
}
