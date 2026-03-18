<?php

if (!defined('ABSPATH')) {
    exit;
}

global $submenu, $parent_file, $submenu_file, $plugin_page, $pagenow;

$parent_slug = 'edit.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE;


$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
$current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
$tabs = array();
if (isset($submenu[$parent_slug])) {
    foreach ($submenu[$parent_slug] as $i => $sub_item) {

        if (!current_user_can($sub_item[1])) {
            continue;
        }

        if ($i === 1) {
            continue;
        }

        $tab = array(
                'text' => $sub_item[0],
                'url'  => $sub_item[2]
        );

        if (!strpos($sub_item[2], '.php')) {
            $tab['url'] = add_query_arg(array('page' => $sub_item[2]), $parent_slug);
        }

        $tab_tab = '';
        $tab_url_parts = wp_parse_url($tab['url']);
        if (isset($tab_url_parts['query'])) {
            parse_str($tab_url_parts['query'], $tab_qs);
            if (isset($tab_qs['tab'])) {
                $tab_tab = sanitize_key($tab_qs['tab']);
            }
        }

        $tab['is_active'] = false;

        $tab_matches = false;
        if (strpos($sub_item[2], '.php') === false) {
            $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
            $tab_matches  = ($current_page === sanitize_key($sub_item[2]));
        } else {
            $tab_matches = ($submenu_file === $sub_item[2] || $plugin_page === $sub_item[2]);
        }
        $is_same_submenu = $tab_matches;

        if ($is_same_submenu) {
            if($current_tab !== 'import_export' && $current_page == 'filters-settings'){
                $tab['is_active'] = true;
            }elseif ($current_tab) {
                if ($tab_tab && $tab_tab === $current_tab) {
                    $tab['is_active'] = true;
                }
            } else {
                if (empty($tab_tab)) {
                    $tab['is_active'] = true;
                }
            }
        }

        if ($i === 0 && $submenu_file === 'post-new.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE) {
            $tab['is_active'] = true;
        }
        $tabs[] = $tab;
    }
}

$tabs = apply_filters('wpc_header_nav_tabs', $tabs);

if ($tabs === false) {
    return;
}
?>
<div class="wpc-admin-toolbar">
    <h2><img src="<?php
        echo esc_attr(flrt_get_icon_svg('#333333'));
        ?>" alt="" width="24"/> <?php
        echo esc_html(flrt_get_plugin_name());
        ?></h2>
    <?php foreach ($tabs as $tab) {
        $is_active = !empty($tab['is_active']) ? ' is-active' : '';
        $is_pro = str_contains($tab['text'], 'wpc-pro-badge') ? ' wpc-pro-badge-text' : '';
        $tab_text = str_replace('wpc-pro-badge', 'wpc-pro-badge-transparent', $tab['text']);
        printf(
                '<a class="wpc-tab%s%s" href="%s">%s</a>',
                $is_active,
                $is_pro,
                esc_url($tab['url']),
                wp_kses($tab_text, array(
                        'span' => array(
                                'class' => array(),
                        ),
                ))
        );
    } ?>
    <div class="wpc-admin-right">
        <?php do_action('wpc_admin_toolbar_right'); ?>
    </div>
</div>