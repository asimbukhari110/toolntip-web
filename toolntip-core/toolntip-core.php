<?php
/**
 * Plugin Name: Toolntip Core
 * Plugin URI: https://toolntip.com
 * Description: Core functionality for the Toolntip platform.
 * Version: 1.0.11
 * Author: Syed Asim Raza
 * Author URI: https://toolntip.com
 * License: GPL v2 or later
 * Text Domain: toolntip-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin Constants
 */

define( 'TNT_CORE_VERSION', '1.0.11' );
define( 'TNT_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'TNT_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include Plugin Files
 */
require_once TNT_CORE_PATH . 'includes/load.php';

require_once TNT_CORE_PATH . 'shortcodes/load.php';
