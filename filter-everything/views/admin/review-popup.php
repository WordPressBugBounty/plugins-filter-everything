<?php
/**
 * Review request popup (free build only) — see src/Admin/ReviewRequest.php.
 *
 * Deliberately minimal, App-Store style: it interrupts someone in the middle
 * of admin work, so one question, five stars, one button, two quiet ways out
 * and a support link for the people who are here because something is wrong.
 *
 * @var string $review_nonce
 */

use FilterEverything\Filter\ReviewRequest;

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="flrt-review-popup-overlay" data-nonce="<?php echo esc_attr($review_nonce); ?>">
    <div class="flrt-review-popup-wrapper">
        <div class="flrt-review-popup-modal">
            <div class="flrt-review-popup-close-btn" aria-label="<?php echo esc_attr__('Close', 'filter-everything'); ?>">&#10005;</div>

            <div class="flrt-review-popup-card">
                <div class="flrt-review-popup-card-title">
                    <?php
                    echo esc_html__('Enjoying', 'filter-everything')
                        . '<br>'
                        . esc_html__('Filter Everything?', 'filter-everything');
                    ?>
                </div>
                <div class="flrt-review-popup-stars">
                    <?php for ($flrt_star = 0; $flrt_star < 5; $flrt_star++) : ?><a class="flrt-review-popup-star" href="<?php echo esc_url(ReviewRequest::REVIEW_URL); ?>" target="_blank" rel="noopener">&#9733;</a><?php endfor; ?>
                </div>
                <div class="flrt-review-popup-card-text"><?php echo esc_html__('Rate us on WordPress.org and help the plugin grow', 'filter-everything'); ?></div>
            </div>

            <a class="flrt-review-popup-rate-btn" href="<?php echo esc_url(ReviewRequest::REVIEW_URL); ?>" target="_blank" rel="noopener"><?php echo esc_html__('Rate Filter Everything', 'filter-everything'); ?> <span class="flrt-review-popup-rate-btn-star">&#9733;</span><span class="flrt-review-popup-rate-btn-ext" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></span></a>

            <div class="flrt-review-popup-dismiss-row">
                <span class="flrt-review-popup-later"><?php echo esc_html__('Maybe later', 'filter-everything'); ?></span>
                <span class="flrt-review-popup-dismiss-dot"></span>
                <span class="flrt-review-popup-never"><?php echo esc_html__("Don't show this again", 'filter-everything'); ?></span>
            </div>

            <div class="flrt-review-popup-support-line">
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
            </div>
        </div>
    </div>
</div>
