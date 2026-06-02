<?php
if (!defined('ABSPATH')) {
    exit;
}
$create_auto_url = wp_nonce_url(
        admin_url('admin-post.php?action=wpc_create_auto_filter_set'),
        $nonce_action,'_flrt_nonce'
);
$is_registered = true;
$disabled_class = "";
if(function_exists('flrt_get_license_status')){
    $is_registered = flrt_get_license_status();
}
if(!$is_registered){
    $create_auto_url = "";
    $disabled_class = " disabled";
}
if ($has_filter_set) { ?>
        <div id="wpc-create-auto-filter-div">
            <a id="wpc-create-auto-filter-set" class="button wpc-create-auto-filter<?php echo $disabled_class; ?>" <?php echo ($is_registered) ? "href='" . esc_url($create_auto_url) . "'" : ''; ?>>
                <?php echo esc_html__('Create Filters Automatically', 'filter-everything')?>
            </a>
        </div>
<?php }

if (!$has_filter_set) {
    $create_manual_url = admin_url('post-new.php?post_type=' . FLRT_FILTERS_SET_POST_TYPE); ?>
    <style type="text/css">.wp-list-table, .subsubsub {
            display: none !important;
        }
    </style>
    <div class="flrt-create-auto-filter-set">
        <div class="wpc-auto-filter-buttons">
            <a <?php echo ($is_registered) ? "href='" . esc_url($create_auto_url) . "'" : ''; ?> class="button button-primary wpc-create-auto-filter<?php echo esc_attr($disabled_class); ?>"><?php esc_html_e('Create Filters Automatically', 'filter-everything') ?>
            </a>
            <a href="<?php echo esc_url($create_manual_url) ?>" class="button"><?php esc_html_e('Create Filters Manually', 'filter-everything') ?></a>
        </div>
        <div class="description wpc-text-center">
            <p><?php
            printf(
                    esc_html__('%sCreate Filters Automatically%s — instantly generate filters from your site’s categories and fields. You can always edit them later.', 'filter-everything'),
                    '<strong>',
                    '</strong>'
            );
            ?></p>
            <p><?php
            printf(
                    esc_html__('%sCreate Filters Manually%s — full control — add filters exactly as you want.', 'filter-everything'),
                    '<strong>',
                    '</strong>'
            );
            ?></p>
        </div>
    </div>
<?php }
