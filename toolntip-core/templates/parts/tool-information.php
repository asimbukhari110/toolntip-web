<?php
/**
 * Tool Information.
 *
 * Displays normalized Tool metadata in the Tool Detail sidebar.
 *
 * @package ToolntipCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = ! empty( $tool['post_id'] )
    ? (int) $tool['post_id']
    : 0;

$platform_label = ! empty( $tool['platform'] )
    ? implode( ', ', $tool['platform'] )
    : '';

$category_names = array();

if ( ! empty( $tool['categories'] ) ) {

    foreach ( $tool['categories'] as $category ) {

        if ( ! empty( $category['name'] ) ) {
            $category_names[] = $category['name'];
        }

    }

}

$category_label = ! empty( $category_names )
    ? implode( ', ', $category_names )
    : '';

$permalink = $post_id > 0
    ? get_permalink( $post_id )
    : '';

$encoded_permalink = rawurlencode( $permalink );
$encoded_title     = rawurlencode( $tool['title'] );
?>

<section class="tnt-tool-information">

    <header class="tnt-tool-information__header">

        <span
            class="tnt-tool-information__icon"
            aria-hidden="true"
        >
            i
        </span>

        <h2 class="tnt-tool-information__title">
            <?php echo esc_html__( 'Tool Information', 'toolntip-core' ); ?>
        </h2>

    </header>

    <dl class="tnt-tool-information__list">

        <?php if ( ! empty( $tool['developer'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt><?php echo esc_html__( 'Developer', 'toolntip-core' ); ?></dt>
                <dd><?php echo esc_html( $tool['developer'] ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tool['pricing'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt><?php echo esc_html__( 'Pricing', 'toolntip-core' ); ?></dt>
                <dd><?php echo esc_html( $tool['pricing'] ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $platform_label ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt><?php echo esc_html__( 'Platform', 'toolntip-core' ); ?></dt>
                <dd><?php echo esc_html( $platform_label ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tool['tool_type'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt><?php echo esc_html__( 'Tool Type', 'toolntip-core' ); ?></dt>
                <dd><?php echo esc_html( $tool['tool_type'] ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $category_label ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt><?php echo esc_html__( 'Category', 'toolntip-core' ); ?></dt>
                <dd><?php echo esc_html( $category_label ); ?></dd>
            </div>

        <?php endif; ?>

        <?php if ( ! empty( $tool['last_verified'] ) ) : ?>

            <div class="tnt-tool-information__row">
                <dt><?php echo esc_html__( 'Last Verified', 'toolntip-core' ); ?></dt>
                <dd><?php echo esc_html( $tool['last_verified'] ); ?></dd>
            </div>

        <?php endif; ?>

    </dl>

    <?php if ( ! empty( $tool['tags'] ) ) : ?>

        <div class="tnt-tool-information__taxonomy">

            <span class="tnt-tool-information__meta-label">
                <?php echo esc_html__( 'Tags', 'toolntip-core' ); ?>
            </span>

            <div class="tnt-tool-information__tags">

                <?php foreach ( $tool['tags'] as $tag ) : ?>

                    <?php if ( ! empty( $tag['name'] ) ) : ?>

                        <span class="tnt-tool-information__tag">
                            <?php echo esc_html( $tag['name'] ); ?>
                        </span>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endif; ?>

    <?php if ( ! empty( $permalink ) ) : ?>

        <div class="tnt-tool-information__share">

            <span class="tnt-tool-information__meta-label">
                <?php echo esc_html__( 'Share', 'toolntip-core' ); ?>
            </span>

            <div class="tnt-tool-information__share-actions">

                <a
                    href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_permalink ); ?>"
                    class="tnt-tool-information__share-link"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__( 'Share on Facebook', 'toolntip-core' ); ?>"
                >
                    f
                </a>

                <a
                    href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . $encoded_permalink . '&text=' . $encoded_title ); ?>"
                    class="tnt-tool-information__share-link"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__( 'Share on X', 'toolntip-core' ); ?>"
                >
                    X
                </a>

                <a
                    href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_permalink ); ?>"
                    class="tnt-tool-information__share-link"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__( 'Share on LinkedIn', 'toolntip-core' ); ?>"
                >
                    in
                </a>

                <a
                    href="<?php echo esc_url( $permalink ); ?>"
                    class="tnt-tool-information__share-link"
                    aria-label="<?php echo esc_attr__( 'Tool permalink', 'toolntip-core' ); ?>"
                >
                    &#8599;
                </a>

            </div>

        </div>

    <?php endif; ?>

</section>
