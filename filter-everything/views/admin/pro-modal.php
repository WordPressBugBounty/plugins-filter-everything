<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="flrt-pro-modal-overlay">
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
                    <tr class="flrt-upgrade-to-pro-popup-tr-first">
                        <td class="flrt-upgrade-to-pro-popup-td"><?php
                            echo esc_html__('Filter any post list —', 'filter-everything')
                                    . '<br>'
                                    . esc_html__('page builders & custom queries', 'filter-everything');
                            ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html__('∞ Create unlimited filter sets', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html__('Built-in mobile filter widget', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html__('Hide empty filters', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"></div>
                        </td>
                    </tr>
                    <tr class="flrt-upgrade-to-pro-popup-tr-last">
                        <td class="flrt-upgrade-to-pro-popup-td"><?php echo esc_html__('Filter by WooCommerce variations', 'filter-everything'); ?></td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-dash-icon"></div>
                        </td>
                        <td class="flrt-upgrade-to-pro-popup-td flrt-upgrade-to-pro-popup-center-col">
                            <div class="flrt-upgrade-to-pro-popup-checkmark-icon"></div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="flrt-upgrade-to-pro-popup-see-all-container">
                    <a href="<?php echo esc_url(flrt_pro_features_link()); ?>"
                       class="flrt-upgrade-to-pro-popup-see-all-link" target="_blank">
                        <?php echo esc_html__('View all PRO features', 'filter-everything'); ?>
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
                    <p class="flrt-upgrade-to-pro-popup-subtitle"><?php echo esc_html__('Unlock all PRO features and capabilities', 'filter-everything'); ?></p>

                    <div class="flrt-upgrade-to-pro-popup-pricing-row">
                        <span><?php echo esc_html__('Pay once', 'filter-everything'); ?></span>
                        <span class="flrt-upgrade-to-pro-popup-price-dot">•</span>
                        <span class="flrt-upgrade-to-pro-popup-price-tag"><?php echo FLRT_PRO_PRICE; ?></span>
                        <span class="flrt-upgrade-to-pro-popup-price-dot">•</span>
                        <span><?php echo esc_html__('Lifetime updates', 'filter-everything'); ?></span>
                    </div>

                    <div class="flrt-upgrade-to-pro-popup-support-text"><?php echo esc_html__('6 month of support included', 'filter-everything'); ?></div>
                </div>
                <a href="<?php echo esc_url(flrt_unlock_pro_link()); ?>"
                   class="flrt-upgrade-to-pro-popup-unlock-btn"
                   target="_blank"><?php echo esc_html__('Unlock PRO', 'filter-everything'); ?></a>
            </div>
        </div>
    </div>
</div>
