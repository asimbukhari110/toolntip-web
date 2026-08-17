<?php
/**
 * Tool Hero Identity Component.
 *
 * Canonical native Tool Detail Hero identity presentation.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$identity_tool = $tool;
$identity_tool['_tnt_include_hero_actions'] = true;
?>

<div class="tnt-tool-hero__identity">
    <?php tnt_render( 'hero-identity-content', $identity_tool ); ?>
</div>
