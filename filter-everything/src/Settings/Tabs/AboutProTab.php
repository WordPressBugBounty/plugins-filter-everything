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

            <div class="wpc-pro-bnf-page-wrap">
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
                                                _x('Advanced filtering', 'benefits-landing', 'filter-everything'),
                                                _x('Full control', 'benefits-landing', 'filter-everything'),
                                                _x('SEO-ready', 'benefits-landing', 'filter-everything'),
                                        )),
                                        array('br' => array())
                                ); ?>
                            </p>
                            <p class="wpc-pro-bnf-sect-1-advf-wrap-desc">
                                <?php echo wp_kses(
                                        _x('Upgrade to <b>Filter Everything PRO</b> to unlock advanced filter<br>capabilities, SEO features, and full control over the filtering process.', 'benefits-landing', 'filter-everything'),
                                        array('b' => array(), 'br' => array())
                                ); ?>
                            </p>
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
                                        <div class="wpc-pro-bnf-sect-1-trst-scl-big-number"><?php echo _x('4.91/5', 'benefits-landing', 'filter-everything'); ?></div>
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
                                <p class="wpc-pro-bnf-sect-1-anwp-text"><?php echo _x('Filter any WP_Query, get SEO Rules, obtain clean URLs, and unlock many convenient time-saving options — all available in the PRO version.','benefits-landing', 'filter-everything'); ?></p>
                            </div>
                            <ul class="wpc-pro-bnf-sect-1-anwp-list">
                                <li class="wpc-pro-bnf-sect-1-anwp-list-item">
                                    <div class="wpc-pro-bnf-sect-1-anwp-list-item-icon"></div>
                                    <p class="wpc-pro-bnf-sect-1-anwp-list-item-text"><?php echo _x('Filter any WP_Query','benefits-landing', 'filter-everything'); ?></p>
                                </li>
                                <li class="wpc-pro-bnf-sect-1-anwp-list-item">
                                    <div class="wpc-pro-bnf-sect-1-anwp-list-item-icon"></div>
                                    <p class="wpc-pro-bnf-sect-1-anwp-list-item-text"><?php echo _x('Apply SEO Rules','benefits-landing', 'filter-everything'); ?></p>
                                </li>
                                <li class="wpc-pro-bnf-sect-1-anwp-list-item">
                                    <div class="wpc-pro-bnf-sect-1-anwp-list-item-icon"></div>
                                    <p class="wpc-pro-bnf-sect-1-anwp-list-item-text"><?php echo _x('Get clean URLs','benefits-landing', 'filter-everything'); ?></p>
                                </li>
                            </ul>
                        </div>
                        <div class="wpc-pro-bnf-sect-1-btns-wrap">
                            <a href="<?php echo esc_url(flrt_unlock_pro_link()) ?>" target="_blank" class="wpc-pro-bnf-sect-1-btns-up-to-pro"><?php echo _x('Upgrade to PRO', 'benefits-landing', 'filter-everything'); ?></a>
                            <a href="#why-choose-pro" class="wpc-pro-bnf-sect-1-btns-view-all-pro"><?php echo _x('View all PRO features', 'benefits-landing', 'filter-everything'); ?></a>
                        </div>
                    </section>


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
                            <h3 class="wpc-pro-bnf-sect-2-ultm-h3-title"><?php echo _x('∞ Unlimited number of Filter Sets', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-2-ultm-text"><?php echo _x('Create individual filters for each section of your website.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-2-seou-wrap">
                            <div class="wpc-pro-bnf-sect-2-seou-img">
                                <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-seo-cap-cln-url.png', _x( 'SEO Capabilities', 'benefits-landing', 'filter-everything' ) ); ?>
                            </div>
                            <h3 class="wpc-pro-bnf-sect-2-seou-h3-title"><?php echo _x('SEO capabilities + clean URLs', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-2-seou-text"><?php echo _x('Take full control of SEO for filtered results pages and attract additional free traffic from search engines or AI services.', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-2-mobl-wrap">
                            <div class="wpc-pro-bnf-sect-2-mobl-img">
                                <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-mob-dvs-comf.png', _x( 'Mobile Devices', 'benefits-landing', 'filter-everything' ) ); ?>
                            </div>
                            <div class="wpc-pro-bnf-sect-2-mobl-h3-title-text-wrap">
                                <h3 class="wpc-pro-bnf-sect-2-mobl-h3-title"><?php echo wp_kses(
                                            _x('Maximum convenience for Mobile<br>devices', 'benefits-landing', 'filter-everything'),
                                            array('br' => array())
                                    ); ?></h3>
                                <p class="wpc-pro-bnf-sect-2-mobl-text"><?php echo _x('Built-in, user-friendly filter widget for mobile devices.', 'benefits-landing', 'filter-everything'); ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="wpc-pro-bnf-sect-3">
                        <h2 class="wpc-pro-bnf-sect-3-title"><?php echo wp_kses(
                                    _x('Works and is trusted<br>worldwide', 'benefits-landing', 'filter-everything'),
                                    array('br' => array())
                            ); ?></h2>
                        <div class="wpc-pro-bnf-sect-3-wbst-wrap">
                            <h3 class="wpc-pro-bnf-sect-3-wbst-h3"><?php echo _x('160 000+', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-3-wbst-desc"><?php echo _x('websites / users worldwide', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-3-csmt-wrap">
                            <h3 class="wpc-pro-bnf-sect-3-csmt-h3"><?php echo _x('15 000+', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-3-csmt-desc"><?php echo _x('customers on CodeCanyon', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-3-rate-wrap">
                            <h3 class="wpc-pro-bnf-sect-3-rate-h3"><?php echo _x('4.91/5', 'benefits-landing', 'filter-everything'); ?></h3>
                            <p class="wpc-pro-bnf-sect-3-rate-desc"><?php echo _x('rating based on real reviews', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                    </section>

                    <section class="wpc-pro-bnf-sect-4">
                        <h2 class="wpc-pro-bnf-sect-4-title">
                            <?php echo wp_kses( _x( 'What does Filter Everything PRO<br>unlock?', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?>
                        </h2>
                        <p class="wpc-pro-bnf-sect-4-desc"><?php echo wp_kses( _x( 'With PRO, you can go far beyond basic filtering and build scalable solutions<br>for real projects', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                        <div class="wpc-pro-bnf-sect-4-flwp-wrap">
                            <div class="wpc-pro-bnf-sect-4-flwp-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo wp_kses( _x( 'Filter custom posts and complex<br>WP_Query', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('on any wordpress page', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-flst-wrap">
                            <div class="wpc-pro-bnf-sect-4-flst-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo wp_kses( _x( 'Create unlimited<br>Filter Sets', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('and customize them individually to match the needs of any section or page', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-gseo-wrap">
                            <div class="wpc-pro-bnf-sect-4-gseo-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo wp_kses( _x( 'Generate SEO-optimized<br>results pages', 'benefits-landing', 'filter-everything' ), [ 'br' => [] ] ); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('bringing traffic to your website', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-dupl-wrap">
                            <div class="wpc-pro-bnf-sect-4-dupl-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo _x('Duplicate, export, import', 'benefits-landing', 'filter-everything'); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('Filters, Filter Sets, and plugin settings (within one or multiple sites)', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                        <div class="wpc-pro-bnf-sect-4-extr-wrap">
                            <div class="wpc-pro-bnf-sect-4-extr-icon"></div>
                            <p class="wpc-pro-bnf-sect-4-item-title"><?php echo _x('Unlock extra options', 'benefits-landing', 'filter-everything'); ?></p>
                            <p class="wpc-pro-bnf-sect-4-item-desc"><?php echo _x('filtering by variations, hiding empty filters, hiding “out of stock” products, and more', 'benefits-landing', 'filter-everything'); ?></p>
                        </div>
                    </section>

                    <section class="wpc-pro-bnf-sect-5">
                        <h2 class="wpc-pro-bnf-sect-5-title"><?php echo _x('PRO Features Overview', 'benefits-landing', 'filter-everything'); ?></h2>

                        <div class="wpc-pro-bnf-sect-5-ablt-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('Control & flexibility', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text">∞ <?php echo _x( 'Unlimited Filter Sets', 'benefits-landing', 'filter-everything' ); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Filtering for custom queries & page builders', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpc-pro-bnf-sect-5-advn-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('Advanced filtering', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Variation-based product filtering', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Advanced filter types', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpc-pro-bnf-sect-5-expr-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('User experience', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Dedicated mobile filters widget', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('One-click import & export for Filters, Filter Sets, and settings', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('Duplicate Filter Sets in one click', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="wpc-pro-bnf-sect-5-seop-wrap">
                            <div class="wpc-pro-bnf-sect-5-default-data-wrap">
                                <div class="wpc-pro-bnf-sect-5-pro-label"><?php echo _x('PRO', 'benefits-landing', 'benefits-landing', 'filter-everything') ?></div>
                                <p class="pc-pro-bnf-sect-5-default-text"><?php echo _x('SEO & promotion', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="pc-pro-bnf-sect-5-default-icon"></div>
                            </div>
                            <div class="wpc-pro-bnf-sect-5-default-hover-list">
                                <ul class="wpc-pro-bnf-sect-5-default-hover-list-ul">
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('SEO rules for filtered pages', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('SEO-friendly filter URLs', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                    <li class="wpc-pro-bnf-sect-5-default-item">
                                        <div class="wpc-pro-bnf-sect-5-default-item-icon"></div>
                                        <p class="wpc-pro-bnf-sect-5-default-item-text"><?php echo _x('XML sitemap for filter pages', 'benefits-landing', 'filter-everything'); ?></p>
                                    </li>
                                </ul>
                            </div>
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
                                    <div class="fetm-header fetm-pro"><?php echo _x('Pro', 'benefits-landing', 'filter-everything'); ?></div>
                                </div>
                                <!-- Advanced Filtering & Core Value -->
                                <div class="fetm-section-group fetm-section-advanced-filtering">

                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('Advanced Filtering & Core Value', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Any WP_Query Filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature">∞ <?php echo _x( 'Unlimited Filter Sets', 'benefits-landing', 'filter-everything' ); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('SEO Rules for Filter Pages', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Variation-Based Product Filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('SEO-Friendly Filter URLs', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
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
                                        <div class="fetm-feature"><?php echo _x('XML Sitemap for Filter Pages', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Full URL Structure Control', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('No Empty Pages or 404s', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                </div>
                                <!-- Mobile & UX for E-commerce -->
                                <div class="fetm-section-group fetm-section-mobile-ux">

                                    <div class="fetm-row fetm-section">
                                        <div class="fetm-feature"><?php echo _x('Mobile & UX for E-commerce', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free"></div>
                                        <div class="fetm-cell fetm-pro"></div>
                                    </div>

                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Mobile Filters Widget', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Hide Out-of-Stock Products', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
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
                                        <div class="fetm-feature"><?php echo _x('Any Post Type Filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Filter by Any Criteria', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Auto-Generated Filters', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('AJAX Filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Step-by-Step Filtering', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Search Within Results', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Sorting Widget', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Multilingual Support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Responsive Design', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
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
                                        <div class="fetm-feature"><?php echo _x('Import & Export Settings', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Advanced AJAX Containers', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Auto-Hide Empty Filters', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Advanced Filter Field Types', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Duplicate Filter Sets', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
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
                                        <div class="fetm-feature"><?php echo _x('Standard Support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                    <div class="fetm-row">
                                        <div class="fetm-feature"><?php echo _x('Priority Support', 'benefits-landing', 'filter-everything'); ?></div>
                                        <div class="fetm-cell fetm-free">
                                            <svg width="24" height="24"><use href="#fetm-icon-minus"/></svg>
                                        </div>
                                        <div class="fetm-cell fetm-pro">
                                            <svg width="24" height="24"><use href="#fetm-icon-check"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="fetm-freevspro-bottom">
                                <label for="fetm-toggle-rows" class="fetm-toggle-btn"></label>
                                <div class="fetm-freevspro-links">
                                    <a href="<?php echo esc_url(flrt_unlock_pro_link()) ?>" target="_blank" class="wpc-pro-bnf-sect-1-btns-up-to-pro"><?php echo _x('Buy PRO', 'benefits-landing', 'filter-everything'); ?></a>
                                </div>
                            </div>
                            <div class="fetm-freevspro-bottom-extra-wrap">
                                <p class="fetm-freevspro-bottom-extra-title"><?php echo _x('Unlock all PRO features', 'benefits-landing', 'filter-everything'); ?></p>
                                <div class="fetm-freevspro-bottom-extra-grid">
                                    <div class="fetm-freevspro-bottom-extra-grid-item">
                                        <div class="fetm-freevspro-bottom-extra-grid-item-wlt-icon"></div>
                                        <p class="fetm-freevspro-bottom-extra-grid-item-text"><?php echo _x('One-time payment', 'benefits-landing', 'filter-everything'); ?></p>
                                    </div>
                                    <div class="fetm-freevspro-bottom-extra-grid-item">
                                        <div class="fetm-freevspro-bottom-extra-grid-item-res-icon"></div>
                                        <p class="fetm-freevspro-bottom-extra-grid-item-text"><?php echo _x('Lifetime updates', 'benefits-landing', 'filter-everything'); ?></p>
                                    </div>
                                    <div class="fetm-freevspro-bottom-extra-grid-item">
                                        <div class="fetm-freevspro-bottom-extra-grid-item-hdf-icon"></div>
                                        <p class="fetm-freevspro-bottom-extra-grid-item-text"><?php echo _x('6 months of support included', 'benefits-landing', 'filter-everything'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- ------------------------------------- section 6 FREE VS PRO END ----------------------------------------- -->

                    <!-- ------------------------------------- section 6 FAQ START ----------------------------------------- -->
                    <section class="fetm-sec-faq fetm-section">
                        <h2 class="fetm-h2-medium"><?php echo _x('Frequently Asked Questions', 'benefits-landing', 'filter-everything'); ?></h2>
                        <details>
                            <summary><?php echo _x('Will my existing filters disappear after upgrading to PRO?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('No, your existing filters will not disappear after upgrading to PRO. All created Filter Sets, filters, and settings are preserved. We recommend installing and activating the PRO version first, and only then deactivating the Free version — this way all your data will remain intact.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('Will I need to rebuild my site after upgrading to PRO?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('No, you will not need to rebuild your site after upgrading to PRO. All existing page structure, content, and filter settings will remain unchanged. The PRO version simply extends the plugin’s capabilities and adds new features without affecting your current filtering logic.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('Will the PRO version slow down my site?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo wp_kses( _x( 'No, the PRO version will not slow down your site. The plugin itself is optimized for fast performance and does not add unnecessary load on its own.
                                <br><br>
                                However, keep in mind that the speed of filtering depends on the number of posts, attributes, and your hosting resources. With large datasets, performance is primarily determined by server capacity rather than the plugin version.', 'benefits-landing', 'filter-everything' ),
                                        [ 'br' => [] ]
                                ); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('Is the PRO version compatible with my theme or page builder?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo wp_kses( _x( 'Yes, the PRO version is compatible with most WordPress themes and popular page builders. The plugin relies on the standard WP_Query mechanism, which allows it to integrate correctly with Gutenberg, Elementor, WPBakery, Divi, Avada, and other page builders.
                                <br><br>
                                In most cases, no additional configuration is required — filters will work with your theme out of the box. For highly customized or non-standard templates, flexible integration options are also available.', 'benefits-landing', 'filter-everything' ),
                                        [ 'br' => [] ]
                                ); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('How many sites does my license cover?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('The license covers two sites — your main production website and its development copy (DEV / staging). This means you can use the plugin on the live site and simultaneously on its test copy for development, updates, and verification before applying changes to the production environment.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary> <?php echo _x('Is support included with the PRO upgrade?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('Yes, support is included with the PRO purchase. You receive 6 months of support with the option to extend it at any time, along with lifetime plugin updates that include all new features and bug fixes at no additional cost.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                        <details>
                            <summary><?php echo _x('What is the refund policy?', 'benefits-landing', 'filter-everything'); ?></summary>
                            <p>
                                <?php echo _x('We always do our best to help resolve any issues with the plugin and provide the necessary support. If this is not possible, you can use the refund policy and get your money back in accordance with the rules of the website where you purchased the plugin.', 'benefits-landing', 'filter-everything'); ?>
                            </p>
                        </details>
                    </section>

                    <section class="wpc-pro-bnf-sect-8">
                        <div class="wpc-pro-bnf-sect-8-left-wrap">
                            <h2 class="wpc-pro-bnf-sect-8-left-title"><?php echo _x('Ready to unlock the full power of Filter Everything?', 'benefits-landing', 'filter-everything'); ?></h2>
                            <p class="wpc-pro-bnf-sect-8-left-desc"><?php echo _x('Upgrade to PRO and take full control over filtering, SEO, and scalability.', 'benefits-landing', 'filter-everything'); ?></p>
                            <a href="<?php echo esc_url(flrt_unlock_pro_link()) ?>" target="_blank" class="wpc-pro-bnf-sect-1-btns-up-to-pro"><?php echo _x('Upgrade to PRO', 'benefits-landing', 'filter-everything'); ?></a>
                        </div>
                        <div class="wpc-pro-bnf-sect-8-right-wrap">
                            <?php echo $this->wpc_pro_bnf_get_retina_img( 'wpc-red-unl-ftr.png', 'Advanced Features' );?>
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
                <symbol fill="none" id="fetm-icon-minus" viewBox="0 0 24 24">
                    <path d="M4 12C4 11.4477 4.44772 11 5 11H19C19.5523 11 20 11.4477 20 12C20 12.5523 19.5523 13 19 13H5C4.44772 13 4 12.5523 4 12Z"
                          fill="#3D484C"/>
                </symbol>
            </svg>

            <?php
        }
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