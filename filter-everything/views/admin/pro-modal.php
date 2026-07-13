<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="flrt-pro-modal-overlay">
    <svg style="display: none;" xmlns="http://www.w3.org/2000/svg">
        <symbol fill="none" id="flrt-icon-check-double" viewBox="0 0 24 24">
            <path d="M17.75 6.75L7.25 17.25L2 12" stroke="#3858E9" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22.75 6.75L12.25 17.25" stroke="#3858E9" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
        <symbol fill="none" id="flrt-icon-star" viewBox="0 0 24 24">
            <path d="M12.0012 16.9998L6.12321 20.5898L7.72121 13.8898L2.49121 9.40976L9.35621 8.85976L12.0012 2.49976L14.6462 8.85976L21.5122 9.40976L16.2812 13.8898L17.8792 20.5898L12.0012 16.9998Z" fill="#3858E9"/>
        </symbol>
    </svg>
    <div class="flrt-upgrade-to-pro-popup-modal-wrapper">
        <div class="flrt-upgrade-to-pro-popup-modal">
            <div class="flrt-upgrade-to-pro-popup-close-btn" id="flrt-close-modal-btn">
                <div class="flrt-upgrade-to-pro-popup-close-icon"></div>
            </div>

            <div class="flrt-upgrade-to-pro-popup-features-side">
                <table class="flrt-upgrade-to-pro-popup-table">
                    <thead>
                    <tr>
                        <th class="flrt-upgrade-to-pro-popup-th flrt-upgrade-to-pro-popup-th-feature-name"></th>
                        <th class="flrt-upgrade-to-pro-popup-th flrt-upgrade-to-pro-popup-center-col">
                            <?php echo esc_html__('Free', 'filter-everything'); ?>
                        </th>
                        <th class="flrt-upgrade-to-pro-popup-th flrt-upgrade-to-pro-popup-center-col">
                            <?php echo esc_html__('PRO', 'filter-everything'); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $flrt_is_woo = flrt_is_woocommerce(); ?>
                    <tr class="flrt-upgrade-to-pro-popup-tr-first">
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html_x('Filter any content — page builders, plugins, or even custom code', 'benefits-landing', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"><svg width="24" height="24"><use href="#flrt-icon-check-double"/></svg></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><span class="flrt-td-feature-flex"><span><?php echo esc_html_x('Instant filter counts', 'benefits-landing', 'filter-everything'); ?></span><span class="fetm-new-badge"><?php echo esc_html_x('New', 'benefits-landing', 'filter-everything'); ?></span><span class="wpc-icon-help-tip fetm-help-tip" data-tip="<?php echo esc_attr_x('Filter counts update instantly as visitors click — no page reload, no server request. Even faster than AJAX.', 'benefits-landing', 'filter-everything'); ?>">?</span></span></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"><svg width="24" height="24"><use href="#flrt-icon-check-double"/></svg></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo '∞ ' . esc_html_x('Unlimited Filter Sets', 'benefits-landing', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"><svg width="24" height="24"><use href="#flrt-icon-check-double"/></svg></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html_x('Full SEO control for filtered pages', 'benefits-landing', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"><svg width="24" height="24"><use href="#flrt-icon-check-double"/></svg></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><?php
                            echo $flrt_is_woo
                                ? esc_html_x('Accurate filtering of out-of-stock and variable products', 'benefits-landing', 'filter-everything')
                                : esc_html_x('Auto-hiding empty filters', 'benefits-landing', 'filter-everything');
                            ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"><svg width="24" height="24"><use href="#flrt-icon-check-double"/></svg></div>
                        </td>
                    </tr>
                    <tr class="flrt-upgrade-to-pro-popup-tr-last">
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html_x('User-friendly mobile filters widget', 'benefits-landing', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"><svg width="24" height="24"><use href="#flrt-icon-check-double"/></svg></div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="flrt-upgrade-to-pro-popup-see-all-container">
                    <a href="<?php echo esc_url(flrt_pro_features_link()); ?>"
                       class="flrt-upgrade-to-pro-popup-see-all-link" target="_blank">
                        <?php echo _x('View all PRO features', 'benefits-landing', 'filter-everything'); ?>
                        <div class="flrt-upgrade-to-pro-popup-link-icon"></div>
                    </a>
                </div>
            </div>

            <div class="flrt-upgrade-to-pro-popup-promo-side">
                <div class="flrt-upgrade-to-pro-popup-promo-card">
                    <p class="flrt-upgrade-to-pro-popup-promo-title">
                        <?php
                        echo esc_html__('Upgrade to', 'filter-everything')
                                . '<br>'
                                . esc_html__('Filter Everything PRO', 'filter-everything');
                        ?>
                    </p>
                    <div class="flrt-upgrade-to-pro-popup-rating">
                        <?php for ( $flrt_star = 0; $flrt_star < 5; $flrt_star++ ) : ?><svg width="18" height="18"><use href="#flrt-icon-star"/></svg><?php endfor; ?>
                        <span class="flrt-upgrade-to-pro-popup-rating-number">4.88</span>
                    </div>
                    <p class="flrt-upgrade-to-pro-popup-subtitle"><?php echo esc_html__('Get the full power of filtering, SEO tools, and improve conversions', 'filter-everything'); ?></p>

                    <div class="flrt-upgrade-to-pro-popup-pricing-row">
                        <span><?php echo  wp_kses(
                                    __( 'Always<br />WP & Woo compatible', 'filter-everything'),
                                    array( 'br' => array() )
                            ); ?></span>
                        <span class="flrt-upgrade-to-pro-popup-price-dot">•</span>
                        <span class="flrt-upgrade-to-pro-popup-price-tag"><?php echo FLRT_PRO_PRICE; ?><span class="flrt-upgrade-to-pro-popup-price-year">&nbsp;<?php echo esc_html__('/yr', 'filter-everything'); ?></span></span>
                        <span class="flrt-upgrade-to-pro-popup-price-dot">•</span>
                        <span><?php echo esc_html__('1-year updates & support', 'filter-everything'); ?></span>
                    </div>
                    <div class="flrt-upgrade-to-pro-popup-support-text">
                        <?php echo esc_html__('Plugin keeps working after expiry', 'filter-everything'); ?>
                    </div>
                </div>
                <a href="<?php echo esc_url(flrt_unlock_pro_link('popup_unlock_btn')); ?>"
                   class="flrt-upgrade-to-pro-popup-unlock-btn"
                   target="_blank"><?php echo esc_html__('Unlock PRO', 'filter-everything'); ?> <?php echo flrt_crown_icon(); ?></a>
                <div class="flrt-upgrade-to-pro-popup-under-button-text"><?php echo esc_html__('30-day money-back guarantee', 'filter-everything'); ?></div>
            </div>
        </div>
    </div>
</div>
