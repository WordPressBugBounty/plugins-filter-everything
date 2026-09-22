<?php
/**
 * Review request notice (free build only) — see src/Admin/ReviewRequest.php.
 *
 * Rendered on admin_notices on any admin screen, so it carries its own small
 * stylesheet and script: the plugin's admin assets are not loaded there.
 * One question, five stars, one button, two quiet ways out and a support link
 * for the people who are here because something is wrong.
 *
 * @var string $review_nonce
 */
use FilterEverything\Filter\ReviewRequest;

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="notice flrt-review-notice wp-exclude-emoji" id="flrt-review-notice" data-nonce="<?php echo esc_attr($review_nonce); ?>">
    <div class="flrt-review-notice__stars" aria-hidden="true">
        <?php for ($flrt_star = 0; $flrt_star < 5; $flrt_star++) : ?><a class="flrt-review-notice__star" href="<?php echo esc_url(ReviewRequest::REVIEW_URL); ?>" target="_blank" rel="noopener" data-flrt-review="rated">&#9733;</a><?php endfor; ?>
    </div>
    <div class="flrt-review-notice__body">
        <strong class="flrt-review-notice__title"><?php echo esc_html__('Enjoying', 'filter-everything') . ' ' . esc_html__('Filter Everything?', 'filter-everything'); ?> 🤗</strong>
        <span class="flrt-review-notice__text"><?php echo wp_kses( __( 'Your review on WordPress.org <strong>keeps the free version alive</strong> — it takes a minute.', 'filter-everything' ), array( 'strong' => array() ) ); ?></span>
        <span class="flrt-review-notice__support">
            <?php
            echo wp_kses(
                sprintf(
                    /* translators: %1$s: opening link tag to the support forum, %2$s: closing link tag */
                    __("Something not working right? %1\$sContact support%2\$s — we'd rather fix it first.", 'filter-everything'),
                    '<a href="' . esc_url(ReviewRequest::SUPPORT_URL) . '" target="_blank" rel="noopener">',
                    '</a>'
                ),
                ['a' => ['href' => [], 'target' => [], 'rel' => []]]
            );
            ?>
        </span>
    </div>
    <div class="flrt-review-notice__actions">
        <a class="button button-primary flrt-review-notice__rate" href="<?php echo esc_url(ReviewRequest::REVIEW_URL); ?>" target="_blank" rel="noopener" data-flrt-review="rated"><?php echo esc_html__('Rate Filter Everything', 'filter-everything'); ?> &#9733;</a>
        <span class="flrt-review-notice__links">
            <button type="button" class="button-link" data-flrt-review="later"><?php echo esc_html__('Maybe later', 'filter-everything'); ?></button>
            <span class="flrt-review-notice__dot" aria-hidden="true">&middot;</span>
            <button type="button" class="button-link" data-flrt-review="never"><?php echo esc_html__("Don't show this again", 'filter-everything'); ?></button>
        </span>
        <span class="flrt-review-notice__hint"><?php echo esc_html__('A WordPress.org account is needed to post a review.', 'filter-everything'); ?></span>
    </div>
    <button type="button" class="notice-dismiss flrt-review-notice__close" data-flrt-review="later"><span class="screen-reader-text"><?php echo esc_html__('Maybe later', 'filter-everything'); ?></span></button>
</div>
<style>
    .flrt-review-notice{position:relative;display:flex;align-items:center;flex-wrap:wrap;gap:12px 24px;padding:16px 44px 16px 20px;margin:15px 20px 12px 2px;border:1px solid #c3c4c7;border-left:4px solid #3858E9;border-radius:4px;background:#fff;box-shadow:0 1px 1px rgba(0,0,0,.04)}
    .flrt-review-notice__stars{display:flex;gap:2px;flex:none}
    .flrt-review-notice .flrt-review-notice__star{color:#3858E9;font-size:26px;line-height:1;text-decoration:none;border:0;box-shadow:none;transition:transform .15s ease}
    .flrt-review-notice .flrt-review-notice__star:hover,.flrt-review-notice .flrt-review-notice__star:focus{color:#3858E9;transform:scale(1.15);outline:none;box-shadow:none}
    .flrt-review-notice__body{flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:3px;font-size:14px;line-height:1.5;color:#1F2028}
    .flrt-review-notice__text strong{font-weight:700;color:#1F2028;background:linear-gradient(transparent 58%,#DDE3FF 58%);padding:0 2px;border-radius:2px}
    .flrt-review-notice__title{font-size:16px;line-height:1.3;color:#1F2028}
    .flrt-review-notice__support{color:#646970;font-size:13px}
    .flrt-review-notice__actions{display:flex;flex-direction:column;align-items:flex-start;gap:6px;flex:none}
    .flrt-review-notice__rate.button{background:#3858E9;border-color:#3858E9;font-weight:600}
    .flrt-review-notice__rate.button:hover,.flrt-review-notice__rate.button:focus{background:#2f4bd0;border-color:#2f4bd0}
    .flrt-review-notice__links{font-size:13px;color:#646970}
    .flrt-review-notice__links .button-link{font-size:13px;color:#646970;text-decoration:underline}
    .flrt-review-notice__links .button-link:hover{color:#1F2028}
    .flrt-review-notice__dot{margin:0 6px}
    .flrt-review-notice__hint{font-size:12px;color:#8c8f94}
    .flrt-review-notice__close.notice-dismiss{position:absolute;top:8px;right:6px}
</style>
<script>
    ( function () {
        var box = document.getElementById( 'flrt-review-notice' );
        if ( ! box ) { return; }
        // The server already auto-snoozed on render, so hiding is always safe;
        // the request just records the explicit choice (later / never / rated).
        box.addEventListener( 'click', function ( e ) {
            var el = e.target.closest ? e.target.closest( '[data-flrt-review]' ) : null;
            if ( ! el ) { return; }
            var mode = el.getAttribute( 'data-flrt-review' );
            var body = 'action=flrt_review_popup&nonce=' + encodeURIComponent( box.getAttribute( 'data-nonce' ) ) + '&mode=' + encodeURIComponent( mode );
            var xhr  = new XMLHttpRequest();
            xhr.open( 'POST', window.ajaxurl || '/wp-admin/admin-ajax.php', true );
            xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
            xhr.send( body );
            box.parentNode.removeChild( box );
        } );
    } )();
</script>
