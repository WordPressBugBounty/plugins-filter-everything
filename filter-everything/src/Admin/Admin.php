<?php


namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Admin
{
    public $tabRenderer;
    public $parentSlug;

    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'adminMenu'), 9);
        $this->tabRenderer = Container::instance()->getTabRenderer();
        $filterSet = Container::instance()->getFilterSetService();

        add_action( 'pre_post_update', [$filterSet, 'preSaveSet'], 10, 2 );
        add_action( 'save_post', array( $filterSet, 'saveSet' ), 10, 2 );

        add_action( 'init', array( $this, 'initTabs' ), 11 );

        add_action( 'admin_init', array( $this, 'init' ) );

        add_filter( 'wpc_general_filters_settings', [$this, 'generalFilterSettings'] );

        add_action( 'admin_head', array( $this, 'menuHighlight' ) );
        add_action('admin_head', array($this, 'addAdminStyles'));

    }

    public function init()
    {
        $filterFields = Container::instance()->getFilterFieldsService();
        $filterFields->registerHooks();

        // Check permissions before to show these screens
        add_action( 'load-post.php', [ $this, 'checkPermissions' ] );
        add_action( 'load-edit.php', [ $this, 'checkPermissions' ] );
        add_action( 'load-post-new.php', [ $this, 'checkPermissions' ] );

    }

    public function adminMenu()
    {
        global $submenu;
        $page = 'edit.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE;

        add_menu_page( esc_html__('Filters', 'filter-everything'), esc_html__('Filters', 'filter-everything'), 'manage_options', $page, false,  'none', '85');

        add_submenu_page( $page, esc_html__('Filter Sets', 'filter-everything'), esc_html__('Filter Sets', 'filter-everything'), 'manage_options', $page);
        add_submenu_page( $page, esc_html__('Add New', 'filter-everything'), esc_html__('Add New', 'filter-everything'), 'manage_options', 'post-new.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE);

        if (!defined('FLRT_FILTERS_PRO')) {
            $settings = flrt_vailable_in_pro_attr_link();

            add_submenu_page($page, esc_html__('SEO Rules', 'filter-everything'), esc_html__('SEO Rules', 'filter-everything'), 'manage_options', $settings);
            add_submenu_page($page, esc_html__('Import/Export', 'filter-everything'), esc_html__('Import/Export', 'filter-everything'), 'manage_options', $settings);

            if (isset($submenu[$page])) {
                foreach ($submenu[$page] as $key => $details) {
                    if ($details[2] === $settings) {
                        $submenu[$page][$key][0] .= flrt_pro_promo_label(true);
                    }
                }
            }
        }

        do_action('wpc_add_submenu_pages');

        add_submenu_page( $page, esc_html__('Settings', 'filter-everything'), esc_html__('Settings', 'filter-everything'), 'manage_options', 'filters-settings', array($this, 'filterSettingsPage'));

        do_action('wpc_after_add_submenu_pages');
        
    }

    public function filterSettingsPage()
    {
        $this->tabRenderer->render();
    }

    public function initTabs()
    {
        $this->tabRenderer->register(new SettingsTab());
        $this->tabRenderer->register(new PermalinksTab());

        do_action( 'wpc_setttings_tabs_register', $this->tabRenderer );

        $this->tabRenderer->register(new ExperimentalTab());

        if( ! defined('FLRT_FILTERS_PRO') ) {
            $this->tabRenderer->register( new AboutProTab() );

        }else{
            $show_license_tab = false;

            if ( is_multisite() ) {
                if ( is_main_site() ) {
                    $show_license_tab = true;
                }
            } else {
                $show_license_tab = true;
            }

            if ( $show_license_tab ) {
                $this->tabRenderer->register(new LicenseTab( FLRT_LICENSE_KEY ));
            }
        }

        $this->tabRenderer->init();
    }

    public function menuHighlight()
    {
        if ( ! is_admin() ) {
            return;
        }

        $is_filters_settings = isset($_GET['page']) && $_GET['page'] === 'filters-settings';
        $is_import_export_tab = isset($_GET['tab']) && $_GET['tab'] === 'import_export';

        if ( $is_filters_settings && $is_import_export_tab ) {
            global $parent_file, $submenu_file;

            $parent_file = $this->parentSlug ? $this->parentSlug : ('edit.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE);
            $submenu_file = $parent_file . '&page=filters-settings&tab=import_export';
        }
    }

    public function addAdminStyles()
    {
        if(defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) {
            return;
        }

        if (!is_admin()) {
            return;
        }
        ?>
        <style type="text/css">
            .wpc-pro-badge {
                background: #7A1FA2;
                color: #fff;
                padding: 2px 6px;
                border-radius: 14px;
                margin-left: 6px;
                font-size: 10px;
                border: 1px solid #7A1FA2;
            }
        </style>
        <?php
    }


    public function get_icon_svg()
    {
        return flrt_get_icon_svg();
    }

    public function checkPermissions()
    {
        $screen     = get_current_screen();
        $post_types = [ FLRT_FILTERS_SET_POST_TYPE, FLRT_FILTERS_POST_TYPE ];

        if( defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO ){
            $post_types[] = FLRT_SEO_RULES_POST_TYPE;
        }

        if( ! is_null( $screen ) && property_exists( $screen, 'post_type' ) && in_array( $screen->post_type, $post_types, true ) ){
            if( ! current_user_can( flrt_plugin_user_caps() ) ) {
                wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
            }
        }
    }

    public function generalFilterSettings( $settings )
    {
        $result_terms   = [];

        // Chips hooks
        $maybe_saved_terms  = flrt_get_option('show_terms_in_content', []);
        $theme_dependencies = flrt_get_theme_dependencies();

        $current_terms = $settings['common_settings']['fields']['show_terms_in_content']['options'];

        if( flrt_is_woocommerce() ){
            $woocommerce_terms = array(
                'woocommerce_archive_description' => esc_html__('WooCommerce archive description', 'filter-everything' ),
                'woocommerce_no_products_found' => esc_html__('WooCommerce no products found', 'filter-everything' ),
                'woocommerce_before_shop_loop' => esc_html__('WooCommerce before Shop loop', 'filter-everything' ),
                'woocommerce_before_main_content' => esc_html__('WooCommerce before main content', 'filter-everything' )
            );

            $result_terms = array_merge( $current_terms, $woocommerce_terms );
        }

        if( $maybe_saved_terms && is_array( $maybe_saved_terms )){
            foreach ($maybe_saved_terms as $hook ){
                if( ! in_array( $hook, array_keys( $result_terms ) ) ){
                    $result_terms[$hook] = $hook;
                }
            }
        }

        if( isset( $theme_dependencies['chips_hook'] ) && ! empty( $theme_dependencies['chips_hook'] )){
            foreach ($theme_dependencies['chips_hook'] as $hook ){
                if( ! in_array( $hook, array_keys( $result_terms ) ) ){
                    $result_terms[$hook] = $hook;
                }
            }
        }

        $settings['common_settings']['fields']['show_terms_in_content']['options'] = $result_terms;

        return $settings;
    }

}