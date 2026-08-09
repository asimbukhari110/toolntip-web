<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load frontend CSS and JavaScript.
 */
function tnt_enqueue_assets() {

    wp_enqueue_style(
        'toolntip-core-style',
        TNT_CORE_URL . 'assets/css/frontend.css',
        array(),
        TNT_CORE_VERSION
    );
	
	wp_enqueue_style(

		'tnt-rating',

		TNT_CORE_URL.'assets/css/rating.css',

		array(),

		TNT_CORE_VERSION

	);
	
	wp_enqueue_style(

		'tnt-rating',

		TNT_CORE_URL.'assets/css/about.css',

		array(),

		TNT_CORE_VERSION

	);
	
	wp_enqueue_style(

		'tnt-rating',

		TNT_CORE_URL.'assets/css/screenshots.css',

		array(),

		TNT_CORE_VERSION

	);
	
	wp_enqueue_style(

		'tnt-rating',

		TNT_CORE_URL.'assets/css/pros-cons.css',

		array(),

		TNT_CORE_VERSION

	);



    wp_enqueue_script(
        'toolntip-core-script',
        TNT_CORE_URL . 'assets/js/frontend.js',
        array(),
        TNT_CORE_VERSION,
        true
    );
	
	
}

add_action( 'wp_enqueue_scripts', 'tnt_enqueue_assets' );