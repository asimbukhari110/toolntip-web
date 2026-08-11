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

	if ( is_post_type_archive( 'tool' ) ) {

		wp_enqueue_style(
			'tnt-tool-directory',
			TNT_CORE_URL . 'assets/css/tool-directory.css',
			array( 'toolntip-core', 'tnt-tool-card' ),
			TNT_CORE_VERSION
		);
	}
}

add_action( 'wp_enqueue_scripts', 'tnt_enqueue_assets' );
