<?php
if (!defined('ABSPATH')) {
    exit;
}
$create_url = wp_nonce_url(admin_url('admin.php?action=wpc_create_xml_files'), 'wpc_create_xml_files');

$xml_progress_url = wp_nonce_url(admin_url('admin.php?action=wpc_xml_load_progress'), 'wpc_xml_load_progress');

$xml_file_link = flrt_is_sitemap_exists();
?>
<div class="wpc-create-xml-file-wrap">
    <a id="wpc-create-xml" class="button" href="<?php echo esc_url($create_url); ?>"
       data-xml-progress-url="<?php echo esc_url($xml_progress_url); ?>"
       data-error-create-xml-text="<?php echo esc_html__('Failed to generate XML', 'filter-everything'); ?>"
       data-success-create-xml-text="<?php echo esc_html__('Success: XML sitemap generated successfully.', 'filter-everything'); ?>"
       data-progress-notice-text="<?php echo esc_html__("Please keep this tab open until the process is complete.", 'filter-everything'); ?>"
       data-dismiss-notice-text="<?php echo esc_html__('Dismiss this notice.', 'filter-everything'); ?>"
       data-err-500="<?php echo esc_html__("Error: XML sitemap generation failed due to a server error. Please check your web server's error log or contact support.", 'filter-everything'); ?>"
       data-err-503="<?php echo esc_html__('Error: Service unavailable (503). Please try again later or contact support.', 'filter-everything'); ?>"
       data-err-0="<?php echo esc_html__('Error: Unable to connect to the server. Please check your network connection or try again later.', 'filter-everything'); ?>"
       data-err-404="<?php echo esc_html__('Error: Requested resource not found (404). Please try again or contact support.', 'filter-everything'); ?>"
       data-err-generic-prefix="<?php echo esc_html__('Error', 'filter-everything'); ?>">
    <span>
        <?php
        if ($xml_file_link) {
            if (flrt_check_to_update_xml()){
                echo '<span class="wpc-alert-emoji">⚠️</span>' . esc_html__('Please regenerate the XML sitemap', 'filter-everything');
            } else {
                echo esc_html__('Regenerate XML Sitemap', 'filter-everything');
            }
        } else {
            echo esc_html__('Generate XML Sitemap', 'filter-everything');
        }
        ?>
    </span>
    </a>
    <?php
    echo flrt_tooltip(array(
            'tooltip' => wp_kses(
                __('Generates an XML sitemap of URLs available for indexing to help search engines crawl your site faster. You need to submit this sitemap to search engines for proper indexing.', 'filter-everything'),
                array()
            )
        )
    );
    ?>
    <a id="wpc-xml-link" class="button <?php echo (!$xml_file_link) ? 'hidden-xml-button' : ''; ?>"
       href="<?php echo flrt_get_index_sitemap(); ?>"
       target="_blank"><?php echo esc_html__('Open XML sitemap', 'filter-everything'); ?>
        <svg xmlns="http://www.w3.org/2000/svg" width="13px" height="13px" viewBox="0 0 24 24">
            <path fill-rule="evenodd"
                  d="M5,2 L7,2 C7.55228475,2 8,2.44771525 8,3 C8,3.51283584 7.61395981,3.93550716 7.11662113,3.99327227 L7,4 L5,4 C4.48716416,4 4.06449284,4.38604019 4.00672773,4.88337887 L4,5 L4,19 C4,19.5128358 4.38604019,19.9355072 4.88337887,19.9932723 L5,20 L19,20 C19.5128358,20 19.9355072,19.6139598 19.9932723,19.1166211 L20,19 L20,17 C20,16.4477153 20.4477153,16 21,16 C21.5128358,16 21.9355072,16.3860402 21.9932723,16.8833789 L22,17 L22,19 C22,20.5976809 20.75108,21.9036609 19.1762728,21.9949073 L19,22 L5,22 C3.40231912,22 2.09633912,20.75108 2.00509269,19.1762728 L2,19 L2,5 C2,3.40231912 3.24891996,2.09633912 4.82372721,2.00509269 L5,2 L7,2 L5,2 Z M21,2 L21.081,2.003 L21.2007258,2.02024007 L21.2007258,2.02024007 L21.3121425,2.04973809 L21.3121425,2.04973809 L21.4232215,2.09367336 L21.5207088,2.14599545 L21.5207088,2.14599545 L21.6167501,2.21278596 L21.7071068,2.29289322 L21.7071068,2.29289322 L21.8036654,2.40469339 L21.8036654,2.40469339 L21.8753288,2.5159379 L21.9063462,2.57690085 L21.9063462,2.57690085 L21.9401141,2.65834962 L21.9401141,2.65834962 L21.9641549,2.73400703 L21.9641549,2.73400703 L21.9930928,2.8819045 L21.9930928,2.8819045 L22,3 L22,3 L22,9 C22,9.55228475 21.5522847,10 21,10 C20.4477153,10 20,9.55228475 20,9 L20,5.414 L13.7071068,11.7071068 C13.3466228,12.0675907 12.7793918,12.0953203 12.3871006,11.7902954 L12.2928932,11.7071068 C11.9324093,11.3466228 11.9046797,10.7793918 12.2097046,10.3871006 L12.2928932,10.2928932 L18.584,4 L15,4 C14.4477153,4 14,3.55228475 14,3 C14,2.44771525 14.4477153,2 15,2 L21,2 Z"/>
        </svg>
    </a>
</div>
