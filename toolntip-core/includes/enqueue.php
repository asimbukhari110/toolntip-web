<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load ToolNTip Core frontend assets.
 */
function tnt_enqueue_assets() {

    wp_enqueue_style(
        'toolntip-core',
        TNT_CORE_URL . 'assets/css/frontend.css',
        array(),
        TNT_CORE_VERSION
    );

	wp_enqueue_style(
		'tnt-tool-card',
		TNT_CORE_URL . 'assets/css/tool-card.css',
		array( 'toolntip-core' ),
		TNT_CORE_VERSION
	);

	wp_register_style(
		'tnt-resource-card',
		TNT_CORE_URL . 'assets/css/resource-card.css',
		array( 'toolntip-core' ),
		TNT_CORE_VERSION
	);

	wp_register_style(
		'tnt-resource-hub',
		TNT_CORE_URL . 'assets/css/resource-hub.css',
		array( 'toolntip-core', 'tnt-resource-card' ),
		TNT_CORE_VERSION
	);

    wp_enqueue_style(
        'tnt-rating',
        TNT_CORE_URL . 'assets/css/rating.css',
        array( 'toolntip-core' ),
        TNT_CORE_VERSION
    );

    wp_enqueue_style(
        'tnt-about',
        TNT_CORE_URL . 'assets/css/about.css',
        array( 'toolntip-core' ),
        TNT_CORE_VERSION
    );

    wp_enqueue_style(
        'tnt-screenshots',
        TNT_CORE_URL . 'assets/css/screenshots.css',
        array( 'toolntip-core' ),
        TNT_CORE_VERSION
    );

    wp_enqueue_style(
        'tnt-pros-cons',
        TNT_CORE_URL . 'assets/css/pros-cons.css',
        array( 'toolntip-core' ),
        TNT_CORE_VERSION
    );

	wp_enqueue_style(
    'tnt-tool-detail',
    TNT_CORE_URL . 'assets/css/tool-detail.css',
    array(
        'toolntip-core',
        'tnt-tool-card',
        'tnt-rating',
        'tnt-about',
        'tnt-screenshots',
        'tnt-pros-cons',
    ),
		TNT_CORE_VERSION
	);

	wp_enqueue_style(
		'tnt-reviews',
		TNT_CORE_URL . 'assets/css/reviews.css',
		array( 'tnt-tool-detail' ),
		TNT_CORE_VERSION
	);
    wp_enqueue_style(
        'tnt-monetization-page-level',
        TNT_CORE_URL . 'assets/css/monetization-page-level.css',
        array( 'tnt-tool-detail' ),
        TNT_CORE_VERSION
    );

    if ( is_post_type_archive( 'tool' ) ) {

		wp_enqueue_style(
			'tnt-tool-directory',
			TNT_CORE_URL . 'assets/css/tool-directory.css',
			array( 'toolntip-core', 'tnt-tool-card' ),
			TNT_CORE_VERSION
		);
	}

    if (
        is_post_type_archive( 'resource' )
        || is_tax( 'resource_type' )
        || ( is_tax( 'tool_category' ) && 'resource' === get_query_var( 'post_type' ) )
    ) {
        wp_enqueue_style( 'tnt-resource-card' );
        wp_enqueue_style( 'tnt-resource-hub' );
    }
}

add_action( 'wp_enqueue_scripts', 'tnt_enqueue_assets' );
