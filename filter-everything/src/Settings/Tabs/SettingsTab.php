<?php

namespace FilterEverything\Filter;

if ( ! defined('ABSPATH') ) {
    exit;
}

class SettingsTab extends BaseSettings
{
    protected $page = 'wpc-filter-admin-settings';

    protected $group = 'wpc_filter';

    protected $optionName = 'wpc_filter_settings';

    public function init()
    {
        add_action('admin_init', array($this, 'initSettings'));
    }

    public function initSettings()
    {
        register_setting($this->group, $this->optionName);
        /**
         * @see https://developer.wordpress.org/reference/functions/add_settings_field/
        */
        $defaultPostsContainer = flrt_default_posts_container();
        $defaultPrimaryColor   = flrt_default_theme_color();

        $settings = array(
            'mobile_devices' => array(
                'label'  => esc_html__('Mobile devices', 'filter-everything'),
                'fields' => array(
                    'mobile_filter_settings' => array(
                        'type'  => 'select',
                        'title' => esc_html__('Filters widget on mobile should be', 'filter-everything'),
                        'options'       => array(
                            'nothing' => esc_html__('The same as on a desktop', 'filter-everything'),
                            'show_open_close_button' => esc_html__('Collapsed and expanded', 'filter-everything'),
                        ),
                        'default' => 'no',
                        'id'    => 'mobile_filter_settings',
                    ),
                    /*'show_open_close_button'  => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('Collapse Filters Widget on Mobile devices', 'filter-everything'),
                        'id'    => 'show_open_close_button',
                        'label' => esc_html__('Collapse the widget and show the Filters opening button', 'filter-everything'),
                    ),*/
                    'try_move_to_top_sidebar' => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('Sidebar on top', 'filter-everything'),
                        'id'    => 'try_move_to_top_sidebar',
                        'label' => esc_html__('Try to move the sidebar to the top on mobile devices', 'filter-everything'),
                    )
                ),
            ),
            'ajax' => array(
                'label'  => esc_html__('AJAX', 'filter-everything'),
                'fields' => array(
                    'enable_ajax'     => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('AJAX for Filters', 'filter-everything'),
                        'id'    => 'enable_ajax',
                        'label' => esc_html__('Try to use AJAX', 'filter-everything'),
                        'description' => esc_html__( 'Please enable this option only after you have ensured that the filtering is working correctly', 'filter-everything' ),
                    ),
                    'posts_container' => array(
                        'type'      => 'text',
                        'title'     => esc_html__('Results container', 'filter-everything'),
                        'id'        => 'posts_container',
                        'default'   => $defaultPostsContainer,
                        'description' => esc_html__( 'e.g. #primary or .main-content', 'filter-everything' ),
                        'label'     => '',
                        'tooltip'   => wp_kses(
                            __( 'The part of the page where your posts or products are listed. When AJAX is enabled, the plugin refreshes only this area after each filter click, without reloading the whole page.<br /><br />Click «Select visually» and simply click the area with your posts, or enter its CSS id or class if you know it.', 'filter-everything' ),
                            array( 'br' => array() )
                        ),
                        'additional_link'     => [
                            'label' => esc_html__( 'Select visually', 'filter-everything' ),
                            'url' => add_query_arg(
                                [
                                    'flrt_get_html_selector' => 1,
                                    'flrt_set_id'            => 'global_posts_container',
                                ],
                                flrt_default_selector_page_link()
                            ),
                        ],
                    ),
                    'apply_button_instant_recount' => array(
                        'type'  => (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) ? 'checkbox' : 'inProButton',
                        'pro_label'  => (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) ? '' : flrt_pro_promo_label(),
                        'title' => esc_html__('Instant recount for the «Apply Button» mode', 'filter-everything'),
                        'id'    => 'apply_button_instant_recount',
                        'label' => esc_html__('Instantly recalculate filter counters in the browser', 'filter-everything'),
                        'description' => (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO)
                            ? esc_html__( 'When disabled, Filter Sets in the «Apply Button» mode recalculate counters on the server with an AJAX request on every filter click.', 'filter-everything' )
                            // Free: promo wording — sells what the PRO option does
                            : esc_html__( 'When enabled, Filter Sets in the «Apply Button» mode recalculate counters instantly in the browser, without an AJAX request on every filter click.', 'filter-everything' ),
                        'tooltip'   => wp_kses(
                            __( 'This option greatly speeds up filtering, but the recount runs in the visitor\'s browser and may be heavy on sites with a large amount of content. If you filter more than 30,000 posts, make sure filtering does not freeze — especially on mobile devices.', 'filter-everything' ),
                            array( 'br' => array() )
                        ),
                    )
                )
            ),
            'common_settings' => array(
                'label'  => esc_html__('Other', 'filter-everything'),
                'fields' => array(
                    'primary_color' => array(
                        'type'    => 'text',
                        'title'   => esc_html__('Widget Primary Color', 'filter-everything'),
                        'id'      => 'wpc_primary_color',
                        'default' => $defaultPrimaryColor,
                        'label'   => '',
                    ),
                    'disable_filter_links_for_bots' => array(
                        'type'  => (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) ? 'checkbox' : 'inProButton',
                        'pro_label'  => (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO) ? '' : flrt_pro_promo_label(),
                        'title' => esc_html__('Disable filter links for crawlers', 'filter-everything'),
                        'id'    => 'disable_filter_links_for_bots',
                        'description' => esc_html__( 'Replaces &lt;a&gt; tags with &lt;span&gt; in filters to prevent crawlers from following filter links and overloading your site.', 'filter-everything' ),
                        // The detailed how-it-works lives in the tooltip; in free the
                        // short promo description is enough
                        'tooltip' => (defined('FLRT_FILTERS_PRO') && FLRT_FILTERS_PRO)
                            ? wp_kses(
                                __( 'Filter combinations produce thousands of URLs, and crawlers may waste your crawl budget and server resources trying to follow them all. This option renders filter links as &lt;span&gt; tags — they keep working for visitors, but crawlers no longer see them as links.<br /><br />Links to pages that your SEO Rules define as indexed always keep the real &lt;a&gt; tag, so search engines can still discover and rank those pages.<br /><br />You can also block unwanted pages and specific bots in the robots.txt file.', 'filter-everything' ),
                                array( 'br' => array() )
                            )
                            : ''
                    ),
                    'container_height' => array(
                        'type'  => 'text',
                        'title' => esc_html__('Filter Container max height, px', 'filter-everything'),
                        'id'    => 'container_height',
                        'label' => '',
                    ),
                    'show_terms_in_content' => array(
                        'type'  => 'select',
                        'title' => esc_html__('Selected Filters (Chips) integration', 'filter-everything'),
                        'id'    => 'show_terms_in_content',
                        'label' => esc_html__('Try to show selected terms above the Results container', 'filter-everything'),
                        'options' => array(),
                        'multiple' => true,
                        'description' => esc_html__( 'Select where to show Chips on your site. Or enter your theme\'s hooks. For example: before_main_content', 'filter-everything' )
                    ),
                    'widget_debug_messages' => array(
                        'type'  => 'checkbox',
                        'title' => esc_html__('Debug mode', 'filter-everything'),
                        'id'    => 'widget_debug_messages',
                        'label' => esc_html__('Enable debugging messages to help to configure filters', 'filter-everything'),
                    )
                )
            )
        );

        if (!defined('FLRT_FILTERS_PRO')) {
            $settings['mobile_devices']['fields']['mobile_filter_settings']['options'][''] = esc_html__('Appear as a Pop-up', 'filter-everything') . ' &mdash; ' . esc_html__('Available in PRO', 'filter-everything');
            $settings['mobile_devices']['fields']['mobile_filter_settings']['disabled'][] = '';
        }

        $settings = apply_filters('wpc_general_filters_settings', $settings);

        $this->registerSettings($settings, $this->page, $this->optionName);
    }

    public function getLabel()
    {
        return esc_html__('General', 'filter-everything');
    }

    public function getName()
    {
        return 'settings';
    }

    public function valid()
    {
        return true;
    }
}

