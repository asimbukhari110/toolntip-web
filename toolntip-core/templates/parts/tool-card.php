<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<article class="tnt-tool-card">

    <div class="tnt-tool-header">

        <?php include TNT_CORE_PATH . 'templates/parts/logo.php'; ?>

        <div class="tnt-tool-content">

            <?php include TNT_CORE_PATH . 'templates/parts/title.php'; ?>
			
			<?php include TNT_CORE_PATH . 'templates/parts/hero-meta.php'; ?>

            <?php include TNT_CORE_PATH . 'templates/parts/excerpt.php'; ?>

        </div>

    </div>

    <?php include TNT_CORE_PATH . 'templates/parts/badges.php'; ?>

    <?php include TNT_CORE_PATH . 'templates/parts/buttons.php'; ?>
	
	<?php tnt_render( 'gallery', $tool ); ?>

    <?php include TNT_CORE_PATH . 'templates/parts/features.php'; ?>
	
	<?php include TNT_CORE_PATH . 'templates/parts/pros-cons.php'; ?>
	
	<?php include TNT_CORE_PATH . 'templates/parts/screenshots.php'; ?>
	
	<?php include TNT_CORE_PATH . 'templates/parts/video.php'; ?>
	
	<?php include TNT_CORE_PATH . 'templates/parts/faq.php'; ?>
	
	<?php include TNT_CORE_PATH . 'templates/parts/schema.php'; ?>

</article>