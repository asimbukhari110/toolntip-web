<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [tnt_hello]
 */
function tnt_hello_shortcode() {

    return '
        <div style="
            padding:20px;
            background:#f5f5f5;
            border-left:5px solid #0d6efd;
            border-radius:6px;
            margin:20px 0;
        ">
            <h3>Hello from Toolntip Core 🚀</h3>
            <p>Your first shortcode is working successfully.</p>
        </div>
    ';
}

add_shortcode( 'tnt_hello', 'tnt_hello_shortcode' );