<?php


namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class AboutProTab extends BaseSettings{

    protected $page = 'wpc-filter-about-pro';

    protected $group = 'wpc_filter_about_pro';

    protected $optionName = 'wpc_filter_about_pro';

    public function init() {
        add_action( 'admin_init', array( $this, 'initSettings') );
        add_filter( 'admin_body_class', array( $this, 'wpc_add_body_class' ));
    }

    /**
     * Adds a CSS class to the body element when viewing the Filter Everything Pro settings page.
     *
     * This function is executed on the 'wp_body_class' filter hook. It checks the current screen ID
     * and appends a specific CSS class ('wpc-pro-bnf-setting-page') to the body class array
     * when the user is on the Filter Everything Pro settings page. This class can be used to apply
     * specific styling rules to the page in the front end.
     *
     * @param array $classes An array of existing body classes.
     * @return array The modified array of body classes.
     */
    public function wpc_add_body_class( $classes ) {
        $screen = get_current_screen();

        if ( 'filters_page_filters-settings' === $screen->id ) {
            $classes .= ' wpc-pro-bnf-setting-page';
        }

        return $classes;
    }

    public function initSettings() {
        register_setting($this->group, $this->optionName);
        add_action('wpc_before_sections_settings_fields', array( $this, 'aboutProInfo' ) );
    }


    // render retina images
    protected function wpc_pro_bnf_get_retina_img( $filename, $alt = '' ) {
        $path = 'assets/img/Admin/pro-benefits-page/';
        $base_url  = FLRT_PLUGIN_DIR_URL . $path;
        $base_dir  = FLRT_PLUGIN_DIR_PATH . $path;

        // check if image exists
        if ( ! file_exists( $base_dir . $filename ) ) {
            return '';
        }

        $url_1x = $base_url . $filename;
        $srcset = array();

        // check for @2x and @3x versions
        foreach ( array( 2, 3 ) as $multiplier ) {
            $retina_name = str_replace('.png', "@{$multiplier}x.png", $filename);
            if ( file_exists( $base_dir . $retina_name ) ) {
                $srcset[] = $base_url . $retina_name . " {$multiplier}x";
            }
        }

        $srcset_attr = ! empty( $srcset ) ? ' srcset="' . esc_attr( implode( ', ', $srcset ) ) . '"' : '';

        return sprintf(
                '<img src="%1$s"%2$s alt="%3$s">',
                esc_url( $url_1x ),
                $srcset_attr,
                esc_attr( $alt )
        );
    }

    public function aboutProInfo( $page ) {

        if( $this->page == $page ){
            ?>

            <div class="wpc-pro-bnf-page-wrap wp-exclude-emoji">
                <div class="wpc-pro-bnf-wrap">
                    <section class="wpc-pro-bnf-sect-1">
                        <div class="wpc-pro-bnf-sect-1-logo-wrap">
                            <div class="wpc-pro-bnf-sect-1-logo-icon-svg"></div>
                            <div class="wpc-pro-bnf-sect-1-logo-text-svg"></div>
                            <div class="wpc-pro-bnf-sect-1-logo-label"><?php echo _x('PRO', 'benefits-landing', 'filter-everything') ?></div>
                        </div>
                        <div class="wpc-pro-bnf-sect-1-advf-wrap">
                            <p class="wpc-pro-bnf-sect-1-advf-wrap-title">
                                <?php echo wp_kses(
                                        implode('<br>', array(
                                                _x('<span>Full control</span> over filtering,', 'benefits-landing', 'filter-everything'),
                                                _x('page builder support,', 'benefits-landing', 'filter-everything'),
                                                _x('SEO ready & instant', 'benefits-landing', 'filter-everything') . ' ⚡️',
                                        )),
                                        array('br' => array(), 'span' => array())
                                ); ?>
                            </p>
<!--                            <p class="wpc-pro-bnf-sect-1-advf-wrap-desc">-->
                                <?php /* echo wp_kses(
                                        _x('Upgrade to Filter Everything PRO to unlock the full power of filtering, advanced SEO tools, page builder support, and provide a <strong>much better navigation experience</strong> for your visitors.', 'benefits-landing', 'filter-everything'),
                                        array('strong' => array(), 'u' => array() )
                                ); */ ?>
<!--                            </p>-->
                        </div>
                        <div class="wpc-pro-bnf-sect-1-trst-wrap">
                            <div class="wpc-pro-bnf-sect-1-trst-scl">
                                <div class="wpc-pro-bnf-sect-1-trst-scl-stage">
                                    <div class="wpc-pro-bnf-sect-1-trst-scl-laurel trst-scl-laurel-left"></div>
                                    <div class="wpc-pro-bnf-sect-1-trst-scl-laurel trst-scl-laurel-right"></div>
                                    <div class="wpc-pro-bnf-sect-1-trst-scl-card trst-scl-card-card-1">
                                        <span class="wpc-pro-bnf-sect-1-trst-scl-label"><?php echo _x('Trusted by over', 'benefits-landing', 'filter-everything'); ?></span>
                                        <div class="wpc-pro-bnf-sect-1-trst-scl-big-number-sls"><?php echo _x('160,000+', 'benefits-landing', 'filter-everything'); ?></div>
                                        <span class="wpc-pro-bnf-sect-1-trst-scl-subtext-bl"><?php echo _x('users worldwide', 'benefits-landing', 'filter-everything'); ?></span>
                                    </div>
                                    <div class="wpc-pro-bnf-sect-1-trst-scl-card trst-scl-card-card-2">
                                        <div class="wpc-pro-bnf-sect-1-trst-scl-big-number"><?php echo _x('4.88/5', 'benefits-landing', 'filter-everything'); ?></div>
                                        <span class="wpc-pro-bnf-sect-1-trst-scl-label"><?php echo _x('average rating','benefits-landing', 'filter-everything'); ?></span>
                                        <div class="wpc-pro-bnf-sect-1-trst-scl-stars">
                                            <div class="wpc-pro-bnf-sect-1-trst-scl-star"></div>
                                            <div class="wpc-pro-bnf-sect-1-trst-scl-star"></div>
                                            <div class="wpc-pro-bnf-sect-1-trst-scl-star"></div>
                                            <div class="wpc-pro-bnf-sect-1-trst-scl-star"></div>
                                            <div class="wpc-pro-bnf-sect-1-trst-scl-star"></div>
                                        </div>
                                        <span class="wpc-pro-bnf-sect-1-trst-scl-subtext"><?php echo _x('from real customers','benefits-landing', 'filter-everything'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="wpc-pro-bnf-sect-1-anwp-wrap">
                            <div class="wpc-pro-bnf-sect-1-anwp-text-wrap">
                                <p class="wpc-pro-bnf-sect-1-anwp-text"><?php echo wp_kses(
                                            _x('Upgrade to Filter Everything PRO to unlock the full power of filtering, advanced SEO tools, page builder support, and provide a <strong>much better navigation experience</strong> for your visitors.', 'benefits-landing', 'filter-everything'),
                                            array('strong' => array(), 'u' => array())
                                    ); ?>
                                </p>
                            </div>
                            <ul class="wpc-pro-bnf-sect-1-anwp-list">
                                <li class="wpc-pro-bnf-sect-1-anwp-list-item">
                                    <div class="wpc-pro-bnf-sect-1-anwp-list-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                    <p class="wpc-pro-bnf-sect-1-anwp-list-item-text"><?php echo _x('Filter any posts list, custom query, or page builder content','benefits-landing', 'filter-everything'); ?></p>
                                </li>
                                <li class="wpc-pro-bnf-sect-1-anwp-list-item">
                                    <div class="wpc-pro-bnf-sect-1-anwp-list-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                    <p class="wpc-pro-bnf-sect-1-anwp-list-item-text"><?php echo _x('Improve conversions and user experience with advanced filtering capabilities','benefits-landing', 'filter-everything'); ?></p>
                                </li>
                                <li class="wpc-pro-bnf-sect-1-anwp-list-item">
                                    <div class="wpc-pro-bnf-sect-1-anwp-list-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                    <p class="wpc-pro-bnf-sect-1-anwp-list-item-text"><?php echo _x('Get more organic traffic with SEO tools for filtered pages','benefits-landing', 'filter-everything'); ?></p>
                                </li>
                            </ul>
                        </div>
                        <div class="wpc-pro-bnf-sect-1-btns-wrap">
                            <a href="<?php echo esc_url(flrt_unlock_pro_link('features_view_link')) ?>" target="_blank" class="wpc-pro-bnf-sect-1-btns-up-to-pro"><?php echo _x('Upgrade to PRO', 'benefits-landing', 'filter-everything'); ?> <?php echo flrt_crown_icon(); ?></a>
                            <a href="#why-choose-pro" class="wpc-pro-bnf-sect-1-btns-view-all-pro"><?php echo _x('View all PRO features', 'benefits-landing', 'filter-everything'); ?> ↓</a>
                        </div>
                    </section>

                    <!-- ------------------------------------- Trusted by WordPress Professionals (reviews) ----------------------------------------- -->
                    <section class="wpc-pro-bnf-sect-rvw">
                        <h2 class="wpc-pro-bnf-sect-rvw-title"><?php echo _x('Trusted by WordPress Professionals', 'benefits-landing', 'filter-everything'); ?></h2>
                        <p class="wpc-pro-bnf-sect-rvw-subtitle"><?php echo wp_kses( _x( 'Real reviews from independent experts<br>and verified customers.', 'benefits-landing', 'filter-everything' ), array( 'br' => array() ) ); ?></p>
                        <input type="checkbox" id="wpc-rvw-toggle" class="fetm-toggle-checkbox" hidden>
                        <div class="wpc-pro-bnf-sect-rvw-grid">
                            <?php
                            // YouTube video IDs — to add or swap a video, just change the ID here.
                            $rvw_videos = array( 'g1_qlJvNdsg', 'cNqd210P920', 'Ki7LBcrYuAo' );

                            // Real customer reviews taken from the filtereverything.pro landing page.
                            $rvw_reviews = array(
                                    array(
                                            'author' => 'ScarletLyn',
                                            'date'   => '2022-03-12',
                                            'url'    => 'https://codecanyon.net/ratings/3352977',
                                            'text'   => "I am crying my eyeballs out of happiness. I cannot thank you enough. I just spent the whole weekend trying to make one myself because all the others that were fitting my shop scenario were way too heavy and chunky. I needed a specific structure for my website along with efficiently displaying filters depending on which section of the shop the user is in, and GOD, your plugin has ALL the freaking options possible to make it possible for me. I love you, marry me! (Just kidding, but you get the idea). My Query Monitor is happy, I am happy, and when my shop will go online, my customers will be happy to enjoy a blazing fast filtering engine for my greeting cards and stationery! Keep the great work, and please please, keep it loading FAST. Take care.",
                                    ),
                                    array(
                                            'author' => 'bsmolyanov',
                                            'date'   => '2024-09-08',
                                            'url'    => 'https://codecanyon.net/ratings/3601791',
                                            'text'   => "Filter Everything Pro is magnificent plugin! I use it for filtering WordPress posts database of more than 150k posts, with more than 10 custom taxonomies and thousands of terms and it does a great job! Not to mention, Filter Everything Pro is working alongside the GeneratePress theme like a charm, where other filtering solutions just do not cut it. The plugin is very well documented and very flexible for customization. Last but not least, the assistance I received was absolutely exceeding any level of support I have received around here - both in terms of speed, thoroughness and accuracy! The support team was extremely helpful, taking the time to review my entire setup (which is quite complex) and to pinpoint an issue which was related to a third-party plugin and has nothing to do with Filter Everything Pro. Outstanding!!! I definitely recommend Filter Everything Pro to everyone who needs a good filtering solution for posts, custom post types or WooCommerce products.",
                                    ),
                                    array(
                                            'author' => 'migge',
                                            'date'   => '2024-10-17',
                                            'url'    => 'https://codecanyon.net/ratings/3605614',
                                            'text'   => "There are many reason to give this plugin a 5-star rating! I picked Customer Support because it's VERY important. Functionality is easier to spot before purchase. The support for this plugin is beyond what you would expect, so i can recommend Filter Everything both as the best filter plugin that comes along with a matching support. Thanks guys!",
                                    ),
                            );

                            $rvw_total = max( count( $rvw_videos ), count( $rvw_reviews ) );

                            for ( $i = 0; $i < $rvw_total; $i++ ) :
                            // After the first row (one video + one review) push the rest under the "View more" toggle.
                            if ( $i === 1 ) :
                            ?>
                        </div>
                        <div class="wpc-pro-bnf-sect-rvw-more">
                            <div class="wpc-pro-bnf-sect-rvw-grid">
                                <?php
                                endif;
                                $rvw_has_video  = isset( $rvw_videos[ $i ] );
                                $rvw_has_review = isset( $rvw_reviews[ $i ] );

                                // Checkerboard layout: even rows start with the video card, odd rows start with the text card.
                                if ( $i % 2 === 0 ) {
                                    if ( $rvw_has_video )  { $this->renderRvwVideoCard( $rvw_videos[ $i ] ); }
                                    if ( $rvw_has_review ) { $this->renderRvwReviewCard( $rvw_reviews[ $i ] ); }
                                } else {
                                    if ( $rvw_has_review ) { $this->renderRvwReviewCard( $rvw_reviews[ $i ] ); }
                                    if ( $rvw_has_video )  { $this->renderRvwVideoCard( $rvw_videos[ $i ] ); }
                                }
                                endfor;
                                ?>
                            </div>
                            <div class="wpc-pro-bnf-sect-rvw-fade" aria-hidden="true"></div>
                        </div>
                        <div class="wpc-pro-bnf-sect-rvw-bottom">
                            <label for="wpc-rvw-toggle" class="fetm-toggle-btn">
                                <span class="fetm-toggle-text fetm-toggle-text-more"><?php echo esc_html_x( 'View more', 'benefits-landing', 'filter-everything' ); ?></span>
                                <span class="fetm-toggle-text fetm-toggle-text-less"><?php echo esc_html_x( 'View less', 'benefits-landing', 'filter-everything' ); ?></span>
                            </label>
                        </div>
                    </section>

                    <!-- ------------------------------------- section 6 FREE VS PRO START ----------------------------------------- -->
                    <section class="fetm-freevspro fetm-section" id="why-choose-pro">
                        <div class="fetm-freevspro-wrap">
                            <h2 class="fetm-h2-medium"><?php echo wp_kses( _x( 'Choose the right version<br>for you', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></h2>
                            <input type="checkbox" id="fetm-toggle-rows" class="fetm-toggle-checkbox" hidden>
                            <div class="fetm-features-grid">
                                <div class="fetm-row fetm-grid-header">
                                    <div class="fetm-feature fetm-header"><?php echo _x('Features', 'benefits-landing', 'filter-everything'); ?></div>
                                    <div class="fetm-header fetm-free"><?php echo _x('Current version', 'benefits-landing', 'filter-everything'); ?></div>
                                    <div class="fetm-header fetm-pro"><?php echo _x('PRO', 'benefits-landing', 'filter-everything'); ?></div>
                                </div>
                                <!-- Advanced Filtering & Core Value -->
                                <div class="fetm-section-group fetm-section-advanced-filtering">

                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('Advanced Filtering & Core Value', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Filter any content — page builders, plugins, or even custom code', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Instant filter counts', 'benefits-landing', 'filter-everything'); ?><span class="fetm-new-badge"><?php echo _x('New', 'benefits-landing', 'filter-everything'); ?></span><span class="wpc-icon-help-tip fetm-help-tip" data-tip="<?php echo esc_attr_x('Filter counts update instantly as visitors click — no page reload, no server request. Even faster than AJAX.', 'benefits-landing', 'filter-everything'); ?>">?</span></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature">∞ <?php echo _x( 'Unlimited Filter Sets', 'benefits-landing', 'filter-everything' ); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Accurate out-of-stock products filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Accurate WooCommerce variation filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('User-friendly mobile filters widget', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Auto-hiding empty filters', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <!-- SEO & Traffic Growth -->
                                <div class="fetm-section-group fetm-section-seo-traffic">
                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('SEO & Traffic Growth', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Full SEO control for filtered pages', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Clean, SEO-friendly filter URLs', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('No empty filter pages or 404s', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('XML sitemap for filtered pages', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <!-- Core Features (Free + Pro) -->
                                <div class="fetm-section-group fetm-section-core-features">
                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('Core Features (Free + Pro)', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Filtering any post type', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Filter by any criteria', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Auto-generated filters', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('AJAX filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Step-by-step filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Search within filtered results', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Sorting widget', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Multilingual compatibility', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Responsive design', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <!-- Advanced Workflow & Flexibility -->
                                <div class="fetm-section-group fetm-section-workflow">

                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('Advanced Workflow & Flexibility', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Import & export settings', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Advanced AJAX support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Additional filtering options', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('One-click Filter Set duplication', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <!-- Support -->
                                <div class="fetm-section-group fetm-section-support">
                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('Support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Standard support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Priority support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="fetm-freevspro-bottom">
                                <label for="fetm-toggle-rows" class="fetm-toggle-btn">
                                    <span class="fetm-toggle-text fetm-toggle-text-more"><?php echo esc_html_x('View more', 'benefits-landing', 'filter-everything'); ?></span>
                                    <span class="fetm-toggle-text fetm-toggle-text-less"><?php echo esc_html_x('View less', 'benefits-landing', 'filter-everything'); ?></span>
                                </label>
                                <div class="fetm-freevspro-links">
                                    <a href="<?php echo esc_url(flrt_unlock_pro_link('compare_buy_btn')) ?>" target="_blank" class="wpc-pro-bnf-sect-1-btns-up-to-pro"><?php echo _x('Buy PRO', 'benefits-landing', 'filter-everything'); ?> <?php echo flrt_crown_icon(); ?></a>
                                </div>
                            </div>
                            <div class="fetm-freevspro-bottom-extra-wrap">
                                <p class="fetm-freevspro-bottom-extra-title"><?php echo _x('Unlock all PRO features', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="fetm-freevspro-bottom-extra-grid">
                                    <div class="fetm-freevspro-bottom-extra-grid-item">
                                        <div class="fetm-freevspro-bottom-extra-grid-item-wlt-icon"></div>
                                        <p class="fetm-freevspro-bottom-extra-grid-item-text"><?php echo _x('Risk-free purchase', 'benefits-landing', 'filter-everything'); ?></p>
                                    </div>
                                    <div class="fetm-freevspro-bottom-extra-grid-item">
                                        <div class="fetm-freevspro-bottom-extra-grid-item-res-icon"></div>
                                        <p class="fetm-freevspro-bottom-extra-grid-item-text"><?php echo _x('Regular updates', 'benefits-landing', 'filter-everything'); ?></p>
                                    </div>
                                    <div class="fetm-freevspro-bottom-extra-grid-item">
                                        <div class="fetm-freevspro-bottom-extra-grid-item-hdf-icon"></div>
                                        <p class="fetm-freevspro-bottom-extra-grid-item-text"><?php echo _x('Premium support', 'benefits-landing', 'filter-everything'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- ------------------------------------- section 6 FREE VS PRO END ----------------------------------------- -->

                    <section class="wpc-pro-bnf-sect-2">
                        <h2 class="wpc-pro-bnf-sect-2-title">
                            <?php echo wp_kses(
                                    _x('Why Upgrade to<br>Filter Everything PRO?', 'benefits-landing', 'filter-everything'),
                                    array('br' => array())
                            ); ?>
                        </h2>
                        <div class="wpc-pro-bnf-sect-2-ultm-wrap">
                            <div class="wpc-pro-bnf-sect-2-ultm-wrap-ultm-img">
                                <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-unlm-numb-fset.png', _x( 'Unlimited Filter Sets', 'benefits-landing', 'filter-everything' ) ); ?>
                            </div>
                            <h3 class="wpc-pro-bnf-sect-2-ultm-h3-title"><?php echo _x('∞ Create Unlimited Filter Sets', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-2-ultm-text"><?php echo _x('Create unique filters for every section of your site and let visitors filter content by relevant criteria — e.g. smartphones by brand, clothing by size, or cars by fuel type.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-2-seou-wrap">
                            <div class="wpc-pro-bnf-sect-2-seou-img">
                                <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-seo-cap-cln-url.png', _x( 'SEO Capabilities', 'benefits-landing', 'filter-everything' ) ); ?>
                            </div>
                            <h3 class="wpc-pro-bnf-sect-2-seou-h3-title"><?php echo _x('Auto-generate SEO data for filtered pages', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-2-seou-text"><?php echo _x('Generate SEO titles, H1s, descriptions, and SEO texts based on selected filters to attract more organic traffic from search engines and AI services.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-2-mobl-wrap">
                            <div class="wpc-pro-bnf-sect-2-mobl-img">
                                <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-mob-dvs-comf.png', _x( 'Mobile Devices', 'benefits-landing', 'filter-everything' ) ); ?>
                            </div>
                            <div class="wpc-pro-bnf-sect-2-mobl-h3-title-text-wrap">
                                <h3 class="wpc-pro-bnf-sect-2-mobl-h3-title"><?php echo wp_kses(
                                            _x('Maximum convenience for Mobile devices', 'benefits-landing', 'filter-everything'),
                                            array('br' => array())
                                    ); ?></h3>
                                <p class="wpc-pro-bnf-sect-2-mobl-text"><?php echo _x('Use the built-in, user-friendly filter widget for mobile devices.', 'benefits-landing', 'filter-everything'); ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="wpc-pro-bnf-sect-4">
                        <h2 class="wpc-pro-bnf-sect-4-title">
                            <?php echo wp_kses( _x( 'What does Filter Everything PRO<br>unlock?', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?>
                        </h2>
                        <p class="wpc-pro-bnf-sect-4-desc"><span class="wpc-pro-bnf-sect-4-text-wrapper"><?php echo wp_kses( _x( "The PRO version includes tools that <strong>increase conversions</strong><br />by helping visitors find the right content faster.", 'benefits-landing', 'filter-everything' ), [ 'br' => [], 'strong' => [] ] ); ?></span></p>
                        <div class="wpc-pro-bnf-sect-4-gseo-wrap">
                            <div class="wpc-pro-bnf-sect-4-gseo-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo wp_kses( _x( 'Full control over SEO', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('Get the most out of filtered pages with full control over SEO titles, H1s, descriptions, SEO text, and XML sitemaps. Better indexation means more organic traffic.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-flwp-wrap">
                            <div class="wpc-pro-bnf-sect-4-flwp-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo wp_kses( _x( 'Filter Any Content', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('Page builder content, WooCommerce products, custom post types, and even custom WP_Query — the PRO version handles it all!', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-flst-wrap">
                            <div class="wpc-pro-bnf-sect-4-flst-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo wp_kses( _x( '∞ Unlimited Filter Sets', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('Create a dedicated Filter Set for every section of your site instead of using the same filters everywhere.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-dupl-wrap">
                            <div class="wpc-pro-bnf-sect-4-dupl-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo _x('Tools for faster workflow', 'benefits-landing', 'filter-everything'); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('Back up, duplicate, transfer, and reuse your filters, Filter Sets, and settings with ease.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-extr-wrap">
                            <div class="wpc-pro-bnf-sect-4-extr-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo _x('Precision filtering', 'benefits-landing', 'filter-everything'); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('Accurate variation filtering, proper handling of out-of-stock products, auto-hiding empty filters, and more — the details that make the difference.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                    </section>

                    <!-- ------------------------------------- Works and is trusted worldwide (moved below "What does PRO unlock?") ----------------------------------------- -->
                    <section class="wpc-pro-bnf-sect-3">
                        <h2 class="wpc-pro-bnf-sect-3-title"><?php echo wp_kses(
                                    _x('Works and is trusted<br>worldwide', 'benefits-landing', 'filter-everything'),
                                    array('br' => array())
                            ); ?></h2>
                        <div class="wpc-pro-bnf-sect-3-wbst-wrap">
                            <h3 class="wpc-pro-bnf-sect-3-wbst-h3"><?php echo _x('160&nbsp;000+', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-3-wbst-desc"><?php echo _x('websites / users worldwide', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-3-csmt-wrap">
                            <h3 class="wpc-pro-bnf-sect-3-csmt-h3"><?php echo _x('16&nbsp;200+', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-3-csmt-desc"><?php echo _x('customers on CodeCanyon', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-3-rate-wrap">
                            <h3 class="wpc-pro-bnf-sect-3-rate-h3"><?php echo _x('4.88/5', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-3-rate-desc"><?php echo _x('rating based on real reviews', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                    </section>

                    <section class="wpc-pro-bnf-sect-5">
                        <h2 class="wpc-pro-bnf-sect-5-title"><?php echo _x('PRO Features Overview', 'benefits-landing', 'filter-everything'); ?></h2>

                        <div class="wpc-pro-bnf-sect-5-ablt-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('Full power of filtering', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Filtering any content — including page builders and custom queries', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text">∞ <?php echo _x( 'Unlimited Filter Sets', 'benefits-landing', 'filter-everything' ); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Works with your existing content — no need to rebuild your site', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpc-pro-bnf-sect-5-advn-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('Filtering accuracy', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Accurate WooCommerce variation filtering', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Accurate out-of-stock products filtering', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Auto-hiding empty filters', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpc-pro-bnf-sect-5-expr-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('Convenience for everyone', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('User-friendly mobile filters widget', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('One-click import & export for Filters, Filter Sets, and settings', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Instant Filter Set duplication', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpc-pro-bnf-sect-5-seop-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('SEO & organic traffic', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Full SEO control for filtered pages', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Clean, SEO-friendly filter URLs', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"><svg width="24" height="24"><use href="#fetm-icon-check-double"/></svg></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('XML sitemap for filtered pages', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- ------------------------------------- section 6 FAQ START ----------------------------------------- -->
                    <section class="fetm-sec-faq fetm-section">
                        <h2 class="fetm-h2-medium"><?php echo _x('Frequently Asked Questions', 'benefits-landing', 'filter-everything'); ?></h2>
                        <details>
                            <summary><?php echo _x('What happens to my existing setup when I upgrade to PRO?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('None of your work will be lost. All your created Filter Sets, filters, and settings are fully preserved — the PRO version uses the same data. You also won\'t need to rebuild your page structure, content, or filter placement: PRO simply extends the plugin\'s capabilities and adds new features without changing your current filtering logic. The only thing we recommend regarding the order of steps is to install and activate the PRO version first, and only then deactivate the Free version. This way all your data is guaranteed to stay in place.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('Will the PRO version slow down my site?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo wp_kses( _x( 'No, the PRO version will not slow down your site. The plugin itself is optimized for fast performance and does not add unnecessary load on its own.<br><br>However, keep in mind that the speed of filtering depends on the number of posts, attributes, and your hosting resources. With large datasets, performance is primarily determined by server capacity rather than the plugin version.', 'benefits-landing', 'filter-everything' ),
                                        [ 'br' => [] ]
                                ); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('Is the PRO version compatible with my theme or page builder?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo wp_kses( _x( 'Yes, the PRO version is compatible with most WordPress themes and popular page builders. The plugin relies on the standard WP_Query mechanism, which allows it to integrate correctly with Gutenberg, Elementor, WPBakery, Divi, Avada, and other page builders.<br><br>In most cases, no additional configuration is required — filters will work with your theme out of the box. For highly customized or non-standard templates, flexible integration options are also available.', 'benefits-landing', 'filter-everything' ),
                                        [ 'br' => [] ]
                                ); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('How many sites does my license cover?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php printf( _x('The <a href="%s" target="_blank">Personal license</a> covers two sites — your main production website and its development copy (DEV / staging). This means you can use the plugin on the live site and simultaneously on its test copy for development, updates, and verification before applying changes to the production environment.', 'benefits-landing', 'filter-everything'), esc_url( flrt_unlock_pro_link( 'faq_hms' ) ) ); ?>
                            </p>
                            <p>
                                <?php echo _x('For larger projects, Freelancer and Agency licenses are available and allow you to use the plugin on multiple websites.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary> <?php echo _x('Is support included with the PRO upgrade?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('Yes, of course — support is included with the PRO version. Your purchase comes with 1 year of premium support and plugin updates, with the option to renew. Even if you choose not to renew after a year, you can keep using the plugin — you just won\'t receive updates anymore.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('What is the refund policy?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php printf( _x('We always do our best to help resolve any issues with the plugin and provide the support you need. If that\'s not possible, you can use the refund policy and get your money back in accordance with the rules of the website where you purchased the plugin (CodeCanyon, or the official site <a href="%s" target="_blank">filtereverything.pro</a>).', 'benefits-landing', 'filter-everything'), esc_url( flrt_refund_policy_link() ) ); ?>
                            </p>

                            <p>
                                <?php echo _x('We follow a standard refund period — 30 days from the date of purchase.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                    </section>

                    <section class="wpc-pro-bnf-sect-8">
                        <div class="wpc-pro-bnf-sect-8-left-wrap">
                            <h2 class="wpc-pro-bnf-sect-8-left-title"><?php echo _x('Ready to unlock the full power of Filter Everything PRO?', 'benefits-landing', 'filter-everything'); ?></h2>
                            <p class="wpc-pro-bnf-sect-8-left-desc"><?php echo _x('Upgrade to PRO and take full control over filtering, SEO, and user experience.', 'benefits-landing', 'filter-everything'); ?></p>
                            <a href="<?php echo esc_url(flrt_unlock_pro_link('footer_upgrade_btn')) ?>" target="_blank" class="wpc-pro-bnf-sect-1-btns-up-to-pro"><?php echo _x('Upgrade to PRO', 'benefits-landing', 'filter-everything'); ?> <?php echo flrt_crown_icon(); ?></a>
                        </div>
                        <div class="wpc-pro-bnf-sect-8-right-wrap">
                            <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-red-unl-ftr.png', _x( 'Risk-free purchase, premium support and regular updates', 'benefits-landing', 'filter-everything' ) );?>
                        </div>
                    </section>

                </div>
            </div>

            <svg style="display: none;" xmlns="http://www.w3.org/2000/svg">
                <symbol fill="none" id="fetm-icon-check" viewBox="0 0 24 24">
                    <path d="M20.25 6.75L9.75 17.25L4.5 12"
                          stroke="#3858E9"
                          stroke-width="2.25"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                </symbol>
                <symbol fill="none" id="fetm-icon-check-double" viewBox="0 0 24 24">
                    <path d="M17.75 6.75L7.25 17.25L2 12"
                          stroke="#3858E9"
                          stroke-width="2.25"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                    <path d="M22.75 6.75L12.25 17.25"
                          stroke="#3858E9"
                          stroke-width="2.25"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                </symbol>
                <symbol fill="none" id="fetm-icon-minus" viewBox="0 0 24 24">
                    <path d="M4 12C4 11.4477 4.44772 11 5 11H19C19.5523 11 20 11.4477 20 12C20 12.5523 19.5523 13 19 13H5C4.44772 13 4 12.5523 4 12Z"
                          fill="#3D484C"/>
                </symbol>
                <symbol fill="none" id="fetm-icon-star" viewBox="0 0 24 24">
                    <path d="M12.0012 16.9998L6.12321 20.5898L7.72121 13.8898L2.49121 9.40976L9.35621 8.85976L12.0012 2.49976L14.6462 8.85976L21.5122 9.40976L16.2812 13.8898L17.8792 20.5898L12.0012 16.9998Z" fill="#3858E9"/>
                </symbol>
                <symbol fill="none" id="fetm-icon-link" viewBox="0 0 24 24">
                    <path d="M18.0001 13.4998V19.4998C18.0001 20.0303 17.7894 20.539 17.4143 20.9141C17.0392 21.2891 16.5305 21.4999 16.0001 21.4999H5.00001C4.46958 21.4999 3.96087 21.2891 3.58579 20.9141C3.21071 20.539 3 20.0303 3 19.4998V8.49977C3 7.96933 3.21071 7.46062 3.58579 7.08555C3.96087 6.71047 4.46958 6.49976 5.00001 6.49976H11.0001" stroke="#3858E9" stroke-width="2.02501" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15 3.5H21V9.50004" stroke="#3858E9" stroke-width="2.02501" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 14.5001L21.0001 3.5" stroke="#3858E9" stroke-width="2.02501" stroke-linecap="round" stroke-linejoin="round"/>
                </symbol>
            </svg>

            <script>
                ( function () {
                    var section = document.querySelector( '.wpc-pro-bnf-sect-rvw' );
                    if ( ! section ) {
                        return;
                    }
                    // Delegate from the whole section so the videos under the "View more" area work too.
                    section.addEventListener( 'click', function ( e ) {
                        var btn = e.target.closest( '.wpc-pro-bnf-rvw-video-btn' );
                        if ( ! btn ) {
                            return;
                        }
                        var card = btn.closest( '.wpc-pro-bnf-rvw-video' );
                        var id   = card && card.getAttribute( 'data-ytid' );
                        if ( ! id ) {
                            return;
                        }
                        var iframe = document.createElement( 'iframe' );
                        iframe.className = 'wpc-pro-bnf-rvw-video-iframe';
                        iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&playsinline=1';
                        iframe.title = 'YouTube video player';
                        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
                        iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
                        iframe.setAttribute( 'allowfullscreen', '' );
                        iframe.frameBorder = '0';
                        card.innerHTML = '';
                        card.appendChild( iframe );
                    } );
                } )();
            </script>

            <?php
        }
    }

    /**
     * Renders a single video review card (lazy YouTube facade) for the "Trusted by WordPress Professionals" section.
     *
     * @param string $video_id YouTube video ID.
     */
    protected function renderRvwVideoCard( $video_id ) {
        $vid = preg_replace( '/[^A-Za-z0-9_-]/', '', $video_id );
        ?>
        <div class="wpc-pro-bnf-rvw-card wpc-pro-bnf-rvw-video" data-ytid="<?php echo esc_attr( $vid ); ?>">
            <button type="button" class="wpc-pro-bnf-rvw-video-btn" aria-label="<?php echo esc_attr_x( 'Play video', 'benefits-landing', 'filter-everything' ); ?>">
                <img class="wpc-pro-bnf-rvw-video-thumb"
                     src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . $vid . '/maxresdefault.jpg' ); ?>"
                     onerror="this.onerror=null;this.src='https://i.ytimg.com/vi/<?php echo $vid; ?>/hqdefault.jpg';"
                     alt="" loading="lazy">
                <span class="wpc-pro-bnf-rvw-video-play" aria-hidden="true">
                    <svg viewBox="0 0 68 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="#FF0000"/>
                        <path d="M45 24 27 14v20z" fill="#FFFFFF"/>
                    </svg>
                </span>
            </button>
        </div>
        <?php
    }

    /**
     * Renders a single text review card for the "Trusted by WordPress Professionals" section.
     *
     * @param array $rv Review data: author, date (Y-m-d), url, text.
     */
    protected function renderRvwReviewCard( $rv ) {
        ?>
        <div class="wpc-pro-bnf-rvw-card wpc-pro-bnf-rvw-text">
            <div class="wpc-pro-bnf-rvw-head">
                <span class="wpc-pro-bnf-rvw-author"><?php echo esc_html( $rv['author'] ); ?></span>
                <span class="wpc-pro-bnf-rvw-time"><?php echo esc_html( sprintf( _x( '%s ago', 'benefits-landing', 'filter-everything' ), human_time_diff( strtotime( $rv['date'] ) ) ) ); ?></span>
            </div>
            <div class="wpc-pro-bnf-rvw-stars">
                <?php for ( $s = 0; $s < 5; $s++ ) : ?><svg width="20" height="20"><use href="#fetm-icon-star"/></svg><?php endfor; ?>
            </div>
            <p class="wpc-pro-bnf-rvw-text-body"><?php echo esc_html( $rv['text'] ); ?></p>
            <a class="wpc-pro-bnf-rvw-link" href="<?php echo esc_url( $rv['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html_x( 'Read more on CodeCanyon', 'benefits-landing', 'filter-everything' ); ?>
                <svg width="20" height="20"><use href="#fetm-icon-link"/></svg>
            </a>
        </div>
        <?php
    }

    public function getLabel()
    {
        return esc_html__('PRO benefits', 'filter-everything');
    }

    public function getName()
    {
        return 'aboutpro';
    }

    public function valid()
    {
        return true;
    }

    public function labelIcon() : string
    {
        return flrt_diamond_icon('#000');
    }
}