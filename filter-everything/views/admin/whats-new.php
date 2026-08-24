<?php
/**
 * «What's new» admin page — release notes of the installed version, parsed from
 * the bundled readme.txt by WhatsNew::changelog().
 *
 * @var string      $version       version whose notes are shown (e.g. 1.9.6)
 * @var array|null  $current       ['date' => string, 'items' => [...]] or null when the readme has no changelog
 * @var array       $earlier       version => release, older entries from the bundled readme
 * @var string      $changelog_url full changelog on filtereverything.pro
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

$flrt_render_items = static function ( array $items ) {
    foreach ( $items as $item ) {
        echo '<li>';
        if ( ! empty( $item['new'] ) ) {
            echo '<span class="wpc-whats-new-tag wpc-whats-new-tag-new">' . esc_html__( 'New', 'filter-everything' ) . '</span>';
        } elseif ( ! empty( $item['label'] ) ) {
            echo '<span class="wpc-whats-new-tag">' . esc_html( $item['label'] ) . '</span>';
        }
        echo esc_html( $item['text'] );
        echo '</li>';
    }
};
?>
<div class="wrap wpc-whats-new">
    <h1><?php
        /* translators: %s: plugin name with version, e.g. "Filter Everything 1.9.6" */
        printf( esc_html__( "What's new in %s", 'filter-everything' ), esc_html( 'Filter Everything ' . $version ) );
    ?></h1>

    <?php if ( $current && ! empty( $current['items'] ) ) : ?>
        <?php if ( ! empty( $current['date'] ) ) : ?>
            <p class="wpc-whats-new-date"><?php
                /* translators: %s: release date as written in the changelog */
                printf( esc_html__( 'Release date: %s', 'filter-everything' ), esc_html( $current['date'] ) );
            ?></p>
        <?php endif; ?>
        <ul class="wpc-whats-new-list">
            <?php $flrt_render_items( $current['items'] ); ?>
        </ul>
    <?php else : ?>
        <p class="description"><?php esc_html_e( 'No release notes were found for this version.', 'filter-everything' ); ?></p>
    <?php endif; ?>

    <p>
        <a class="button button-secondary" href="<?php echo esc_url( add_query_arg( [ 'utm_source' => 'plugin', 'utm_medium' => 'whats_new', 'utm_campaign' => 'release_' . $version ], $changelog_url ) ); ?>" target="_blank" rel="noopener">
            <?php esc_html_e( 'Full changelog', 'filter-everything' ); ?> &#8599;
        </a>
    </p>

    <?php if ( ! empty( $earlier ) ) : ?>
        <details class="wpc-whats-new-earlier">
            <summary><?php esc_html_e( 'Earlier releases', 'filter-everything' ); ?></summary>
            <?php foreach ( $earlier as $ver => $release ) : ?>
                <?php if ( empty( $release['items'] ) ) { continue; } ?>
                <h3><?php echo esc_html( $ver ); ?><?php if ( ! empty( $release['date'] ) ) : ?> <small><?php echo esc_html( $release['date'] ); ?></small><?php endif; ?></h3>
                <ul>
                    <?php $flrt_render_items( $release['items'] ); ?>
                </ul>
            <?php endforeach; ?>
        </details>
    <?php endif; ?>
</div>
