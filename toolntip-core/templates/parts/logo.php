<?php
/**
 * Tool Logo
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $tool['logo']['url'] ) ) {
    return;
}
?>

<div class="tnt-tool-logo">

    <img
        src="<?php echo esc_url( $tool['logo']['url'] ); ?>"
        alt="<?php echo esc_attr( $tool['logo']['alt'] ); ?>"
        width="<?php echo esc_attr( $tool['logo']['width'] ); ?>"
        height="<?php echo esc_attr( $tool['logo']['height'] ); ?>">

</div>