<?php
/**
 * Review request popup (free build only) — see src/Admin/ReviewRequest.php.
 *
 * @var string $installs_label e.g. "50,000+"
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

            <div class="flrt-review-popup-message-side">
                <div class="flrt-review-popup-heading"><?php echo esc_html__("You've been using Filter Everything for a while now", 'filter-everything'); ?></div>
                <div class="flrt-review-popup-subtext"><?php echo esc_html__('We hope the plugin has made building filters easier for you. If it has, could you take a minute to rate it on WordPress.org?', 'filter-everything'); ?></div>
                <div class="flrt-review-popup-bullets">
                    <div class="flrt-review-popup-bullet">
                        <div class="flrt-review-popup-bullet-check">&#10003;</div>
                        <div class="flrt-review-popup-bullet-text"><?php echo esc_html__('It takes about a minute — a star rating and a sentence is plenty', 'filter-everything'); ?></div>
                    </div>
                    <div class="flrt-review-popup-bullet">
                        <div class="flrt-review-popup-bullet-check">&#10003;</div>
                        <div class="flrt-review-popup-bullet-text"><?php echo esc_html__('Reviews help other WordPress users find the plugin', 'filter-everything'); ?></div>
                    </div>
                    <div class="flrt-review-popup-bullet">
                        <div class="flrt-review-popup-bullet-check">&#10003;</div>
                        <div class="flrt-review-popup-bullet-text"><?php echo esc_html__('Your feedback shapes what we build next', 'filter-everything'); ?></div>
                    </div>
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

            <div class="flrt-review-popup-cta-side">
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
                    <div class="flrt-review-popup-social-proof">
                        <?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: active installs count, e.g. "50,000+" */
                                __('Trusted by %s active installs', 'filter-everything'),
                                '<strong>' . esc_html($installs_label) . '</strong>'
                            ),
                            ['strong' => []]
                        );
                        ?>
                    </div>
                </div>
                <a class="flrt-review-popup-rate-btn" href="<?php echo esc_url(ReviewRequest::REVIEW_URL); ?>" target="_blank" rel="noopener"><?php echo esc_html__('Rate Filter Everything', 'filter-everything'); ?> <span class="flrt-review-popup-rate-btn-star">&#9733;</span></a>
                <div class="flrt-review-popup-dismiss-row">
                    <span class="flrt-review-popup-later"><?php echo esc_html__('Maybe later', 'filter-everything'); ?></span>
                    <span class="flrt-review-popup-dismiss-dot"></span>
                    <span class="flrt-review-popup-never"><?php echo esc_html__("Don't show this again", 'filter-everything'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
